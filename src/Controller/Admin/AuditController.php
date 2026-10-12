<?php

namespace App\Controller\Admin;

use App\Repository\AuditLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/audit', name: 'admin_audit_')]
#[IsGranted('ROLE_ADMIN')]
class AuditController extends AbstractController
{
    public function __construct(
        private readonly AuditLogRepository $repo,
    ) {}

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $filters = [
            'action'  => $request->query->get('action'),
            'entity'  => $request->query->get('entity'),
            'user_id' => $request->query->get('user_id') ? (int)$request->query->get('user_id') : null,
            'from'    => $request->query->get('from') ? new \DateTimeImmutable($request->query->get('from')) : null,
            'to'      => $request->query->get('to') ? new \DateTimeImmutable($request->query->get('to') . ' 23:59:59') : null,
        ];
        $page   = max(1, (int)$request->query->get('page', 1));
        $result = $this->repo->search(array_filter($filters), $page);

        return $this->render('admin/audit/index.html.twig', [
            'result'  => $result,
            'filters' => $filters,
            'actions' => ['create', 'update', 'delete', 'login', 'logout', 'report', 'export', 'import'],
        ]);
    }

    #[Route('/verify', name: 'verify')]
    #[IsGranted('ROLE_SYSADMIN')]
    public function verify(Request $request): Response
    {
        $page  = max(1, (int)$request->query->get('page', 1));
        $limit = 200;
        $result = $this->repo->search([], $page, $limit);

        $broken = [];
        $prevHash = null;
        foreach ($result['rows'] as $log) {
            if ($prevHash !== null && $log->getPrevHash() !== $prevHash) {
                $broken[] = $log->getId();
            }
            $prevHash = $log->getHash();
        }

        return $this->render('admin/audit/verify.html.twig', [
            'result' => $result,
            'broken' => $broken,
        ]);
    }
}
