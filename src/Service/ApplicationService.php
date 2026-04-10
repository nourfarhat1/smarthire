<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\JobRequest;
use App\Entity\JobOffer;
use App\Entity\User;

class ApplicationService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    // --- SAVED JOBS FEATURE ---

    public function saveJob(int $userId, int $jobId): void
    {
        $user = $this->entityManager->find(User::class, $userId);
        $job = $this->entityManager->find(JobOffer::class, $jobId);
        
        if ($user && $job && !$user->getSavedJobs()->contains($job)) {
            $user->addSavedJob($job);
            $this->entityManager->flush();
        }
    }

    public function unsaveJob(int $userId, int $jobId): void
    {
        $user = $this->entityManager->find(User::class, $userId);
        $job = $this->entityManager->find(JobOffer::class, $jobId);
        
        if ($user && $job) {
            $user->removeSavedJob($job);
            $this->entityManager->flush();
        }
    }

    public function getSavedJobsCount(int $userId): int
    {
        $user = $this->entityManager->find(User::class, $userId);
        return $user ? $user->getSavedJobs()->count() : 0;
    }

    /**
     * @return JobOffer[]
     */
    public function getSavedJobs(int $userId): array
    {
        $user = $this->entityManager->find(User::class, $userId);
        return $user ? $user->getSavedJobs()->toArray() : [];
    }

    // --- CREATE APPLICATION (Normal or Spontaneous) ---

    public function apply(JobRequest $request): void
    {
        $request->setStatus('PENDING');
        $this->entityManager->persist($request);
        $this->entityManager->flush();
    }

    /**
     * @return JobRequest[]
     */
    public function getByCandidate(int $candidateId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('r', 'j', 'u')
            ->from(JobRequest::class, 'r')
            ->leftJoin('r.jobOffer', 'j')
            ->leftJoin('r.candidate', 'u')
            ->where('r.candidate = :candidateId')
            ->setParameter('candidateId', $candidateId)
            ->orderBy('r.applicationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return JobRequest[]
     */
    public function getForHR(int $recruiterId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('r', 'j', 'u')
            ->from(JobRequest::class, 'r')
            ->leftJoin('r.jobOffer', 'j')
            ->leftJoin('r.candidate', 'u')
            ->where('j.postedBy = :recruiterId OR r.jobOffer IS NULL')
            ->setParameter('recruiterId', $recruiterId)
            ->orderBy('r.applicationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return JobRequest[]
     */
    public function getAll(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('r', 'j', 'u')
            ->from(JobRequest::class, 'r')
            ->leftJoin('r.jobOffer', 'j')
            ->leftJoin('r.candidate', 'u')
            ->orderBy('r.applicationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function update(JobRequest $request): void
    {
        $this->entityManager->flush();
    }

    public function delete(int $id): void
    {
        $request = $this->entityManager->find(JobRequest::class, $id);
        if ($request) {
            $this->entityManager->remove($request);
            $this->entityManager->flush();
        }
    }

    public function isSaved(int $userId, int $jobId): bool
    {
        $user = $this->entityManager->find(User::class, $userId);
        $job = $this->entityManager->find(JobOffer::class, $jobId);
        
        return $user && $job && $user->getSavedJobs()->contains($job);
    }
}
