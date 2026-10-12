<?php

namespace App\Controller\Admin;

use App\Service\AuditService;
use App\Service\SettingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/settings', name: 'admin_settings_')]
#[IsGranted('ROLE_ADMIN')]
class SettingsController extends AbstractController
{
    private const GROUPS = [
        'general' => [
            'church_name'      => ['label' => 'Church / Organisation name', 'type' => 'text', 'required' => true],
            'church_email'     => ['label' => 'Contact email', 'type' => 'email'],
            'church_phone'     => ['label' => 'Contact phone', 'type' => 'text'],
            'timezone'         => ['label' => 'Timezone', 'type' => 'text', 'default' => 'Africa/Nairobi'],
            'date_format'      => ['label' => 'Date format', 'type' => 'text', 'default' => 'd M Y'],
            'locale'           => ['label' => 'Default language', 'type' => 'select', 'options' => ['en' => 'English', 'sw' => 'Kiswahili']],
        ],
        'security' => [
            'session_timeout'  => ['label' => 'Idle timeout (minutes)', 'type' => 'number', 'default' => '30'],
            'max_login_fails'  => ['label' => 'Lock after failed attempts', 'type' => 'number', 'default' => '5'],
            'lockout_minutes'  => ['label' => 'Lockout duration (minutes)', 'type' => 'number', 'default' => '15'],
            'require_2fa'      => ['label' => 'Require two-factor for admins', 'type' => 'checkbox'],
        ],
        'privacy' => [
            'retention_months' => ['label' => 'Visitor retention (months)', 'type' => 'number', 'default' => '24'],
            'mask_contacts'    => ['label' => 'Mask contacts for low-privilege roles', 'type' => 'checkbox', 'default' => '1'],
        ],
        'modules' => [
            'mod_giving'       => ['label' => 'Giving module enabled', 'type' => 'checkbox', 'default' => '1'],
            'mod_attendance'   => ['label' => 'Attendance module enabled', 'type' => 'checkbox', 'default' => '1'],
            'mod_members'      => ['label' => 'Members / CRM enabled', 'type' => 'checkbox', 'default' => '1'],
            'mod_reports'      => ['label' => 'Reports enabled', 'type' => 'checkbox', 'default' => '1'],
            'mod_sms'          => ['label' => 'SMS (Africa\'s Talking) enabled', 'type' => 'checkbox'],
        ],
        'sms' => [
            'at_api_key'       => ['label' => 'Africa\'s Talking API key', 'type' => 'text'],
            'at_username'      => ['label' => 'Africa\'s Talking username', 'type' => 'text'],
            'at_sender_id'     => ['label' => 'Sender ID', 'type' => 'text'],
        ],
    ];

    public function __construct(
        private readonly SettingService $settings,
        private readonly AuditService $audit,
    ) {}

    #[Route('/{group}', name: 'index', defaults: ['group' => 'general'])]
    public function index(string $group, Request $request): Response
    {
        if (!array_key_exists($group, self::GROUPS)) {
            $group = 'general';
        }
        $fields = self::GROUPS[$group];
        $current = [];
        foreach ($fields as $key => $def) {
            $current[$key] = $this->settings->get($key, $def['default'] ?? null);
        }

        if ($request->isMethod('POST')) {
            foreach ($fields as $key => $def) {
                $value = $def['type'] === 'checkbox'
                    ? ($request->request->has($key) ? '1' : '0')
                    : $request->request->get($key, '');
                $this->settings->set($key, $value);
                $current[$key] = $value;
            }
            $this->audit->log('update', 'settings', null, ['group' => $group], $request->getClientIp());
            $this->addFlash('success', 'Settings saved.');
            return $this->redirectToRoute('admin_settings_index', ['group' => $group]);
        }

        return $this->render('admin/settings/index.html.twig', [
            'groups'  => array_keys(self::GROUPS),
            'group'   => $group,
            'fields'  => $fields,
            'current' => $current,
        ]);
    }
}
