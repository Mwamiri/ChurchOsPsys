<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/profile')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private AuditService $audit,
    ) {}

    #[Route('', name: 'profile_index')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig', ['user' => $this->getUser()]);
    }

    #[Route('/edit', name: 'profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $req): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($req->isMethod('POST')) {
            $user->setName((string) $req->request->get('name', $user->getName()));
            $user->setEmail($req->request->get('email') ?: null);
            $user->setPhone($req->request->get('phone') ?: null);
            $user->setLocale($req->request->get('locale') ?: null);

            $this->em->flush();
            $this->audit->log('profile_update', 'user', $user->getId() ?? 0, []);
            $this->addFlash('success', 'Profile updated.');
            return $this->redirectToRoute('profile_index');
        }
        return $this->render('profile/edit.html.twig', ['user' => $user]);
    }

    #[Route('/password', name: 'profile_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $req): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($req->isMethod('POST')) {
            $current = (string) $req->request->get('current_password', '');
            $newPw   = (string) $req->request->get('new_password', '');
            $confirm = (string) $req->request->get('confirm_password', '');

            if (!$this->hasher->isPasswordValid($user, $current)) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->render('profile/password.html.twig');
            }
            if (strlen($newPw) < 8) {
                $this->addFlash('error', 'New password must be at least 8 characters.');
                return $this->render('profile/password.html.twig');
            }
            if ($newPw !== $confirm) {
                $this->addFlash('error', 'Passwords do not match.');
                return $this->render('profile/password.html.twig');
            }

            $user->setPasswordHash($this->hasher->hashPassword($user, $newPw));
            $this->em->flush();
            $this->audit->log('password_change', 'user', $user->getId() ?? 0, []);
            $this->addFlash('success', 'Password changed successfully.');
            return $this->redirectToRoute('profile_index');
        }
        return $this->render('profile/password.html.twig');
    }

    #[Route('/2fa', name: 'profile_2fa', methods: ['GET', 'POST'])]
    public function twoFactor(Request $req): Response
    {
        /** @var User $user */
        $user   = $this->getUser();
        $action = $req->request->get('action');

        if ($req->isMethod('POST') && $action === 'enable') {
            $secret = $this->generateTotpSecret();
            $user->setTotpSecret($secret);
            $this->em->flush();
            $this->audit->log('2fa_enabled', 'user', $user->getId() ?? 0, []);
            $this->addFlash('success', '2FA has been enabled. Save your secret key safely.');
            return $this->render('profile/2fa.html.twig', ['user' => $user, 'newSecret' => $secret]);
        }

        if ($req->isMethod('POST') && $action === 'disable') {
            $user->setTotpSecret(null);
            $this->em->flush();
            $this->audit->log('2fa_disabled', 'user', $user->getId() ?? 0, []);
            $this->addFlash('success', '2FA has been disabled.');
        }

        return $this->render('profile/2fa.html.twig', ['user' => $user, 'newSecret' => null]);
    }

    #[Route('/sessions', name: 'profile_sessions')]
    public function sessions(): Response
    {
        return $this->render('profile/sessions.html.twig', ['user' => $this->getUser()]);
    }

    private function generateTotpSecret(int $len = 32): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $out   = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= $chars[random_int(0, 31)];
        }
        return $out;
    }
}
