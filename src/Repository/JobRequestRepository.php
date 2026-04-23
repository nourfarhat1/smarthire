<?php

namespace App\Repository;

use App\Entity\JobRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobRequest>
 */
class JobRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobRequest::class);
    }

    public function findByCandidate(int $candidateId): array
    {
        return $this->createQueryBuilder('jr')
            ->where('jr.candidate = :candidateId')
            ->setParameter('candidateId', $candidateId)
            ->orderBy('jr.submissionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('jr')
            ->where('jr.status = :status')
            ->setParameter('status', $status)
            ->orderBy('jr.submissionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingApplications(): array
    {
        return $this->findBy(['status' => 'PENDING'], ['submissionDate' => 'DESC']);
    }

    public function findRecentApplications(int $limit = 10): array
    {
        return $this->createQueryBuilder('jr')
            ->orderBy('jr.submissionDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function save(JobRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(JobRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByCandidateWithFilters(int $candidateId, string $status = '', ?\DateTime $dateFrom = null, ?\DateTime $dateTo = null): array
    {
        $qb = $this->createQueryBuilder('jr')
            ->leftJoin('jr.jobOffer', 'j')
            ->where('jr.candidate = :candidateId')
            ->setParameter('candidateId', $candidateId);

        if (!empty($status)) {
            $qb->andWhere('jr.status = :status')
               ->setParameter('status', $status);
        }

        if ($dateFrom) {
            $qb->andWhere('jr.submissionDate >= :dateFrom')
               ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $qb->andWhere('jr.submissionDate <= :dateTo')
               ->setParameter('dateTo', $dateTo);
        }

        return $qb->orderBy('jr.submissionDate', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function hasUserApplied(int $jobId, int $userId): bool
    {
        return $this->createQueryBuilder('jr')
            ->select('COUNT(jr.id)')
            ->where('jr.jobOffer = :jobId')
            ->andWhere('jr.candidate = :userId')
            ->setParameter('jobId', $jobId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function findApplicationsForHR(int $hrId): array
    {
        return $this->createQueryBuilder('jr')
            ->leftJoin('jr.jobOffer', 'j')
            ->leftJoin('j.recruiter', 'r')
            ->where('r.id = :hrId')
            ->setParameter('hrId', $hrId)
            ->getQuery()
            ->getResult();
    }

    public function findPendingApplicationsForHR(int $hrId): array
    {
        return $this->createQueryBuilder('jr')
            ->leftJoin('jr.jobOffer', 'j')
            ->leftJoin('j.recruiter', 'r')
            ->where('r.id = :hrId')
            ->andWhere('jr.status = :status')
            ->setParameter('hrId', $hrId)
            ->setParameter('status', 'PENDING')
            ->getQuery()
            ->getResult();
    }

    public function findRecentApplicationsForHR(int $hrId, int $limit = 5): array
    {
        return $this->createQueryBuilder('jr')
            ->leftJoin('jr.jobOffer', 'j')
            ->leftJoin('j.recruiter', 'r')
            ->leftJoin('jr.candidate', 'c')
            ->where('r.id = :hrId')
            ->setParameter('hrId', $hrId)
            ->orderBy('jr.submissionDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findSpontaneousApplicationsForHR(int $hrId): array
    {
        return $this->createQueryBuilder('jr')
            ->leftJoin('jr.candidate', 'c')
            ->where('jr.jobOffer IS NULL')
            ->orderBy('jr.submissionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findApplicationsThisMonth($candidate): array
    {
        $startOfMonth = new \DateTime('first day of this month');
        $endOfMonth = new \DateTime('last day of this month 23:59:59');
        
        return $this->createQueryBuilder('jr')
            ->where('jr.candidate = :candidate')
            ->andWhere('jr.submissionDate >= :startOfMonth')
            ->andWhere('jr.submissionDate <= :endOfMonth')
            ->setParameter('candidate', $candidate)
            ->setParameter('startOfMonth', $startOfMonth)
            ->setParameter('endOfMonth', $endOfMonth)
            ->getQuery()
            ->getResult();
    }
}
