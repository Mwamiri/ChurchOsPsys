<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_VIEWER')]
class SearchController extends AbstractController
{
    public function __construct(private Connection $db) {}

    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $results = [];

        if (mb_strlen($q) >= 2) {
            $like = '%' . $q . '%';
            $centre = $this->centreScope();

            // Members
            $sql = 'SELECT id, full_name, phone, email FROM members WHERE (full_name LIKE ? OR phone LIKE ? OR email LIKE ?)';
            $params = [$like, $like, $like];
            if ($centre) { $sql .= ' AND centre_id = ?'; $params[] = $centre; }
            $sql .= ' LIMIT 10';
            foreach ($this->db->fetchAllAssociative($sql, $params) as $r) {
                $results[] = ['group' => 'Members', 'icon' => 'person', 'title' => $r['full_name'], 'sub' => $r['phone'] ?? $r['email'] ?? '', 'url' => '/ministry/members/' . $r['id']];
            }

            // Leaders
            $sql = 'SELECT id, full_name, phone FROM leaders WHERE full_name LIKE ?';
            $params = [$like];
            if ($centre) { $sql .= ' AND centre_id = ?'; $params[] = $centre; }
            $sql .= ' LIMIT 5';
            foreach ($this->db->fetchAllAssociative($sql, $params) as $r) {
                $results[] = ['group' => 'Leaders', 'icon' => 'star', 'title' => $r['full_name'], 'sub' => $r['phone'] ?? '', 'url' => '/ministry/leaders/' . $r['id']];
            }

            // Visitors
            $sql = 'SELECT id, full_name, phone FROM visitors WHERE full_name LIKE ?';
            $params = [$like];
            if ($centre) { $sql .= ' AND centre_id = ?'; $params[] = $centre; }
            $sql .= ' LIMIT 5';
            foreach ($this->db->fetchAllAssociative($sql, $params) as $r) {
                $results[] = ['group' => 'Visitors', 'icon' => 'person-plus', 'title' => $r['full_name'], 'sub' => $r['phone'] ?? '', 'url' => '/ministry/visitors/' . $r['id']];
            }

            // Groups
            $sql = 'SELECT id, group_name FROM `groups` WHERE group_name LIKE ?';
            $params = [$like];
            if ($centre) { $sql .= ' AND centre_id = ?'; $params[] = $centre; }
            $sql .= ' LIMIT 5';
            foreach ($this->db->fetchAllAssociative($sql, $params) as $r) {
                $results[] = ['group' => 'Groups', 'icon' => 'people', 'title' => $r['group_name'], 'sub' => '', 'url' => '/people/groups/' . $r['id']];
            }

            // Announcements
            $sql = 'SELECT id, title FROM announcements WHERE title LIKE ? LIMIT 5';
            foreach ($this->db->fetchAllAssociative($sql, [$like]) as $r) {
                $results[] = ['group' => 'Announcements', 'icon' => 'megaphone', 'title' => $r['title'], 'sub' => '', 'url' => '/announcements'];
            }
        }

        return $this->render('search/index.html.twig', ['q' => $q, 'results' => $results]);
    }

    #[Route('/search/suggest', name: 'app_search_suggest', methods: ['GET'])]
    public function suggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        if (mb_strlen($q) < 2) {
            return new JsonResponse([]);
        }

        $like = '%' . $q . '%';
        $items = [];
        $centre = $this->centreScope();

        $sql = 'SELECT id, full_name, phone FROM members WHERE full_name LIKE ?';
        $params = [$like];
        if ($centre) { $sql .= ' AND centre_id = ?'; $params[] = $centre; }
        $sql .= ' LIMIT 6';
        foreach ($this->db->fetchAllAssociative($sql, $params) as $r) {
            $items[] = ['icon' => 'person', 'title' => $r['full_name'], 'sub' => $r['phone'] ?? '', 'url' => '/ministry/members/' . $r['id']];
        }

        return new JsonResponse($items);
    }

    private function centreScope(): ?int
    {
        $user = $this->getUser();
        if (!$user) return null;
        if ($this->isGranted('ROLE_ADMIN')) return null;
        return $user->getCentreId();
    }
}
