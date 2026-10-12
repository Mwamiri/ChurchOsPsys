<?php

namespace App\Controller;

use App\Service\AuditService;
use App\Service\SettingService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CLERK')]
#[Route('/sms')]
class SmsController extends AbstractController
{
    public function __construct(
        private Connection $db,
        private AuditService $audit,
        private SettingService $settings,
    ) {}

    #[Route('', name: 'sms_index')]
    public function index(Request $req): Response
    {
        $cid   = $this->centreScope();
        $page  = max(1, (int) $req->query->get('page', 1));
        $limit = 25;
        $where = $cid ? 'WHERE centre_id=?' : 'WHERE 1=1';
        $params= $cid ? [$cid] : [];

        $rows  = $this->db->fetchAllAssociative(
            "SELECT * FROM sms_log $where ORDER BY created_at DESC LIMIT $limit OFFSET " . (($page - 1) * $limit),
            $params
        );
        $total = (int) $this->db->fetchOne("SELECT COUNT(*) FROM sms_log $where", $params);

        return $this->render('sms/index.html.twig', [
            'rows'  => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => (int) ceil($total / $limit),
        ]);
    }

    #[Route('/compose', name: 'sms_compose', methods: ['GET', 'POST'])]
    public function compose(Request $req): Response
    {
        if ($req->isMethod('POST')) {
            $recipients = $this->parseRecipients((string) $req->request->get('recipients', ''));
            $message    = trim((string) $req->request->get('message', ''));

            if (empty($recipients)) {
                $this->addFlash('error', 'No valid recipients.');
                return $this->render('sms/compose.html.twig', ['req' => $req->request->all()]);
            }
            if (strlen($message) < 2) {
                $this->addFlash('error', 'Message is too short.');
                return $this->render('sms/compose.html.twig', ['req' => $req->request->all()]);
            }

            $sent = 0;
            foreach ($recipients as $entry) {
                $result = $this->sendViaSmsProvider($entry['phone'], $message);
                $this->db->insert('sms_log', [
                    'centre_id'      => $this->centreScope(),
                    'recipient_name' => $entry['name'] ?? null,
                    'phone'          => $entry['phone'],
                    'message'        => $message,
                    'status'         => $result['status'],
                    'provider_ref'   => $result['ref'] ?? null,
                    'error'          => $result['error'] ?? null,
                    'cost'           => $result['cost'] ?? null,
                    'sent_at'        => $result['status'] === 'sent' ? (new \DateTime())->format('Y-m-d H:i:s') : null,
                    'created_by_id'  => $this->getUser()?->getId(),
                    'created_at'     => (new \DateTime())->format('Y-m-d H:i:s'),
                ]);
                if ($result['status'] === 'sent') $sent++;
            }

            $this->audit->log('sms_send', 'sms', 0, ['count' => $sent, 'total' => count($recipients)]);
            $this->addFlash('success', "Sent $sent / " . count($recipients) . " messages.");
            return $this->redirectToRoute('sms_index');
        }

        return $this->render('sms/compose.html.twig', ['req' => []]);
    }

    #[Route('/groups', name: 'sms_groups')]
    public function groups(): Response
    {
        $cid    = $this->centreScope();
        $where  = $cid ? 'WHERE centre_id=?' : 'WHERE 1=1';
        $groups = $this->db->fetchAllAssociative("SELECT * FROM groups $where ORDER BY group_name", $cid ? [$cid] : []);
        return $this->render('sms/groups.html.twig', ['groups' => $groups]);
    }

    /**
     * Send via Africa's Talking (Kenya) or log as pending if not configured.
     */
    private function sendViaSmsProvider(string $phone, string $message): array
    {
        $apiKey   = $this->settings->get('sms_at_api_key', '');
        $username = $this->settings->get('sms_at_username', 'sandbox');
        $from     = $this->settings->get('sms_sender_id', '');

        if (!$apiKey || $apiKey === 'sandbox') {
            return ['status' => 'queued', 'ref' => null, 'error' => 'SMS provider not configured'];
        }

        try {
            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "apiKey: $apiKey\r\nContent-Type: application/x-www-form-urlencoded\r\nAccept: application/json",
                    'content' => http_build_query([
                        'username' => $username,
                        'to'       => $phone,
                        'message'  => $message,
                        'from'     => $from ?: null,
                    ]),
                    'timeout' => 15,
                ],
            ]);
            $resp = file_get_contents('https://api.africastalking.com/version1/messaging', false, $ctx);
            $data = $resp ? json_decode($resp, true) : null;
            $entry = $data['SMSMessageData']['Recipients'][0] ?? null;
            if ($entry && $entry['statusCode'] === 101) {
                return ['status' => 'sent', 'ref' => $entry['messageId'], 'cost' => (float) ltrim($entry['cost'] ?? '0', 'KES ')];
            }
            return ['status' => 'failed', 'error' => $entry['status'] ?? 'Unknown error'];
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    private function parseRecipients(string $raw): array
    {
        $out = [];
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if (!$line) continue;
            // Support "Name,+254700000000" or plain number
            if (str_contains($line, ',')) {
                [$name, $phone] = explode(',', $line, 2);
                $out[] = ['name' => trim($name), 'phone' => trim($phone)];
            } else {
                $out[] = ['name' => null, 'phone' => $line];
            }
        }
        return array_filter($out, fn($r) => preg_match('/^\+?\d{9,15}$/', $r['phone']));
    }

    private function centreScope(): ?int
    {
        $user = $this->getUser();
        if (!$user || $user->isSysAdmin() || $user->isAdmin()) return null;
        return $user->getCentre()?->getId();
    }
}
