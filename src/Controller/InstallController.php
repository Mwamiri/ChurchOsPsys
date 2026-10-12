<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/install')]
class InstallController extends AbstractController
{
    public function __construct(
        private Connection $db,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {}

    #[Route('', name: 'install_index', methods: ['GET'])]
    public function index(): Response
    {
        if ($this->isInstalled()) {
            return $this->redirectToRoute('insights_dashboard');
        }
        return $this->render('install/index.html.twig', [
            'requirements' => $this->checkRequirements(),
        ]);
    }

    #[Route('/database', name: 'install_database', methods: ['GET', 'POST'])]
    public function database(Request $request): Response
    {
        if ($this->isInstalled()) {
            return $this->redirectToRoute('insights_dashboard');
        }

        $error = null;
        if ($request->isMethod('POST')) {
            $db_host = $request->request->get('db_host', '127.0.0.1');
            $db_name = $request->request->get('db_name', '');
            $db_user = $request->request->get('db_user', '');
            $db_pass = $request->request->get('db_pass', '');

            try {
                $testConn = \Doctrine\DBAL\DriverManager::getConnection([
                    'driver' => 'pdo_mysql',
                    'host'   => $db_host,
                    'dbname' => $db_name,
                    'user'   => $db_user,
                    'password' => $db_pass,
                    'charset' => 'utf8mb4',
                ]);
                $testConn->executeQuery('SELECT 1');
                // Store in session for next step
                $request->getSession()->set('install_db', compact('db_host', 'db_name', 'db_user', 'db_pass'));
                return $this->redirectToRoute('install_church');
            } catch (\Exception $e) {
                $error = 'Could not connect: ' . $e->getMessage();
            }
        }

        // Pre-fill from .env
        return $this->render('install/database.html.twig', [
            'error'   => $error,
            'db_host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'db_name' => $_ENV['DB_NAME'] ?? '',
            'db_user' => $_ENV['DB_USER'] ?? 'root',
        ]);
    }

    #[Route('/church', name: 'install_church', methods: ['GET', 'POST'])]
    public function church(Request $request): Response
    {
        if ($this->isInstalled()) {
            return $this->redirectToRoute('insights_dashboard');
        }

        if ($request->isMethod('POST')) {
            $request->getSession()->set('install_church', [
                'church_name' => $request->request->get('church_name'),
                'root_name'   => $request->request->get('root_name', 'Headquarters'),
                'timezone'    => $request->request->get('timezone', 'Africa/Nairobi'),
                'environment' => $request->request->get('environment', 'production'),
            ]);
            return $this->redirectToRoute('install_admin');
        }

        $zones = \DateTimeZone::listIdentifiers(\DateTimeZone::AFRICA);
        return $this->render('install/church.html.twig', [
            'zones' => $zones,
            'state' => $request->getSession()->get('install_church', []),
        ]);
    }

    #[Route('/admin', name: 'install_admin', methods: ['GET', 'POST'])]
    public function admin(Request $request): Response
    {
        if ($this->isInstalled()) {
            return $this->redirectToRoute('insights_dashboard');
        }

        $error = null;
        if ($request->isMethod('POST')) {
            $name     = trim((string) $request->request->get('name'));
            $email    = trim((string) $request->request->get('email'));
            $password = (string) $request->request->get('password');

            if (mb_strlen($password) < 8) {
                $error = 'Password must be at least 8 characters.';
            } else {
                $request->getSession()->set('install_admin', compact('name', 'email', 'password'));
                return $this->redirectToRoute('install_finish');
            }
        }

        return $this->render('install/admin.html.twig', [
            'error' => $error,
            'state' => $request->getSession()->get('install_admin', []),
        ]);
    }

    #[Route('/finish', name: 'install_finish', methods: ['GET', 'POST'])]
    public function finish(Request $request): Response
    {
        if ($this->isInstalled()) {
            return $this->redirectToRoute('insights_dashboard');
        }

        $church = $request->getSession()->get('install_church', []);
        $admin  = $request->getSession()->get('install_admin', []);

        if ($request->isMethod('POST')) {
            try {
                // Save church settings
                foreach ($church as $key => $val) {
                    $this->upsertSetting($key, (string) $val);
                }
                $this->upsertSetting('installed', '1');
                $this->upsertSetting('installed_at', date('Y-m-d H:i:s'));

                // Create sysadmin user
                $user = new User();
                $user->setName($admin['name'] ?? 'Administrator');
                $user->setEmail($admin['email'] ?? 'admin@example.com');
                $user->setRoles(['ROLE_SYSADMIN']);
                $user->setPassword($this->hasher->hashPassword($user, $admin['password'] ?? 'changeme'));
                $user->setIsActive(true);
                $this->em->persist($user);
                $this->em->flush();

                $request->getSession()->remove('install_church');
                $request->getSession()->remove('install_admin');
                $request->getSession()->remove('install_db');

                return $this->redirectToRoute('install_complete');
            } catch (\Exception $e) {
                return $this->render('install/finish.html.twig', [
                    'church' => $church,
                    'admin'  => $admin,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        return $this->render('install/finish.html.twig', [
            'church' => $church,
            'admin'  => $admin,
            'error'  => null,
        ]);
    }

    #[Route('/complete', name: 'install_complete', methods: ['GET'])]
    public function complete(): Response
    {
        return $this->render('install/complete.html.twig');
    }

    // ── Helpers ─────────────────────────────────────────────────────

    private function isInstalled(): bool
    {
        try {
            $val = $this->db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'installed'");
            return $val === '1';
        } catch (\Exception) {
            return false;
        }
    }

    private function checkRequirements(): array
    {
        return [
            ['name' => 'PHP 8.2+',      'ok' => version_compare(PHP_VERSION, '8.2.0', '>='), 'info' => PHP_VERSION],
            ['name' => 'pdo_mysql',      'ok' => extension_loaded('pdo_mysql'),  'info' => 'Required for MySQL'],
            ['name' => 'mbstring',       'ok' => extension_loaded('mbstring'),   'info' => 'Required for Unicode'],
            ['name' => 'openssl',        'ok' => extension_loaded('openssl'),    'info' => 'Required for security'],
            ['name' => 'var/ writable',  'ok' => is_writable($this->getParameter('kernel.project_dir') . '/var'), 'info' => 'Cache and logs'],
        ];
    }

    private function upsertSetting(string $key, string $value): void
    {
        $exists = $this->db->fetchOne('SELECT 1 FROM settings WHERE setting_key = ?', [$key]);
        if ($exists) {
            $this->db->executeStatement('UPDATE settings SET setting_value = ? WHERE setting_key = ?', [$value, $key]);
        } else {
            $this->db->executeStatement(
                'INSERT INTO settings (setting_key, setting_value, created_at) VALUES (?, ?, NOW())',
                [$key, $value]
            );
        }
    }
}
