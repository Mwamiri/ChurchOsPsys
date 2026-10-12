<?php

namespace App\Controller\Ministry;

use App\Service\AuditService;
use App\Service\MinistryConfig;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_VIEWER')]
#[Route('/ministry')]
class RecordsController extends AbstractController
{
    public function __construct(
        private Connection $db,
        private AuditService $audit,
    ) {}

    #[Route('/{area}', name: 'ministry_list', requirements: ['area' => '[a-z_]+'])]
    public function list(string $area, Request $req): Response
    {
        $this->requireValidArea($area);
        $cfg = MinistryConfig::area($area);
        $fields = MinistryConfig::fields($area);
        $table = $cfg['table'];

        $page  = max(1, (int) $req->query->get('page', 1));
        $limit = 25;
        $q     = trim((string) $req->query->get('q', ''));

        $qb = $this->db->createQueryBuilder()
            ->select('*')
            ->from("`$table`")
            ->where('is_deleted = 0')
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit)
            ->orderBy('id', 'DESC');

        if ($q !== '') {
            $primaryCol = MinistryConfig::primaryLabel($area);
            $qb->andWhere("$primaryCol LIKE :q")->setParameter('q', "%$q%");
        }

        $centreId = $this->getCentreScope();
        if ($centreId) {
            $qb->andWhere('centre_id = :cid')->setParameter('cid', $centreId);
        }

        $rows  = $qb->executeQuery()->fetchAllAssociative();
        $total = (int) $this->db->createQueryBuilder()
            ->select('COUNT(*)')
            ->from("`$table`")
            ->where('is_deleted = 0')
            ->executeQuery()->fetchOne();

        return $this->render('ministry/list.html.twig', [
            'area'   => $area,
            'cfg'    => $cfg,
            'fields' => $fields,
            'rows'   => $rows,
            'total'  => $total,
            'page'   => $page,
            'pages'  => (int) ceil($total / $limit),
            'q'      => $q,
        ]);
    }

    #[Route('/{area}/new', name: 'ministry_new', requirements: ['area' => '[a-z_]+'])]
    #[IsGranted('ROLE_CLERK')]
    public function new(string $area, Request $req): Response
    {
        $this->requireValidArea($area);
        $cfg    = MinistryConfig::area($area);
        $fields = MinistryConfig::fields($area);

        if ($req->isMethod('POST')) {
            $data = $this->extractData($fields, $req);
            $data['centre_id']    = $this->getCentreScope();
            $data['created_by_id'] = $this->getUser()?->getId();
            $data['created_at']   = (new \DateTime())->format('Y-m-d H:i:s');
            $data['updated_at']   = $data['created_at'];
            $data['is_deleted']   = 0;

            $this->db->insert('`' . $cfg['table'] . '`', $data);
            $newId = $this->db->lastInsertId();

            $this->audit->log('create', $area, (int) $newId, ['name' => $this->getRecordLabel($data, $fields)]);
            $this->addFlash('success', 'Record added successfully.');

            return $this->redirectToRoute('ministry_list', ['area' => $area]);
        }

        return $this->render('ministry/form.html.twig', [
            'area'   => $area,
            'cfg'    => $cfg,
            'fields' => $fields,
            'record' => [],
            'isNew'  => true,
        ]);
    }

    #[Route('/{area}/{id}/edit', name: 'ministry_edit', requirements: ['area' => '[a-z_]+', 'id' => '\d+'])]
    #[IsGranted('ROLE_CLERK')]
    public function edit(string $area, int $id, Request $req): Response
    {
        $this->requireValidArea($area);
        $cfg    = MinistryConfig::area($area);
        $fields = MinistryConfig::fields($area);
        $record = $this->fetchRecord($cfg['table'], $id);

        if ($req->isMethod('POST')) {
            $data = $this->extractData($fields, $req);
            $data['updated_at'] = (new \DateTime())->format('Y-m-d H:i:s');

            $this->db->update('`' . $cfg['table'] . '`', $data, ['id' => $id]);
            $this->audit->log('update', $area, $id, ['name' => $this->getRecordLabel($data, $fields)]);
            $this->addFlash('success', 'Record updated.');

            return $this->redirectToRoute('ministry_list', ['area' => $area]);
        }

        return $this->render('ministry/form.html.twig', [
            'area'   => $area,
            'cfg'    => $cfg,
            'fields' => $fields,
            'record' => $record,
            'isNew'  => false,
        ]);
    }

    #[Route('/{area}/{id}/view', name: 'ministry_view', requirements: ['area' => '[a-z_]+', 'id' => '\d+'])]
    public function view(string $area, int $id): Response
    {
        $this->requireValidArea($area);
        $cfg    = MinistryConfig::area($area);
        $fields = MinistryConfig::fields($area);
        $record = $this->fetchRecord($cfg['table'], $id);

        return $this->render('ministry/view.html.twig', [
            'area'   => $area,
            'cfg'    => $cfg,
            'fields' => $fields,
            'record' => $record,
        ]);
    }

    #[Route('/{area}/{id}/delete', name: 'ministry_delete', methods: ['POST'], requirements: ['area' => '[a-z_]+', 'id' => '\d+'])]
    #[IsGranted('ROLE_CLERK')]
    public function delete(string $area, int $id, Request $req): Response
    {
        $this->requireValidArea($area);
        $this->denyAccessUnlessGranted('ROLE_CLERK');

        if (!$this->isCsrfTokenValid('del_' . $area . $id, (string) $req->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('ministry_list', ['area' => $area]);
        }

        $cfg = MinistryConfig::area($area);
        $this->db->update('`' . $cfg['table'] . '`', [
            'is_deleted' => 1,
            'deleted_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ], ['id' => $id]);
        $this->audit->log('delete', $area, $id, []);
        $this->addFlash('success', 'Record deleted.');

        return $this->redirectToRoute('ministry_list', ['area' => $area]);
    }

    #[Route('/{area}/import', name: 'ministry_import', methods: ['GET', 'POST'], requirements: ['area' => '[a-z_]+'])]
    #[IsGranted('ROLE_CLERK')]
    public function import(string $area, Request $req): Response
    {
        $this->requireValidArea($area);
        $cfg    = MinistryConfig::area($area);
        $fields = MinistryConfig::fields($area);
        $errors = [];
        $imported = 0;

        if ($req->isMethod('POST')) {
            $file = $req->files->get('csv_file');
            if ($file) {
                $handle = fopen($file->getPathname(), 'r');
                $header = fgetcsv($handle);
                $header = array_map('trim', $header ?? []);
                $colMap = [];
                foreach ($fields as $f) {
                    $idx = array_search($f['label'], $header);
                    if ($idx !== false) {
                        $colMap[$f['col']] = $idx;
                    }
                }
                while (($row = fgetcsv($handle)) !== false) {
                    $data = [];
                    foreach ($colMap as $col => $idx) {
                        $data[$col] = $row[$idx] ?? null;
                    }
                    if (empty($data)) continue;
                    $data['centre_id']     = $this->getCentreScope();
                    $data['created_by_id'] = $this->getUser()?->getId();
                    $data['created_at']    = (new \DateTime())->format('Y-m-d H:i:s');
                    $data['updated_at']    = $data['created_at'];
                    $data['is_deleted']    = 0;
                    try {
                        $this->db->insert('`' . $cfg['table'] . '`', $data);
                        $imported++;
                    } catch (\Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                }
                fclose($handle);
                $this->addFlash('success', "Imported $imported records.");
            }
        }

        return $this->render('ministry/import.html.twig', [
            'area'   => $area,
            'cfg'    => $cfg,
            'fields' => $fields,
            'errors' => $errors,
        ]);
    }

    // ── helpers ──────────────────────────────────────────────────

    private function requireValidArea(string $area): void
    {
        if (!array_key_exists($area, MinistryConfig::AREAS)) {
            throw $this->createNotFoundException("Unknown ministry area: $area");
        }
    }

    private function fetchRecord(string $table, int $id): array
    {
        $row = $this->db->fetchAssociative(
            "SELECT * FROM `$table` WHERE id = ? AND is_deleted = 0",
            [$id]
        );
        if (!$row) {
            throw $this->createNotFoundException('Record not found.');
        }
        return $row;
    }

    private function extractData(array $fields, Request $req): array
    {
        $data = [];
        foreach ($fields as $f) {
            $val = $req->request->get($f['col']);
            if ($f['type'] === 'checkbox') {
                $data[$f['col']] = $val ? 1 : 0;
            } elseif ($val === '' || $val === null) {
                $data[$f['col']] = null;
            } else {
                $data[$f['col']] = $val;
            }
        }
        return $data;
    }

    private function getRecordLabel(array $data, array $fields): string
    {
        foreach ($fields as $f) {
            if (!empty($f['required']) && !empty($data[$f['col']])) {
                return (string) $data[$f['col']];
            }
        }
        return '';
    }

    private function getCentreScope(): ?int
    {
        $user = $this->getUser();
        if (!$user || $user->isSysAdmin() || $user->isAdmin()) {
            return null;
        }
        return $user->getCentre()?->getId();
    }
}
