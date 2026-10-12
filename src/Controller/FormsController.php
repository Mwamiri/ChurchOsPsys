<?php

namespace App\Controller;

use App\Service\AuditService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FormsController extends AbstractController
{
    public function __construct(
        private Connection $db,
        private AuditService $audit,
    ) {}

    // ── Admin management ─────────────────────────────────────

    #[IsGranted('ROLE_CLERK')]
    #[Route('/admin/forms', name: 'forms_index')]
    public function index(Request $req): Response
    {
        $cid    = $this->centreScope();
        $page   = max(1, (int) $req->query->get('page', 1));
        $limit  = 20;
        $where  = $cid ? 'WHERE centre_id=?' : 'WHERE 1=1';
        $params = $cid ? [$cid] : [];

        $rows  = $this->db->fetchAllAssociative(
            "SELECT * FROM forms $where ORDER BY created_at DESC LIMIT $limit OFFSET " . (($page - 1) * $limit),
            $params
        );
        $total = (int) $this->db->fetchOne("SELECT COUNT(*) FROM forms $where", $params);

        return $this->render('forms/index.html.twig', [
            'rows'  => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => (int) ceil($total / $limit),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/forms/new', name: 'forms_new', methods: ['GET', 'POST'])]
    public function new(Request $req): Response
    {
        if ($req->isMethod('POST')) {
            $slug = $this->makeSlug((string) $req->request->get('title', ''));
            $this->db->insert('forms', [
                'centre_id'     => $this->centreScope(),
                'title'         => $req->request->get('title'),
                'description'   => $req->request->get('description'),
                'slug'          => $slug,
                'fields'        => $req->request->get('fields', '[]'),
                'settings'      => json_encode([
                    'submit_label'  => $req->request->get('submit_label', 'Submit'),
                    'success_msg'   => $req->request->get('success_msg', 'Thank you!'),
                    'email_notify'  => $req->request->get('email_notify'),
                ]),
                'is_active'     => 1,
                'require_login' => $req->request->get('require_login') ? 1 : 0,
                'created_by_id' => $this->getUser()?->getId(),
                'created_at'    => (new \DateTime())->format('Y-m-d H:i:s'),
                'updated_at'    => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
            $newId = $this->db->lastInsertId();
            $this->audit->log('create', 'form', (int) $newId, ['title' => $req->request->get('title')]);
            $this->addFlash('success', 'Form created.');
            return $this->redirectToRoute('forms_index');
        }
        return $this->render('forms/form_builder.html.twig', ['form' => [], 'isNew' => true]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/forms/{id}/edit', name: 'forms_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $req): Response
    {
        $form = $this->fetchForm($id);
        if ($req->isMethod('POST')) {
            $this->db->update('forms', [
                'title'         => $req->request->get('title'),
                'description'   => $req->request->get('description'),
                'fields'        => $req->request->get('fields', '[]'),
                'settings'      => json_encode([
                    'submit_label' => $req->request->get('submit_label', 'Submit'),
                    'success_msg'  => $req->request->get('success_msg', 'Thank you!'),
                    'email_notify' => $req->request->get('email_notify'),
                ]),
                'require_login' => $req->request->get('require_login') ? 1 : 0,
                'updated_at'    => (new \DateTime())->format('Y-m-d H:i:s'),
            ], ['id' => $id]);
            $this->audit->log('update', 'form', $id, []);
            $this->addFlash('success', 'Form updated.');
            return $this->redirectToRoute('forms_index');
        }
        return $this->render('forms/form_builder.html.twig', ['form' => $form, 'isNew' => false]);
    }

    #[IsGranted('ROLE_CLERK')]
    #[Route('/admin/forms/{id}/submissions', name: 'forms_submissions', requirements: ['id' => '\d+'])]
    public function submissions(int $id, Request $req): Response
    {
        $form  = $this->fetchForm($id);
        $page  = max(1, (int) $req->query->get('page', 1));
        $limit = 25;

        $rows  = $this->db->fetchAllAssociative(
            'SELECT fs.*, u.name as submitter FROM form_submissions fs LEFT JOIN users u ON u.id=fs.submitter_id
             WHERE fs.form_id=? ORDER BY fs.created_at DESC LIMIT ? OFFSET ?',
            [$id, $limit, ($page - 1) * $limit]
        );
        $total = (int) $this->db->fetchOne('SELECT COUNT(*) FROM form_submissions WHERE form_id=?', [$id]);

        return $this->render('forms/submissions.html.twig', [
            'form'  => $form,
            'rows'  => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => (int) ceil($total / $limit),
        ]);
    }

    // ── Public form submission ────────────────────────────────

    #[Route('/f/{slug}', name: 'forms_public')]
    public function publicForm(string $slug, Request $req): Response
    {
        $form = $this->db->fetchAssociative('SELECT * FROM forms WHERE slug=? AND is_active=1', [$slug]);
        if (!$form) throw $this->createNotFoundException('Form not found.');

        if ($form['require_login'] && !$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        if ($req->isMethod('POST')) {
            $fields = json_decode((string) $form['fields'], true) ?? [];
            $data   = [];
            foreach ($fields as $f) {
                $data[$f['name']] = $req->request->get($f['name']);
            }
            $this->db->insert('form_submissions', [
                'form_id'      => $form['id'],
                'centre_id'    => $form['centre_id'],
                'submitter_id' => $this->getUser()?->getId(),
                'data'         => json_encode($data),
                'ip_address'   => $req->getClientIp(),
                'created_at'   => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
            $this->db->executeStatement('UPDATE forms SET submission_count=submission_count+1 WHERE id=?', [$form['id']]);

            $settings   = json_decode((string) ($form['settings'] ?? '{}'), true) ?? [];
            $successMsg = $settings['success_msg'] ?? 'Thank you for submitting!';
            return $this->render('forms/success.html.twig', ['message' => $successMsg, 'form' => $form]);
        }

        return $this->render('forms/public.html.twig', ['form' => $form]);
    }

    // ── Helpers ───────────────────────────────────────────────

    private function fetchForm(int $id): array
    {
        $f = $this->db->fetchAssociative('SELECT * FROM forms WHERE id=?', [$id]);
        if (!$f) throw $this->createNotFoundException('Form not found.');
        return $f;
    }

    private function makeSlug(string $title): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title) ?? $title);
        $slug = trim($slug, '-');
        $existing = $this->db->fetchOne('SELECT id FROM forms WHERE slug=?', [$slug]);
        if ($existing) $slug .= '-' . substr(md5(uniqid()), 0, 4);
        return $slug;
    }

    private function centreScope(): ?int
    {
        $user = $this->getUser();
        if (!$user || $user->isSysAdmin() || $user->isAdmin()) return null;
        return $user->getCentre()?->getId();
    }
}
