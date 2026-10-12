<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }
        $user->setPasswordHash($newHashedPassword);
        $this->getEntityManager()->flush();
    }

    public function findByUsernameOrEmail(string $identifier): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :val OR u.email = :val')
            ->setParameter('val', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function search(string $q, ?int $centreId = null, ?string $role = null, int $page = 1, int $limit = 30): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.centre', 'c')
            ->addSelect('c');

        if ($q) {
            $qb->andWhere('u.name LIKE :q OR u.username LIKE :q OR u.email LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }
        if ($centreId) {
            $qb->andWhere('u.centre = :cid')->setParameter('cid', $centreId);
        }
        if ($role) {
            $qb->andWhere('u.role = :role')->setParameter('role', $role);
        }

        $total = (clone $qb)->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        $rows = $qb->orderBy('u.name')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ['rows' => $rows, 'total' => (int) $total, 'pages' => (int) ceil($total / $limit), 'page' => $page];
    }

    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isActive = 1')
            ->getQuery()->getSingleScalarResult();
    }

    public function countByRole(): array
    {
        $rows = $this->createQueryBuilder('u')
            ->select('u.role, COUNT(u.id) as cnt')
            ->where('u.isActive = 1')
            ->groupBy('u.role')
            ->getQuery()->getArrayResult();
        $out = [];
        foreach ($rows as $r) { $out[$r['role']] = (int)$r['cnt']; }
        return $out;
    }

    public function activeSysadminCount(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.role = :r AND u.isActive = 1')
            ->setParameter('r', 'sysadmin')
            ->getQuery()->getSingleScalarResult();
    }
}
