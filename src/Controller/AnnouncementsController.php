<?php

namespace App\Controller;

use App\Service\AuditService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_VIEWER')]
#[Route('/announcements')]
class AnnouncementsController extends AbstractController
{
    public function __construct(
        private Connection $db,
        private AuditService $audit,
    ) {}

    #[Route('', name: 'announcements_index')]
    public function index(Request $req): Response
    {
        $cid    = $this->centreScope();
        $page   = max(1, (int) $req->query->get('page', 1));
        $limit  = 20;
        $status = $req->query->get('status', 'all');

        $where  = $cid ? 'WHERE a.centre_id=?' : 'WHERE 1=1';
        $params = $cid ? [$cid] : [];
        if ($status !== 'all') { $where .= ' AND a.status=?'; $params[] = $status; }

        $rows  = $this->db->fetchAllAssociative(
            "SELECT a.*, u.name as author FROM announcements a LEFT JOIN users u ON u.id=a.created_by_id
             $where ORDER BY a.is_pinned DESC, a.created_at DESC LIMIT $limit OFFSET " . (($page - 1) * $limit),
            $params
        );
        $total = (int) $this->db->fetchOne("SELECT COUNT(*) FROM announcements a $where", $params);

        return $this->render('announcements/index.html.twig', [
            'rows'   => $rows,
            'total'  => $total,
            'page'   => $page,
            'pages'  => (int) ceil($total / $limit),
            'status' => $status,
        ]);
    }

    #[Route('/new', name: 'announcements_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLERK')]
    public function new(Request $req): Response
    {
        if ($req->isMethod('POST')) {
            $this->db->insert('announcements', [
                'centre_id'    => $this->centreScope(),
                'title'        => $req->request->get('title'),
                'body'         => $req->request->get('body'),
                'audience'     => $req->request->get('audience', 'all'),
                'publish_date' => $req->request->get('publish_date') ?: date('Y-m-d'),
                'expiry_date'  => $req->request->get('expiry_date') ?: null,
                'is_pinned'    => $req->request->get('is_pinned') ? 1 : 0,
                'is_sms'       => $req->request->get('is_sms') ? 1 : 0,
                'status'       => $req->request->get('status', 'draft'),
                'created_by_id'=> $this->getUser()?->getId(),
                'created_at'   => (new \DateTime())->format('Y-m-d H:i:s'),
                'updated_at'   => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
            $newId = $this->db->lastInsertId();
            $this->audit->log('create', 'announcement', (int) $newId, ['title' => $req->request->get('title')]);
            $this->addFlash('success', 'Announcement saved.');
            return $this->redirectToRoute('announcements_index');
        }

        return $this->render('announcements/form.html.twig', ['ann' => [], 'isNew' => true]);
    }

    #[Route('/{id}/edit', name: 'announcements_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_CLERK')]
    public function edit(int $id, Request $req): Response
    {
        $ann = $this->fetch($id);
        if ($req->isMethod('POST')) {
            $this->db->update('announcements', [
                'title'       => $req->request->get('title'),
                'body'        => $req->request->get('body'),
                'audience'    => $req->request->get('audience', 'all'),
                'publish_date'=> $req->request->get('publish_date'),
                'expiry_date' => $req->request->get('expiry_date') ?: null,
                'is_pinned'   => $req->request->get('is_pinned') ? 1 : 0,
                'is_sms'      => $req->request->get('is_sms') ? 1 : 0,
                'status'      => $req->request->get('status', 'draft'),
                'updated_at'  => (new \DateTime())->format('Y-m-d H:i:s'),
            ], ['id' => $id]);
            $this->audit->log('update', 'announcement', $id, []);
            $this->addFlash('success', 'Announcement updated.');
            return $this->redirectToRoute('announcements_index');
        }
        return $this->render('announcements/form.html.twig', ['ann' => $ann, 'isNew' => false]);
    }

    #[Route('/{id}/publish', name: 'announcements_publish', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EXECUTIVE')]
    public function publish(int $id, Request $req): Response
    {
        if (!$this->isCsrfTokenValid('pub_ann_' . $id, (string) $req->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('announcements_index');
        }
        $this->db->update('announcements', ['status' => 'published', 'updated_at' => (new \DateTime())->format('Y-m-d H:i:s')], ['id' => $id]);
        $this->audit->log('publish', 'announcement', $id, []);
        $this->addFlash('success', 'Announcement published.');
        return $this->redirectToRoute('announcements_index');
    }

    #[Route('/{id}/delete', name: 'announcements_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, Request $req): Response
    {
        if (!$this->isCsrfTokenValid('del_ann_' . $id, (string) $req->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
        } else {
            $this->db->delete('announcements', ['id' => $id]);
            $this->audit->log('delete', 'announcement', $id, []);
            $this->addFlash('success', 'Announcement deleted.');
        }
        return $this->redirectToRoute('announcements_index');
    }

    private function fetch(int $id): array
    {
        $a = $this->db->fetchAssociative('SELECT * FROM announcements WHERE id=?', [$id]);
        if (!$a) throw $this->createNotFoundException('Announcement not found.');
        return $a;
    }

    private function centreScope(): ?int
    {
        $user = $this->getUser();
        if (!$user || $user->isSysAdmin() || $user->isAdmin()) return null;
        return $user->getCentre()?->getId();
    }
}
