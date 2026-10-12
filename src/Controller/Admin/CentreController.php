<?php

namespace App\Controller\Admin;

use App\Entity\Centre;
use App\Form\CentreFormType;
use App\Repository\CentreRepository;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/centres', name: 'admin_centres_')]
#[IsGranted('ROLE_ADMIN')]
class CentreController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CentreRepository $centres,
        private readonly AuditService $audit,
    ) {}

    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render('admin/centres/index.html.twig', [
            'tree' => $this->centres->findTree(),
            'all'  => $this->centres->findAllActive(),
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $centre = new Centre();
        $form   = $this->createForm(CentreFormType::class, $centre, ['centres' => $this->centres->flatOptions()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($centre);
            $this->em->flush();
            $this->audit->log('create', 'centre', $centre->getId(), ['name' => $centre->getName()], $request->getClientIp());
            $this->addFlash('success', 'Centre created.');
            return $this->redirectToRoute('admin_centres_index');
        }

        return $this->render('admin/centres/form.html.twig', ['form' => $form, 'centre' => $centre, 'is_new' => true]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(int $id, Request $request): Response
    {
        $centre = $this->centres->find($id);
        if (!$centre) { throw $this->createNotFoundException(); }

        $form = $this->createForm(CentreFormType::class, $centre, ['centres' => $this->centres->flatOptions()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->audit->log('update', 'centre', $id, ['name' => $centre->getName()], $request->getClientIp());
            $this->addFlash('success', 'Centre updated.');
            return $this->redirectToRoute('admin_centres_index');
        }

        return $this->render('admin/centres/form.html.twig', ['form' => $form, 'centre' => $centre, 'is_new' => false]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $centre = $this->centres->find($id);
        if (!$centre) { throw $this->createNotFoundException(); }

        if ($this->isCsrfTokenValid('delete_centre_' . $id, $request->request->get('_token'))) {
            $name = $centre->getName();
            $this->em->remove($centre);
            $this->em->flush();
            $this->audit->log('delete', 'centre', $id, $name, $request->getClientIp());
            $this->addFlash('success', 'Centre deleted.');
        }

        return $this->redirectToRoute('admin_centres_index');
    }

    #[Route('/{id}/toggle', name: 'toggle', methods: ['POST'])]
    public function toggle(int $id, Request $request): Response
    {
        $centre = $this->centres->find($id);
        if (!$centre) { throw $this->createNotFoundException(); }

        if ($this->isCsrfTokenValid('toggle_centre_' . $id, $request->request->get('_token'))) {
            $centre->setIsActive(!$centre->isActive());
            $this->em->flush();
            $this->audit->log('update', 'centre', $id, ['active' => $centre->isActive()], $request->getClientIp());
        }

        return $this->redirectToRoute('admin_centres_index');
    }
}
