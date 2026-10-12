<?php

namespace App\Controller\System;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/system')]
class SystemController extends AbstractController
{
    public function __construct(private Connection $db) {}

    #[Route('', name: 'system_index', methods: ['GET'])]
    public function index(): Response
    {
        $checks = $this->runHealthChecks();
        $failed = count(array_filter($checks, fn($c) => !$c['ok']));
        $disk   = disk_free_space('/');

        return $this->render('system/index.html.twig', [
            'checks' => $checks,
            'failed' => $failed,
            'disk'   => $disk,
        ]);
    }

    #[Route('/extensions', name: 'system_extensions', methods: ['GET', 'POST'])]
    public function extensions(Request $request): Response
    {
        // Extension registry — stored in settings key 'extensions_registry'
        $raw  = $this->db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'extensions_registry'") ?: '[]';
        $installed = json_decode((string) $raw, true) ?? [];

        return $this->render('system/extensions.html.twig', [
            'installed' => $installed,
            'pending'   => null,
        ]);
    }

    #[Route('/extensions/upload', name: 'system_extensions_upload', methods: ['POST'])]
    #[IsGranted('ROLE_SYSADMIN')]
    public function extensionsUpload(Request $request): Response
    {
        $file = $request->files->get('package');
        if (!$file || $file->getClientOriginalExtension() !== 'zip') {
            $this->addFlash('error', 'Please upload a .zip extension package.');
            return $this->redirectToRoute('system_extensions');
        }

        // TODO: unzip, validate manifest, store as pending
        $this->addFlash('info', 'Extension upload received. Review and install from the Extensions page.');
        return $this->redirectToRoute('system_extensions');
    }

    #[Route('/logs', name: 'system_logs', methods: ['GET'])]
    public function logs(Request $request): Response
    {
        $page  = max(1, (int) $request->query->get('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $rows = $this->db->fetchAllAssociative(
            'SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
        $total = (int) $this->db->fetchOne('SELECT COUNT(*) FROM audit_logs');

        return $this->render('system/logs.html.twig', [
            'rows'   => $rows,
            'total'  => $total,
            'page'   => $page,
            'pages'  => (int) ceil($total / $limit),
        ]);
    }

    #[Route('/signins', name: 'system_signins', methods: ['GET'])]
    public function signins(Request $request): Response
    {
        $rows = $this->db->fetchAllAssociative(
            'SELECT lh.*, u.name AS user_name FROM login_history lh
             LEFT JOIN users u ON u.id = lh.user_id
             ORDER BY lh.logged_in_at DESC LIMIT 200'
        );

        return $this->render('system/signins.html.twig', ['rows' => $rows]);
    }

    #[Route('/repair', name: 'system_repair', methods: ['POST'])]
    #[IsGranted('ROLE_SYSADMIN')]
    public function repair(): Response
    {
        // Ensure all required settings keys exist
        $defaults = [
            'church_name'   => 'My Church',
            'sms_at_api_key' => '',
            'sms_at_username' => 'sandbox',
            'sms_sender_id'  => 'ChurchOS',
        ];
        foreach ($defaults as $key => $val) {
            $exists = $this->db->fetchOne('SELECT 1 FROM settings WHERE setting_key = ?', [$key]);
            if (!$exists) {
                $this->db->executeStatement(
                    'INSERT INTO settings (setting_key, setting_value, created_at) VALUES (?, ?, NOW())',
                    [$key, $val]
                );
            }
        }
        $this->addFlash('success', 'Self-repair completed.');
        return $this->redirectToRoute('system_index');
    }

    // ── Private helpers ─────────────────────────────────────────────

    private function runHealthChecks(): array
    {
        $checks = [];

        // Database
        try {
            $this->db->executeQuery('SELECT 1');
            $checks[] = ['group' => 'Database', 'name' => 'Connection', 'ok' => true, 'info' => 'OK'];
        } catch (\Exception $e) {
            $checks[] = ['group' => 'Database', 'name' => 'Connection', 'ok' => false, 'info' => $e->getMessage()];
        }

        // Required tables
        foreach (['users', 'centres', 'members', 'giving_batches', 'calendar_events'] as $t) {
            $exists = $this->db->fetchOne("SHOW TABLES LIKE '$t'");
            $checks[] = ['group' => 'Database', 'name' => "Table: $t", 'ok' => (bool) $exists, 'info' => $exists ? 'Present' : 'Missing — run migrations'];
        }

        // PHP extensions
        foreach (['pdo_mysql', 'json', 'mbstring', 'openssl', 'intl'] as $ext) {
            $ok = extension_loaded($ext);
            $checks[] = ['group' => 'PHP', 'name' => "ext/$ext", 'ok' => $ok, 'info' => $ok ? 'Loaded' : 'Missing'];
        }

        // PHP version
        $phpOk = version_compare(PHP_VERSION, '8.2.0', '>=');
        $checks[] = ['group' => 'PHP', 'name' => 'PHP version', 'ok' => $phpOk, 'info' => PHP_VERSION . ($phpOk ? '' : ' (8.2+ required)')];

        // Writable directories
        foreach (['var/cache', 'var/log', 'public/uploads'] as $dir) {
            $path = $this->getParameter('kernel.project_dir') . '/' . $dir;
            $ok   = is_writable($path);
            $checks[] = ['group' => 'Filesystem', 'name' => $dir, 'ok' => $ok, 'info' => $ok ? 'Writable' : 'Not writable'];
        }

        return $checks;
    }
}
