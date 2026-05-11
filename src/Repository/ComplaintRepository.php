<?php

namespace App\Repository;

use App\Entity\Complaint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Complaint>
 */
class ComplaintRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Complaint::class);
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.status = :status')
            ->setParameter('status', $status)
            ->orderBy('c.submissionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOpenComplaints(): array
    {
        return $this->findBy(['status' => 'OPEN'], ['submissionDate' => 'DESC']);
    }

    public function findResolvedComplaints(): array
    {
        return $this->findBy(['status' => 'RESOLVED'], ['submissionDate' => 'DESC']);
    }

    public function findRecentComplaints(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.submissionDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function save(Complaint $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Complaint $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUserWithFilters(int $userId, string $search = '', string $status = '', string $type = ''): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.type', 't')
            ->where('c.user = :userId')
            ->setParameter('userId', $userId);

        if (!empty($search)) {
            $qb->andWhere('c.subject LIKE :search OR c.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if (!empty($status)) {
            $qb->andWhere('c.status = :status')
               ->setParameter('status', $status);
        }

        if (!empty($type)) {
            $qb->andWhere('t.id = :type')
               ->setParameter('type', $type);
        }

        return $qb->orderBy('c.submissionDate', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}
