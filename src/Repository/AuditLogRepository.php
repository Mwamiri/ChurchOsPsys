<?php

namespace App\Repository;

use App\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    public function search(array $filters = [], int $page = 1, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('a');

        if (!empty($filters['action'])) {
            $qb->andWhere('a.action = :action')->setParameter('action', $filters['action']);
        }
        if (!empty($filters['entity'])) {
            $qb->andWhere('a.entity = :entity')->setParameter('entity', $filters['entity']);
        }
        if (!empty($filters['user_id'])) {
            $qb->andWhere('a.userId = :uid')->setParameter('uid', $filters['user_id']);
        }
        if (!empty($filters['from'])) {
            $qb->andWhere('a.createdAt >= :from')->setParameter('from', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $qb->andWhere('a.createdAt <= :to')->setParameter('to', $filters['to']);
        }

        $total = (clone $qb)->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();

        $rows = $qb->orderBy('a.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()->getResult();

        return ['rows' => $rows, 'total' => (int)$total, 'pages' => (int)ceil($total / $limit), 'page' => $page];
    }

    public function getLatestHash(): ?string
    {
        $row = $this->createQueryBuilder('a')
            ->select('a.hash')
            ->orderBy('a.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
        return $row ? $row['hash'] : null;
    }

    public function recentActivity(int $days = 30): array
    {
        $since = new \DateTimeImmutable("-{$days} days");
        return $this->createQueryBuilder('a')
            ->select('a.action, COUNT(a.id) as cnt')
            ->where('a.createdAt >= :since')
            ->setParameter('since', $since)
            ->groupBy('a.action')
            ->orderBy('cnt', 'DESC')
            ->getQuery()->getArrayResult();
    }
}
