<?php

namespace App\Controller\Giving;

use App\Service\AuditService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_VIEWER')]
#[Route('/giving')]
class GivingController extends AbstractController
{
    public function __construct(
        private Connection $db,
        private AuditService $audit,
    ) {}

    // ── Dashboard ──────────────────────────────────────────────

    #[Route('', name: 'giving_home')]
    public function home(): Response
    {
        $cid    = $this->centreScope();
        $where  = $cid ? 'WHERE centre_id = ?' : 'WHERE 1=1';
        $params = $cid ? [$cid] : [];

        $stats = [
            'batches_open'   => (int) $this->db->fetchOne("SELECT COUNT(*) FROM giving_batches $where AND status='open'", $params),
            'batches_total'  => (int) $this->db->fetchOne("SELECT COUNT(*) FROM giving_batches $where", $params),
            'month_total'    => (float) $this->db->fetchOne("SELECT COALESCE(SUM(c.amount),0) FROM contributions c JOIN giving_batches b ON b.id=c.batch_id " . ($cid ? "WHERE b.centre_id=?" : "WHERE 1=1") . " AND YEAR(c.transaction_date)=YEAR(CURDATE()) AND MONTH(c.transaction_date)=MONTH(CURDATE())", $params),
            'funds_count'    => (int) $this->db->fetchOne("SELECT COUNT(*) FROM funds $where AND is_active=1", $params),
            'pledges_active' => (int) $this->db->fetchOne("SELECT COUNT(*) FROM pledges $where AND status='active'", $params),
        ];

        $recentBatches = $this->db->fetchAllAssociative(
            "SELECT * FROM giving_batches $where ORDER BY batch_date DESC LIMIT 10",
            $params
        );

        return $this->render('giving/home.html.twig', [
            'stats'         => $stats,
            'recentBatches' => $recentBatches,
        ]);
    }

    // ── Batches ────────────────────────────────────────────────

    #[Route('/batches', name: 'giving_batches')]
    public function batches(Request $req): Response
    {
        $cid    = $this->centreScope();
        $page   = max(1, (int) $req->query->get('page', 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $where  = $cid ? 'WHERE centre_id = ?' : 'WHERE 1=1';
        $params = $cid ? [$cid] : [];

        $rows  = $this->db->fetchAllAssociative(
            "SELECT * FROM giving_batches $where ORDER BY batch_date DESC LIMIT $limit OFFSET $offset",
            $params
        );
        $total = (int) $this->db->fetchOne("SELECT COUNT(*) FROM giving_batches $where", $params);

        return $this->render('giving/batches.html.twig', [
            'rows'  => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => (int) ceil($total / $limit),
        ]);
    }

    #[Route('/batches/new', name: 'giving_batch_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLERK')]
    public function batchNew(Request $req): Response
    {
        if ($req->isMethod('POST')) {
            $ref = 'BAT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $this->db->insert('giving_batches', [
                'centre_id'     => $this->centreScope(),
                'batch_ref'     => $ref,
                'batch_date'    => $req->request->get('batch_date'),
                'description'   => $req->request->get('description'),
                'status'        => 'open',
                'created_by_id' => $this->getUser()?->getId(),
                'created_at'    => (new \DateTime())->format('Y-m-d H:i:s'),
                'updated_at'    => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
            $batchId = $this->db->lastInsertId();
            $this->audit->log('create', 'giving_batch', (int) $batchId, ['ref' => $ref]);
            $this->addFlash('success', "Batch $ref created.");
            return $this->redirectToRoute('giving_batch_view', ['id' => $batchId]);
        }

        return $this->render('giving/batch_form.html.twig', [
            'batch' => [],
            'isNew' => true,
        ]);
    }

    #[Route('/batches/{id}', name: 'giving_batch_view', requirements: ['id' => '\d+'])]
    public function batchView(int $id): Response
    {
        $batch = $this->fetchBatch($id);
        $contributions = $this->db->fetchAllAssociative(
            'SELECT c.*, f.name as fund_name FROM contributions c LEFT JOIN funds f ON f.id=c.fund_id WHERE c.batch_id=? ORDER BY c.id',
            [$id]
        );
        $funds = $this->db->fetchAllAssociative(
            'SELECT * FROM funds WHERE is_active=1 ORDER BY name',
            []
        );

        return $this->render('giving/batch_view.html.twig', [
            'batch'         => $batch,
            'contributions' => $contributions,
            'funds'         => $funds,
        ]);
    }

    #[Route('/batches/{id}/add-contribution', name: 'giving_contribution_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_CLERK')]
    public function addContribution(int $id, Request $req): Response
    {
        $batch = $this->fetchBatch($id);
        if ($batch['status'] !== 'open') {
            $this->addFlash('error', 'Batch is not open.');
            return $this->redirectToRoute('giving_batch_view', ['id' => $id]);
        }

        $amount = (float) $req->request->get('amount', 0);
        if ($amount <= 0) {
            $this->addFlash('error', 'Amount must be greater than 0.');
            return $this->redirectToRoute('giving_batch_view', ['id' => $id]);
        }

        $this->db->insert('contributions', [
            'batch_id'         => $id,
            'centre_id'        => $batch['centre_id'],
            'fund_id'          => $req->request->get('fund_id') ?: null,
            'member_id'        => $req->request->get('member_id') ?: null,
            'contributor_name' => $req->request->get('contributor_name'),
            'amount'           => $amount,
            'method'           => $req->request->get('method', 'cash'),
            'reference'        => $req->request->get('reference'),
            'transaction_date' => $batch['batch_date'],
            'notes'            => $req->request->get('notes'),
            'created_by_id'    => $this->getUser()?->getId(),
            'created_at'       => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        $this->db->executeStatement(
            'UPDATE giving_batches SET total_amount = (SELECT COALESCE(SUM(amount),0) FROM contributions WHERE batch_id=?) WHERE id=?',
            [$id, $id]
        );

        $this->addFlash('success', 'Contribution recorded.');
        return $this->redirectToRoute('giving_batch_view', ['id' => $id]);
    }

    #[Route('/batches/{id}/close', name: 'giving_batch_close', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EXECUTIVE')]
    public function batchClose(int $id, Request $req): Response
    {
        if (!$this->isCsrfTokenValid('close_batch_' . $id, (string) $req->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('giving_batch_view', ['id' => $id]);
        }
        $this->db->update('giving_batches', [
            'status'         => 'closed',
            'approved_by_id' => $this->getUser()?->getId(),
            'approved_at'    => (new \DateTime())->format('Y-m-d H:i:s'),
            'updated_at'     => (new \DateTime())->format('Y-m-d H:i:s'),
        ], ['id' => $id]);
        $this->audit->log('close', 'giving_batch', $id, []);
        $this->addFlash('success', 'Batch closed and approved.');
        return $this->redirectToRoute('giving_batch_view', ['id' => $id]);
    }

    // ── Funds ──────────────────────────────────────────────────

    #[Route('/funds', name: 'giving_funds')]
    public function funds(): Response
    {
        $cid   = $this->centreScope();
        $where = $cid ? 'WHERE centre_id=?' : 'WHERE 1=1';
        $rows  = $this->db->fetchAllAssociative("SELECT * FROM funds $where ORDER BY name", $cid ? [$cid] : []);
        return $this->render('giving/funds.html.twig', ['funds' => $rows]);
    }

    #[Route('/funds/new', name: 'giving_fund_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function fundNew(Request $req): Response
    {
        if ($req->isMethod('POST')) {
            $this->db->insert('funds', [
                'centre_id'     => $this->centreScope(),
                'name'          => $req->request->get('name'),
                'code'          => $req->request->get('code'),
                'type'          => $req->request->get('type', 'general'),
                'description'   => $req->request->get('description'),
                'target_amount' => $req->request->get('target_amount') ?: null,
                'is_active'     => 1,
                'created_by_id' => $this->getUser()?->getId(),
                'created_at'    => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
            $this->addFlash('success', 'Fund created.');
            return $this->redirectToRoute('giving_funds');
        }
        return $this->render('giving/fund_form.html.twig', ['fund' => [], 'isNew' => true]);
    }

    #[Route('/funds/{id}/edit', name: 'giving_fund_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function fundEdit(int $id, Request $req): Response
    {
        $fund = $this->db->fetchAssociative('SELECT * FROM funds WHERE id=?', [$id]);
        if (!$fund) throw $this->createNotFoundException('Fund not found.');

        if ($req->isMethod('POST')) {
            $this->db->update('funds', [
                'name'          => $req->request->get('name'),
                'code'          => $req->request->get('code'),
                'type'          => $req->request->get('type', 'general'),
                'description'   => $req->request->get('description'),
                'target_amount' => $req->request->get('target_amount') ?: null,
                'updated_at'    => (new \DateTime())->format('Y-m-d H:i:s'),
            ], ['id' => $id]);
            $this->addFlash('success', 'Fund updated.');
            return $this->redirectToRoute('giving_funds');
        }
        return $this->render('giving/fund_form.html.twig', ['fund' => $fund, 'isNew' => false]);
    }

    // ── Pledges ────────────────────────────────────────────────

    #[Route('/pledges', name: 'giving_pledges')]
    public function pledges(Request $req): Response
    {
        $cid    = $this->centreScope();
        $page   = max(1, (int) $req->query->get('page', 1));
        $limit  = 25;
        $where  = $cid ? 'WHERE p.centre_id=?' : 'WHERE 1=1';
        $params = $cid ? [$cid] : [];

        $rows  = $this->db->fetchAllAssociative(
            "SELECT p.*, f.name as fund_name FROM pledges p LEFT JOIN funds f ON f.id=p.fund_id $where ORDER BY p.created_at DESC LIMIT $limit OFFSET " . (($page - 1) * $limit),
            $params
        );
        $total = (int) $this->db->fetchOne("SELECT COUNT(*) FROM pledges p $where", $params);

        return $this->render('giving/pledges.html.twig', [
            'rows'  => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => (int) ceil($total / $limit),
        ]);
    }

    #[Route('/pledges/new', name: 'giving_pledge_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLERK')]
    public function pledgeNew(Request $req): Response
    {
        $funds = $this->db->fetchAllAssociative('SELECT * FROM funds WHERE is_active=1 ORDER BY name');
        if ($req->isMethod('POST')) {
            $this->db->insert('pledges', [
                'centre_id'      => $this->centreScope(),
                'fund_id'        => $req->request->get('fund_id') ?: null,
                'member_id'      => $req->request->get('member_id') ?: null,
                'pledger_name'   => $req->request->get('pledger_name'),
                'phone'          => $req->request->get('phone'),
                'pledged_amount' => $req->request->get('pledged_amount'),
                'pledge_date'    => $req->request->get('pledge_date'),
                'due_date'       => $req->request->get('due_date') ?: null,
                'frequency'      => $req->request->get('frequency', 'once'),
                'status'         => 'active',
                'notes'          => $req->request->get('notes'),
                'created_by_id'  => $this->getUser()?->getId(),
                'created_at'     => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
            $this->addFlash('success', 'Pledge recorded.');
            return $this->redirectToRoute('giving_pledges');
        }
        return $this->render('giving/pledge_form.html.twig', ['pledge' => [], 'funds' => $funds, 'isNew' => true]);
    }

    // ── Statements ────────────────────────────────────────────

    #[Route('/statements', name: 'giving_statements')]
    public function statements(Request $req): Response
    {
        $year   = (int) $req->query->get('year', date('Y'));
        $fundId = (int) $req->query->get('fund_id', 0);
        $cid    = $this->centreScope();

        $sql = 'SELECT f.name as fund_name, MONTH(c.transaction_date) as month, SUM(c.amount) as total
                FROM contributions c
                JOIN giving_batches b ON b.id=c.batch_id
                LEFT JOIN funds f ON f.id=c.fund_id
                WHERE YEAR(c.transaction_date)=?';
        $params = [$year];
        if ($cid) { $sql .= ' AND b.centre_id=?'; $params[] = $cid; }
        if ($fundId) { $sql .= ' AND c.fund_id=?'; $params[] = $fundId; }
        $sql .= ' GROUP BY f.name, MONTH(c.transaction_date) ORDER BY month, fund_name';

        $data  = $this->db->fetchAllAssociative($sql, $params);
        $funds = $this->db->fetchAllAssociative('SELECT * FROM funds WHERE is_active=1 ORDER BY name');

        return $this->render('giving/statements.html.twig', [
            'data'   => $data,
            'year'   => $year,
            'fundId' => $fundId,
            'funds'  => $funds,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────

    private function fetchBatch(int $id): array
    {
        $b = $this->db->fetchAssociative('SELECT * FROM giving_batches WHERE id=?', [$id]);
        if (!$b) throw $this->createNotFoundException('Batch not found.');
        return $b;
    }

    private function centreScope(): ?int
    {
        $user = $this->getUser();
        if (!$user || $user->isSysAdmin() || $user->isAdmin()) return null;
        return $user->getCentre()?->getId();
    }
}
