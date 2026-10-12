<?php

namespace App\Repository;

use App\Entity\Centre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CentreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Centre::class);
    }

    public function findTree(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.parent IS NULL')
            ->orderBy('c.name')
            ->getQuery()
            ->getResult();
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isActive = 1')
            ->orderBy('c.name')
            ->getQuery()
            ->getResult();
    }

    public function flatOptions(): array
    {
        $centres = $this->findAllActive();
        $out = [];
        foreach ($centres as $c) {
            $out[$c->getId()] = $c->getFullPath();
        }
        asort($out);
        return $out;
    }

    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.isActive = 1')
            ->getQuery()->getSingleScalarResult();
    }
}
