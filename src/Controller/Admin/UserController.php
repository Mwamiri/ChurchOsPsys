<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserFormType;
use App\Repository\CentreRepository;
use App\Repository\UserRepository;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'admin_users_')]
#[IsGranted('ROLE_CENTRE_ADMIN')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly CentreRepository $centres,
        private readonly AuditService $audit,
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $q       = trim((string) $request->query->get('q'));
        $role    = $request->query->get('role');
        $cid     = (int) $request->query->get('centre');
        $page    = max(1, (int) $request->query->get('page', 1));

        /** @var User $me */
        $me = $this->getUser();
        if (!$me->isAdmin()) {
            $cid = $me->getCentre()?->getId();
        }

        $result = $this->users->search($q, $cid ?: null, $role ?: null, $page);

        return $this->render('admin/users/index.html.twig', [
            'result'   => $result,
            'q'        => $q,
            'role'     => $role,
            'centre'   => $cid,
            'centres'  => $this->centres->flatOptions(),
            'roles'    => User::ROLES,
        ]);
    }

    #[Route('/new', name: 'new')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserFormType::class, $user, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            if ($plain) {
                $user->setPasswordHash($this->hasher->hashPassword($user, $plain));
            }
            $this->em->persist($user);
            $this->em->flush();
            $this->audit->log('create', 'user', $user->getId(), ['username' => $user->getUsername(), 'role' => $user->getRole()], $request->getClientIp());
            $this->addFlash('success', 'User created successfully.');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/users/form.html.twig', ['form' => $form, 'user' => $user, 'is_new' => true]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(int $id, Request $request): Response
    {
        $user = $this->users->find($id);
        if (!$user) { throw $this->createNotFoundException(); }
        $this->denyUnlessCanEdit($user);

        $form = $this->createForm(UserFormType::class, $user, ['is_new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            if ($plain) {
                $user->setPasswordHash($this->hasher->hashPassword($user, $plain));
            }
            $this->em->flush();
            $this->audit->log('update', 'user', $user->getId(), ['username' => $user->getUsername(), 'role' => $user->getRole(), 'password_changed' => (bool)$plain], $request->getClientIp());
            $this->addFlash('success', 'User updated.');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/users/form.html.twig', ['form' => $form, 'user' => $user, 'is_new' => false]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, Request $request): Response
    {
        $user = $this->users->find($id);
        if (!$user) { throw $this->createNotFoundException(); }
        $this->denyUnlessCanEdit($user);

        /** @var User $me */
        $me = $this->getUser();
        if ($user->getId() === $me->getId()) {
            $this->addFlash('error', 'You cannot delete your own account.');
            return $this->redirectToRoute('admin_users_index');
        }
        if ($user->isSysAdmin() && $user->isActive() && $this->users->activeSysadminCount() <= 1) {
            $this->addFlash('error', 'At least one active system administrator must remain.');
            return $this->redirectToRoute('admin_users_index');
        }

        if ($this->isCsrfTokenValid('delete_user_' . $id, $request->request->get('_token'))) {
            $username = $user->getUsername();
            $this->em->remove($user);
            $this->em->flush();
            $this->audit->log('delete', 'user', $id, $username, $request->getClientIp());
            $this->addFlash('success', 'User deleted.');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/toggle', name: 'toggle', methods: ['POST'])]
    public function toggle(int $id, Request $request): Response
    {
        $user = $this->users->find($id);
        if (!$user) { throw $this->createNotFoundException(); }
        $this->denyUnlessCanEdit($user);

        /** @var User $me */
        $me = $this->getUser();
        if ($user->getId() === $me->getId()) {
            $this->addFlash('error', 'You cannot deactivate yourself.');
            return $this->redirectToRoute('admin_users_index');
        }

        if ($this->isCsrfTokenValid('toggle_user_' . $id, $request->request->get('_token'))) {
            $user->setIsActive(!$user->isActive());
            $this->em->flush();
            $this->audit->log('update', 'user', $id, ['active' => $user->isActive()], $request->getClientIp());
            $this->addFlash('success', $user->isActive() ? 'User activated.' : 'User deactivated.');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    private function denyUnlessCanEdit(User $target): void
    {
        /** @var User $me */
        $me = $this->getUser();
        if ($target->isSysAdmin() && !$me->isSysAdmin()) {
            throw $this->createAccessDeniedException();
        }
        if ($target->isAdmin() && !$me->isAdmin()) {
            throw $this->createAccessDeniedException();
        }
        if (!$me->isAdmin() && $target->getCentre()?->getId() !== $me->getCentre()?->getId()) {
            throw $this->createAccessDeniedException();
        }
    }
}
