<?php

namespace App\Controller\Calendar;

use App\Service\AuditService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_VIEWER')]
#[Route('/calendar')]
class CalendarController extends AbstractController
{
    public function __construct(
        private Connection $db,
        private AuditService $audit,
    ) {}

    #[Route('', name: 'calendar_home')]
    public function home(): Response
    {
        return $this->render('calendar/home.html.twig');
    }

    #[Route('/events.json', name: 'calendar_events_json')]
    public function eventsJson(Request $req): JsonResponse
    {
        $start = $req->query->get('start');
        $end   = $req->query->get('end');
        $cid   = $this->centreScope();

        $sql = 'SELECT * FROM calendar_events WHERE start_date BETWEEN ? AND ?';
        $params = [$start, $end];
        if ($cid) { $sql .= ' AND (centre_id=? OR centre_id IS NULL)'; $params[] = $cid; }

        $rows   = $this->db->fetchAllAssociative($sql, $params);
        $events = [];
        foreach ($rows as $r) {
            $events[] = [
                'id'          => $r['id'],
                'title'       => $r['title'],
                'start'       => $r['start_date'] . ($r['start_time'] ? 'T' . $r['start_time'] : ''),
                'end'         => ($r['end_date'] ?? $r['start_date']) . ($r['end_time'] ? 'T' . $r['end_time'] : ''),
                'allDay'      => (bool) $r['all_day'],
                'color'       => $r['color'] ?? '#3b82f6',
                'description' => $r['description'] ?? '',
                'location'    => $r['location'] ?? '',
                'url'         => $this->generateUrl('calendar_event_view', ['id' => $r['id']]),
            ];
        }
        return new JsonResponse($events);
    }

    #[Route('/event/{id}', name: 'calendar_event_view', requirements: ['id' => '\d+'])]
    public function eventView(int $id): Response
    {
        $event = $this->fetchEvent($id);
        return $this->render('calendar/event_view.html.twig', ['event' => $event]);
    }

    #[Route('/event/new', name: 'calendar_event_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLERK')]
    public function eventNew(Request $req): Response
    {
        if ($req->isMethod('POST')) {
            $this->db->insert('calendar_events', [
                'centre_id'   => $req->request->get('centre_id') ?: $this->centreScope(),
                'title'       => $req->request->get('title'),
                'description' => $req->request->get('description'),
                'location'    => $req->request->get('location'),
                'start_date'  => $req->request->get('start_date'),
                'end_date'    => $req->request->get('end_date') ?: null,
                'start_time'  => $req->request->get('start_time') ?: null,
                'end_time'    => $req->request->get('end_time') ?: null,
                'all_day'     => $req->request->get('all_day') ? 1 : 0,
                'recurrence'  => $req->request->get('recurrence') ?: null,
                'category'    => $req->request->get('category'),
                'color'       => $req->request->get('color', '#3b82f6'),
                'is_public'   => $req->request->get('is_public') ? 1 : 1,
                'created_by_id' => $this->getUser()?->getId(),
                'created_at'  => (new \DateTime())->format('Y-m-d H:i:s'),
                'updated_at'  => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
            $newId = $this->db->lastInsertId();
            $this->audit->log('create', 'calendar_event', (int) $newId, ['title' => $req->request->get('title')]);
            $this->addFlash('success', 'Event added.');
            return $this->redirectToRoute('calendar_home');
        }

        return $this->render('calendar/event_form.html.twig', ['event' => [], 'isNew' => true]);
    }

    #[Route('/event/{id}/edit', name: 'calendar_event_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_CLERK')]
    public function eventEdit(int $id, Request $req): Response
    {
        $event = $this->fetchEvent($id);
        if ($req->isMethod('POST')) {
            $this->db->update('calendar_events', [
                'title'       => $req->request->get('title'),
                'description' => $req->request->get('description'),
                'location'    => $req->request->get('location'),
                'start_date'  => $req->request->get('start_date'),
                'end_date'    => $req->request->get('end_date') ?: null,
                'start_time'  => $req->request->get('start_time') ?: null,
                'end_time'    => $req->request->get('end_time') ?: null,
                'all_day'     => $req->request->get('all_day') ? 1 : 0,
                'category'    => $req->request->get('category'),
                'color'       => $req->request->get('color', '#3b82f6'),
                'updated_at'  => (new \DateTime())->format('Y-m-d H:i:s'),
            ], ['id' => $id]);
            $this->audit->log('update', 'calendar_event', $id, []);
            $this->addFlash('success', 'Event updated.');
            return $this->redirectToRoute('calendar_home');
        }
        return $this->render('calendar/event_form.html.twig', ['event' => $event, 'isNew' => false]);
    }

    #[Route('/event/{id}/delete', name: 'calendar_event_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_CLERK')]
    public function eventDelete(int $id, Request $req): Response
    {
        if (!$this->isCsrfTokenValid('del_cal_' . $id, (string) $req->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
        } else {
            $this->db->delete('calendar_events', ['id' => $id]);
            $this->audit->log('delete', 'calendar_event', $id, []);
            $this->addFlash('success', 'Event deleted.');
        }
        return $this->redirectToRoute('calendar_home');
    }

    private function fetchEvent(int $id): array
    {
        $e = $this->db->fetchAssociative('SELECT * FROM calendar_events WHERE id=?', [$id]);
        if (!$e) throw $this->createNotFoundException('Event not found.');
        return $e;
    }

    private function centreScope(): ?int
    {
        $user = $this->getUser();
        if (!$user || $user->isSysAdmin() || $user->isAdmin()) return null;
        return $user->getCentre()?->getId();
    }
}
