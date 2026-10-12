<?php

namespace App\Controller\Attendance;

use App\Service\AuditService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_VIEWER')]
#[Route('/attendance')]
class AttendanceController extends AbstractController
{
    public function __construct(
        private Connection $db,
        private AuditService $audit,
    ) {}

    #[Route('', name: 'attendance_home')]
    public function home(Request $req): Response
    {
        $cid    = $this->centreScope();
        $page   = max(1, (int) $req->query->get('page', 1));
        $limit  = 20;
        $where  = $cid ? 'WHERE s.centre_id=?' : 'WHERE 1=1';
        $params = $cid ? [$cid] : [];

        $rows = $this->db->fetchAllAssociative(
            "SELECT s.*, u.name as submitted_by_name, a.name as approved_by_name
             FROM service_reports s
             LEFT JOIN users u ON u.id = s.submitted_by_id
             LEFT JOIN users a ON a.id = s.approved_by_id
             $where ORDER BY s.service_date DESC LIMIT $limit OFFSET " . (($page - 1) * $limit),
            $params
        );
        $total = (int) $this->db->fetchOne("SELECT COUNT(*) FROM service_reports s $where", $params);

        return $this->render('attendance/home.html.twig', [
            'rows'  => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => (int) ceil($total / $limit),
        ]);
    }

    #[Route('/new', name: 'attendance_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLERK')]
    public function new(Request $req): Response
    {
        $serviceTimes = $this->getServiceTimes();
        if ($req->isMethod('POST')) {
            $adults   = (int) $req->request->get('attendance_adults', 0);
            $youth    = (int) $req->request->get('attendance_youth', 0);
            $children = (int) $req->request->get('attendance_children', 0);

            $this->db->insert('service_reports', [
                'centre_id'           => $this->centreScope(),
                'service_date'        => $req->request->get('service_date'),
                'service_time_id'     => $req->request->get('service_time_id') ?: null,
                'service_type'        => $req->request->get('service_type'),
                'attendance_adults'   => $adults,
                'attendance_youth'    => $youth,
                'attendance_children' => $children,
                'attendance_total'    => $adults + $youth + $children,
                'first_timers'        => (int) $req->request->get('first_timers', 0),
                'new_believers'       => (int) $req->request->get('new_believers', 0),
                'offering_total'      => (float) $req->request->get('offering_total', 0),
                'preacher'            => $req->request->get('preacher'),
                'sermon_title'        => $req->request->get('sermon_title'),
                'sermon_scripture'    => $req->request->get('sermon_scripture'),
                'notes'               => $req->request->get('notes'),
                'status'              => 'draft',
                'submitted_by_id'     => $this->getUser()?->getId(),
                'created_by_id'       => $this->getUser()?->getId(),
                'created_at'          => (new \DateTime())->format('Y-m-d H:i:s'),
                'updated_at'          => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
            $id = $this->db->lastInsertId();
            $this->audit->log('create', 'service_report', (int) $id, ['date' => $req->request->get('service_date')]);
            $this->addFlash('success', 'Service report saved as draft.');
            return $this->redirectToRoute('attendance_view', ['id' => $id]);
        }

        return $this->render('attendance/form.html.twig', [
            'report'       => [],
            'serviceTimes' => $serviceTimes,
            'isNew'        => true,
        ]);
    }

    #[Route('/{id}', name: 'attendance_view', requirements: ['id' => '\d+'])]
    public function view(int $id): Response
    {
        $report = $this->fetchReport($id);
        return $this->render('attendance/view.html.twig', ['report' => $report]);
    }

    #[Route('/{id}/edit', name: 'attendance_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_CLERK')]
    public function edit(int $id, Request $req): Response
    {
        $report = $this->fetchReport($id);
        if ($report['status'] === 'approved') {
            $this->addFlash('error', 'Approved reports cannot be edited.');
            return $this->redirectToRoute('attendance_view', ['id' => $id]);
        }

        $serviceTimes = $this->getServiceTimes();
        if ($req->isMethod('POST')) {
            $adults   = (int) $req->request->get('attendance_adults', 0);
            $youth    = (int) $req->request->get('attendance_youth', 0);
            $children = (int) $req->request->get('attendance_children', 0);

            $this->db->update('service_reports', [
                'service_date'        => $req->request->get('service_date'),
                'service_time_id'     => $req->request->get('service_time_id') ?: null,
                'service_type'        => $req->request->get('service_type'),
                'attendance_adults'   => $adults,
                'attendance_youth'    => $youth,
                'attendance_children' => $children,
                'attendance_total'    => $adults + $youth + $children,
                'first_timers'        => (int) $req->request->get('first_timers', 0),
                'new_believers'       => (int) $req->request->get('new_believers', 0),
                'offering_total'      => (float) $req->request->get('offering_total', 0),
                'preacher'            => $req->request->get('preacher'),
                'sermon_title'        => $req->request->get('sermon_title'),
                'sermon_scripture'    => $req->request->get('sermon_scripture'),
                'notes'               => $req->request->get('notes'),
                'updated_at'          => (new \DateTime())->format('Y-m-d H:i:s'),
            ], ['id' => $id]);
            $this->audit->log('update', 'service_report', $id, []);
            $this->addFlash('success', 'Report updated.');
            return $this->redirectToRoute('attendance_view', ['id' => $id]);
        }

        return $this->render('attendance/form.html.twig', [
            'report'       => $report,
            'serviceTimes' => $serviceTimes,
            'isNew'        => false,
        ]);
    }

    #[Route('/{id}/submit', name: 'attendance_submit', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_CLERK')]
    public function submit(int $id, Request $req): Response
    {
        if (!$this->isCsrfTokenValid('submit_' . $id, (string) $req->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('attendance_view', ['id' => $id]);
        }
        $this->db->update('service_reports', [
            'status'         => 'submitted',
            'submitted_by_id'=> $this->getUser()?->getId(),
            'submitted_at'   => (new \DateTime())->format('Y-m-d H:i:s'),
            'updated_at'     => (new \DateTime())->format('Y-m-d H:i:s'),
        ], ['id' => $id]);
        $this->audit->log('submit', 'service_report', $id, []);
        $this->addFlash('success', 'Report submitted for approval.');
        return $this->redirectToRoute('attendance_view', ['id' => $id]);
    }

    #[Route('/{id}/approve', name: 'attendance_approve', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EXECUTIVE')]
    public function approve(int $id, Request $req): Response
    {
        if (!$this->isCsrfTokenValid('approve_' . $id, (string) $req->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('attendance_view', ['id' => $id]);
        }
        $this->db->update('service_reports', [
            'status'         => 'approved',
            'approved_by_id' => $this->getUser()?->getId(),
            'approved_at'    => (new \DateTime())->format('Y-m-d H:i:s'),
            'updated_at'     => (new \DateTime())->format('Y-m-d H:i:s'),
        ], ['id' => $id]);
        $this->audit->log('approve', 'service_report', $id, []);
        $this->addFlash('success', 'Report approved.');
        return $this->redirectToRoute('attendance_view', ['id' => $id]);
    }

    #[Route('/trends', name: 'attendance_trends')]
    public function trends(Request $req): Response
    {
        $months = min(24, max(3, (int) $req->query->get('months', 12)));
        $cid    = $this->centreScope();
        $where  = $cid ? 'AND centre_id=?' : '';
        $params = $cid ? [$months, $cid] : [$months];

        $data = $this->db->fetchAllAssociative(
            "SELECT DATE_FORMAT(service_date,'%Y-%m') as ym,
             SUM(attendance_total) as total, SUM(first_timers) as first_timers, COUNT(*) as services
             FROM service_reports
             WHERE status='approved' $where AND service_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY ym ORDER BY ym",
            $params
        );

        return $this->render('attendance/trends.html.twig', ['data' => $data, 'months' => $months]);
    }

    // ── Helpers ───────────────────────────────────────────────

    private function fetchReport(int $id): array
    {
        $r = $this->db->fetchAssociative('SELECT * FROM service_reports WHERE id=?', [$id]);
        if (!$r) throw $this->createNotFoundException('Report not found.');
        return $r;
    }

    private function getServiceTimes(): array
    {
        $cid = $this->centreScope();
        $sql = 'SELECT * FROM service_times WHERE is_active=1' . ($cid ? ' AND centre_id=?' : '') . ' ORDER BY day_of_week, start_time';
        return $this->db->fetchAllAssociative($sql, $cid ? [$cid] : []);
    }

    private function centreScope(): ?int
    {
        $user = $this->getUser();
        if (!$user || $user->isSysAdmin() || $user->isAdmin()) return null;
        return $user->getCentre()?->getId();
    }
}
