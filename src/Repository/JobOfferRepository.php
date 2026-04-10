<?php

namespace App\Repository;

use App\Entity\JobOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobOffer>
 */
class JobOfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobOffer::class);
    }

    public function save(JobOffer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(JobOffer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function searchJobs(string $search = '', string $type = ''): array
    {
        $qb = $this->createQueryBuilder('j');

        if (!empty($search)) {
            $qb->andWhere('j.title LIKE :search OR j.description LIKE :search OR j.location LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if (!empty($type)) {
            $qb->andWhere('j.jobType = :jobType')
               ->setParameter('jobType', $type);
        }

        return $qb->orderBy('j.postedDate', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function hasUserApplied(int $jobId, int $userId): bool
    {
        return $this->getEntityManager()->createQuery(
            'SELECT COUNT(jr.id) FROM App\Entity\JobRequest jr 
             WHERE jr.jobOffer = :jobId AND jr.candidate = :userId'
        )
        ->setParameter('jobId', $jobId)
        ->setParameter('userId', $userId)
        ->getSingleScalarResult() > 0;
    }

    public function findSimilarJobs(JobOffer $job): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.id != :jobId')
            ->andWhere('j.category = :category OR j.location = :location')
            ->setParameter('jobId', $job->getId())
            ->setParameter('category', $job->getCategory())
            ->setParameter('location', $job->getLocation())
            ->orderBy('j.postedDate', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(int $categoryId): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.category = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->orderBy('j.postedDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveJobs(): array
    {
        return $this->createQueryBuilder('j')
            ->orderBy('j.postedDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecommendedJobsForCandidate($candidate, int $limit = 5): array
    {
        // Simple recommendation logic: jobs that match candidate's recent applications
        $qb = $this->createQueryBuilder('j')
            ->leftJoin('j.category', 'c')
            ->orderBy('j.postedDate', 'DESC')
            ->setMaxResults($limit);

        // If candidate has applications, get their preferred categories
        $recentApplications = $this->getEntityManager()->createQuery(
            'SELECT DISTINCT(jo.category) FROM App\Entity\JobRequest jr 
             JOIN jr.jobOffer jo WHERE jr.candidate = :candidate 
             ORDER BY jr.submissionDate DESC'
        )->setParameter('candidate', $candidate)
        ->setMaxResults(3)
        ->getResult();

        if (!empty($recentApplications)) {
            $categoryIds = array_filter(array_column($recentApplications, 'category'));
            if (!empty($categoryIds)) {
                $qb->andWhere('j.category IN (:categories)')
                   ->setParameter('categories', $categoryIds);
            }
        }

        return $qb->getQuery()->getResult();
    }
}
