<?php

namespace App\Controller\People;

use App\Service\AuditService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_VIEWER')]
#[Route('/people')]
class PeopleController extends AbstractController
{
    public function __construct(
        private Connection $db,
        private AuditService $audit,
    ) {}

    // ── Groups ────────────────────────────────────────────────

    #[Route('/groups', name: 'people_groups')]
    public function groups(Request $req): Response
    {
        $cid    = $this->centreScope();
        $page   = max(1, (int) $req->query->get('page', 1));
        $limit  = 20;
        $q      = trim((string) $req->query->get('q', ''));
        $where  = $cid ? 'WHERE g.centre_id=? AND g.is_deleted=0' : 'WHERE g.is_deleted=0';
        $params = $cid ? [$cid] : [];
        if ($q) { $where .= ' AND g.group_name LIKE ?'; $params[] = "%$q%"; }

        $rows  = $this->db->fetchAllAssociative(
            "SELECT g.*, (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id=g.id AND gm.is_active=1) as member_count
             FROM groups g $where ORDER BY g.group_name LIMIT $limit OFFSET " . (($page - 1) * $limit),
            $params
        );
        $total = (int) $this->db->fetchOne("SELECT COUNT(*) FROM groups g $where", $params);

        return $this->render('people/groups.html.twig', [
            'rows'  => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => (int) ceil($total / $limit),
            'q'     => $q,
        ]);
    }

    #[Route('/groups/{id}', name: 'people_group_view', requirements: ['id' => '\d+'])]
    public function groupView(int $id): Response
    {
        $group   = $this->db->fetchAssociative('SELECT * FROM `groups` WHERE id=? AND is_deleted=0', [$id]);
        if (!$group) throw $this->createNotFoundException('Group not found.');
        $members = $this->db->fetchAllAssociative(
            'SELECT gm.*, m.full_name, m.phone FROM group_members gm LEFT JOIN members m ON m.id=gm.member_id WHERE gm.group_id=? AND gm.is_active=1',
            [$id]
        );
        return $this->render('people/group_view.html.twig', ['group' => $group, 'members' => $members]);
    }

    #[Route('/groups/{id}/add-member', name: 'people_group_add_member', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_CLERK')]
    public function groupAddMember(int $id, Request $req): Response
    {
        $memberId = $req->request->get('member_id');
        $existing = $this->db->fetchOne('SELECT id FROM group_members WHERE group_id=? AND member_id=?', [$id, $memberId]);
        if ($existing) {
            $this->db->update('group_members', ['is_active' => 1], ['id' => $existing]);
        } else {
            $this->db->insert('group_members', [
                'group_id'      => $id,
                'member_id'     => $memberId ?: null,
                'member_name'   => $req->request->get('member_name'),
                'role_in_group' => $req->request->get('role_in_group'),
                'joined_date'   => $req->request->get('joined_date') ?: date('Y-m-d'),
                'is_active'     => 1,
                'created_at'    => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
        }
        $this->addFlash('success', 'Member added to group.');
        return $this->redirectToRoute('people_group_view', ['id' => $id]);
    }

    // ── Follow-ups ────────────────────────────────────────────

    #[Route('/follow-ups', name: 'people_followups')]
    public function followUps(Request $req): Response
    {
        $cid    = $this->centreScope();
        $status = $req->query->get('status', 'pending');
        $page   = max(1, (int) $req->query->get('page', 1));
        $limit  = 25;
        $where  = $cid ? 'WHERE f.centre_id=?' : 'WHERE 1=1';
        $params = $cid ? [$cid] : [];
        if ($status !== 'all') { $where .= ' AND f.status=?'; $params[] = $status; }

        $rows  = $this->db->fetchAllAssociative(
            "SELECT f.*, u.name as assigned_to FROM follow_ups f LEFT JOIN users u ON u.id=f.assigned_to_id
             $where ORDER BY f.follow_up_date ASC LIMIT $limit OFFSET " . (($page - 1) * $limit),
            $params
        );
        $total = (int) $this->db->fetchOne("SELECT COUNT(*) FROM follow_ups f $where", $params);

        return $this->render('people/followups.html.twig', [
            'rows'   => $rows,
            'total'  => $total,
            'page'   => $page,
            'pages'  => (int) ceil($total / $limit),
            'status' => $status,
        ]);
    }

    #[Route('/follow-ups/new', name: 'people_followup_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLERK')]
    public function followUpNew(Request $req): Response
    {
        if ($req->isMethod('POST')) {
            $this->db->insert('follow_ups', [
                'centre_id'       => $this->centreScope(),
                'subject_type'    => $req->request->get('subject_type', 'member'),
                'subject_id'      => (int) $req->request->get('subject_id', 0),
                'subject_name'    => $req->request->get('subject_name'),
                'assigned_to_id'  => $req->request->get('assigned_to_id') ?: null,
                'follow_up_date'  => $req->request->get('follow_up_date'),
                'method'          => $req->request->get('method', 'phone'),
                'status'          => 'pending',
                'notes'           => $req->request->get('notes'),
                'created_by_id'   => $this->getUser()?->getId(),
                'created_at'      => (new \DateTime())->format('Y-m-d H:i:s'),
                'updated_at'      => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
            $this->addFlash('success', 'Follow-up scheduled.');
            return $this->redirectToRoute('people_followups');
        }

        $users = $this->db->fetchAllAssociative('SELECT id, name FROM users WHERE is_active=1 ORDER BY name');
        return $this->render('people/followup_form.html.twig', ['users' => $users, 'fu' => [], 'isNew' => true]);
    }

    #[Route('/follow-ups/{id}/resolve', name: 'people_followup_resolve', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_CLERK')]
    public function followUpResolve(int $id, Request $req): Response
    {
        $this->db->update('follow_ups', [
            'status'     => 'completed',
            'outcome'    => $req->request->get('outcome'),
            'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ], ['id' => $id]);
        $this->addFlash('success', 'Follow-up marked as completed.');
        return $this->redirectToRoute('people_followups');
    }

    // ── Households ────────────────────────────────────────────

    #[Route('/households', name: 'people_households')]
    public function households(Request $req): Response
    {
        $cid    = $this->centreScope();
        $page   = max(1, (int) $req->query->get('page', 1));
        $limit  = 20;
        $where  = $cid ? 'WHERE centre_id=? AND is_deleted=0' : 'WHERE is_deleted=0';
        $params = $cid ? [$cid] : [];

        $rows  = $this->db->fetchAllAssociative(
            "SELECT * FROM households $where ORDER BY name LIMIT $limit OFFSET " . (($page - 1) * $limit),
            $params
        );
        $total = (int) $this->db->fetchOne("SELECT COUNT(*) FROM households $where", $params);

        return $this->render('people/households.html.twig', [
            'rows'  => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => (int) ceil($total / $limit),
        ]);
    }

    // ── Member moves ──────────────────────────────────────────

    #[Route('/moves', name: 'people_moves')]
    #[IsGranted('ROLE_ADMIN')]
    public function moves(Request $req): Response
    {
        $page  = max(1, (int) $req->query->get('page', 1));
        $limit = 20;
        $rows  = $this->db->fetchAllAssociative(
            'SELECT mm.*, m.full_name, c1.name as from_centre, c2.name as to_centre
             FROM member_moves mm
             LEFT JOIN members m ON m.id=mm.member_id
             LEFT JOIN centres c1 ON c1.id=mm.from_centre_id
             LEFT JOIN centres c2 ON c2.id=mm.to_centre_id
             ORDER BY mm.move_date DESC LIMIT ? OFFSET ?',
            [$limit, ($page - 1) * $limit]
        );
        $total = (int) $this->db->fetchOne('SELECT COUNT(*) FROM member_moves');
        return $this->render('people/moves.html.twig', [
            'rows'  => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => (int) ceil($total / $limit),
        ]);
    }

    private function centreScope(): ?int
    {
        $user = $this->getUser();
        if (!$user || $user->isSysAdmin() || $user->isAdmin()) return null;
        return $user->getCentre()?->getId();
    }
}
