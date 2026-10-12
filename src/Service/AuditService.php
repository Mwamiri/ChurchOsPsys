<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Repository\AuditLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuditService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AuditLogRepository $repo,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public function log(
        string $action,
        ?string $entity = null,
        ?int $entityId = null,
        mixed $details = null,
        ?string $ip = null,
    ): void {
        $user = $this->tokenStorage->getToken()?->getUser();
        $userId = method_exists($user ?? null, 'getId') ? $user->getId() : null;
        $userName = method_exists($user ?? null, 'getName') ? $user->getName() : ($user?->getUserIdentifier());
        $centreId = method_exists($user ?? null, 'getCentre') ? $user->getCentre()?->getId() : null;

        $prevHash = $this->repo->getLatestHash();
        $log = AuditLog::create($userId, $userName, $centreId, $action, $entity, $entityId, $details, $ip, $prevHash);

        $this->em->persist($log);
        $this->em->flush();
    }
}
