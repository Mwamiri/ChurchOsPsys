<?php

namespace App\Controller\Insights;

use App\Entity\User;
use App\Repository\AuditLogRepository;
use App\Service\InsightService;
use App\Service\SettingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/', name: 'insights_')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly InsightService $insight,
        private readonly SettingService $settings,
        private readonly AuditLogRepository $auditLogs,
    ) {}

    #[Route('', name: 'home')]
    public function home(): Response
    {
        return $this->redirectToRoute('insights_dashboard');
    }

    #[Route('dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $stats = $this->insight->dashboardStats($user);
        $trend = $this->insight->signInTrend(14);

        return $this->render('insights/dashboard/index.html.twig', [
            'stats'      => $stats,
            'sign_trend' => $trend,
            'church'     => $this->settings->getChurchName(),
        ]);
    }

    #[Route('dashboard/keep-alive', name: 'keepalive', methods: ['POST'])]
    public function keepAlive(): JsonResponse
    {
        return new JsonResponse(['ok' => true]);
    }

    #[Route('dashboard/alerts/mark-read', name: 'alerts_mark_read', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function markAlertsRead(Request $request): Response
    {
        if ($this->isCsrfTokenValid('mark_alerts', $request->request->get('_token'))) {
            $conn = $this->insight->em ?? null;
        }
        $this->addFlash('success', 'All alerts marked as read.');
        return $this->redirectToRoute('insights_dashboard');
    }
}
