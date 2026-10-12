<?php

namespace App\Controller\Insights;

use App\Repository\CentreRepository;
use App\Service\AuditService;
use App\Service\InsightService;
use App\Service\SettingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reports', name: 'insights_reports_')]
#[IsGranted('ROLE_USER')]
class ReportsController extends AbstractController
{
    public function __construct(
        private readonly InsightService $insight,
        private readonly SettingService $settings,
        private readonly CentreRepository $centres,
        private readonly AuditService $audit,
    ) {}

    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render('insights/reports/index.html.twig', [
            'church'  => $this->settings->getChurchName(),
            'centres' => $this->centres->flatOptions(),
            'years'   => range((int)date('Y'), (int)date('Y') - 9),
        ]);
    }

    #[Route('/ministry', name: 'ministry')]
    public function ministry(Request $request): Response
    {
        $year    = (int)($request->query->get('year') ?: date('Y'));
        $centreId = (int)$request->query->get('centre');
        $centreIds = $centreId ? [$centreId] : [];

        $data = $this->insight->ministryReport($year, $centreIds);
        $this->audit->log('report', 'ministry', $centreId ?: null, ['year' => $year], $request->getClientIp());

        return $this->render('insights/reports/ministry.html.twig', [
            'data'     => $data,
            'year'     => $year,
            'church'   => $this->settings->getChurchName(),
            'centres'  => $this->centres->flatOptions(),
            'centre_id' => $centreId,
            'years'    => range((int)date('Y'), (int)date('Y') - 9),
        ]);
    }

    #[Route('/annual', name: 'annual')]
    public function annual(Request $request): Response
    {
        $year = (int)($request->query->get('year') ?: date('Y'));
        $data = $this->insight->annualSummary($year);
        $this->audit->log('report', 'annual', null, ['year' => $year], $request->getClientIp());

        return $this->render('insights/reports/annual.html.twig', [
            'data'   => $data,
            'year'   => $year,
            'church' => $this->settings->getChurchName(),
            'years'  => range((int)date('Y'), (int)date('Y') - 9),
        ]);
    }

    #[Route('/sign-ins', name: 'signins')]
    #[IsGranted('ROLE_ADMIN')]
    public function signIns(Request $request): Response
    {
        $days  = max(7, min(365, (int)($request->query->get('days') ?: 30)));
        $trend = $this->insight->signInTrend($days);
        $this->audit->log('report', 'sign_ins', null, ['days' => $days], $request->getClientIp());

        return $this->render('insights/reports/sign_ins.html.twig', [
            'trend' => $trend,
            'days'  => $days,
        ]);
    }
}
