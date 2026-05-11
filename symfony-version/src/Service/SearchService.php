<?php

namespace App\Service;

use App\Entity\JobOffer;
use App\Entity\JobRequest;
use App\Entity\AppEvent;
use App\Entity\Training;
use App\Entity\User;
use App\Repository\JobOfferRepository;
use App\Repository\JobRequestRepository;
use App\Repository\AppEventRepository;
use App\Repository\TrainingRepository;
use Doctrine\ORM\EntityManagerInterface;

class SearchService
{
    private EntityManagerInterface $entityManager;
    private JobOfferRepository $jobOfferRepository;
    private JobRequestRepository $jobRequestRepository;
    private AppEventRepository $appEventRepository;
    private TrainingRepository $trainingRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        JobOfferRepository $jobOfferRepository,
        JobRequestRepository $jobRequestRepository,
        AppEventRepository $appEventRepository,
        TrainingRepository $trainingRepository
    ) {
        $this->entityManager = $entityManager;
        $this->jobOfferRepository = $jobOfferRepository;
        $this->jobRequestRepository = $jobRequestRepository;
        $this->appEventRepository = $appEventRepository;
        $this->trainingRepository = $trainingRepository;
    }

    public function searchJobs(array $criteria): array
    {
        $qb = $this->jobOfferRepository->createQueryBuilder('j')
            ->leftJoin('j.category', 'c')
            ->addSelect('c.name as categoryName');

        // Apply filters
        if (!empty($criteria['search'])) {
            $qb->andWhere('j.title LIKE :search OR j.description LIKE :search OR j.location LIKE :search')
               ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        if (!empty($criteria['category'])) {
            $qb->andWhere('c.id = :category')
               ->setParameter('category', $criteria['category']);
        }

        if (!empty($criteria['location'])) {
            $qb->andWhere('j.location LIKE :location')
               ->setParameter('location', '%' . $criteria['location'] . '%');
        }

        if (!empty($criteria['jobType'])) {
            $qb->andWhere('j.jobType = :jobType')
               ->setParameter('jobType', $criteria['jobType']);
        }

        if (!empty($criteria['salaryMin'])) {
            $qb->andWhere('j.salaryMin >= :salaryMin')
               ->setParameter('salaryMin', $criteria['salaryMin']);
        }

        if (!empty($criteria['salaryMax'])) {
            $qb->andWhere('j.salaryMax <= :salaryMax')
               ->setParameter('salaryMax', $criteria['salaryMax']);
        }

        if (!empty($criteria['postedWithin'])) {
            $dateLimit = new \DateTime();
            switch ($criteria['postedWithin']) {
                case '7':
                    $dateLimit->modify('-7 days');
                    break;
                case '30':
                    $dateLimit->modify('-30 days');
                    break;
                case '90':
                    $dateLimit->modify('-90 days');
                    break;
            }
            $qb->andWhere('j.postedDate >= :postedDate')
               ->setParameter('postedDate', $dateLimit);
        }

        return $qb->orderBy('j.postedDate', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function searchApplications(array $criteria): array
    {
        $qb = $this->jobRequestRepository->createQueryBuilder('jr')
            ->leftJoin('jr.jobOffer', 'j')
            ->leftJoin('jr.candidate', 'c')
            ->addSelect('j.title as jobTitle', 'c.firstName', 'c.lastName', 'c.email');

        if (!empty($criteria['search'])) {
            $qb->andWhere('j.title LIKE :search OR c.firstName LIKE :search OR c.lastName LIKE :search')
               ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('jr.status = :status')
               ->setParameter('status', $criteria['status']);
        }

        if (!empty($criteria['dateFrom'])) {
            $qb->andWhere('jr.submissionDate >= :dateFrom')
               ->setParameter('dateFrom', new \DateTime($criteria['dateFrom']));
        }

        if (!empty($criteria['dateTo'])) {
            $qb->andWhere('jr.submissionDate <= :dateTo')
               ->setParameter('dateTo', new \DateTime($criteria['dateTo']));
        }

        return $qb->orderBy('jr.submissionDate', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function searchEvents(array $criteria): array
    {
        $qb = $this->appEventRepository->createQueryBuilder('e')
            ->leftJoin('e.participants', 'p')
            ->addSelect('COUNT(p.id) as participantCount');

        if (!empty($criteria['search'])) {
            $qb->andWhere('e.name LIKE :search OR e.description LIKE :search')
               ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        if (!empty($criteria['location'])) {
            $qb->andWhere('e.location LIKE :location')
               ->setParameter('location', '%' . $criteria['location'] . '%');
        }

        if (!empty($criteria['month'])) {
            $qb->andWhere('MONTH(e.eventDate) = :month')
               ->setParameter('month', $criteria['month']);
        }

        if (!empty($criteria['status'])) {
            switch ($criteria['status']) {
                case 'upcoming':
                    $qb->andWhere('e.eventDate >= :today')
                       ->setParameter('today', new \DateTime('today'));
                    break;
                case 'past':
                    $qb->andWhere('e.eventDate < :today')
                       ->setParameter('today', new \DateTime('today'));
                    break;
                case 'today':
                    $qb->andWhere('DATE(e.eventDate) = :today')
                       ->setParameter('today', new \DateTime('today'));
                    break;
            }
        }

        return $qb->orderBy('e.eventDate', 'ASC')
                  ->groupBy('e.id')
                  ->getQuery()
                  ->getResult();
    }

    public function searchTraining(array $criteria): array
    {
        $qb = $this->trainingRepository->createQueryBuilder('t')
            ->leftJoin('t.admin', 'a')
            ->addSelect('a.firstName as adminFirstName', 'a.lastName as adminLastName');

        if (!empty($criteria['search'])) {
            $qb->andWhere('t.title LIKE :search OR t.description LIKE :search OR t.category LIKE :search')
               ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        if (!empty($criteria['category'])) {
            $qb->andWhere('t.category = :category')
               ->setParameter('category', $criteria['category']);
        }

        if (!empty($criteria['admin'])) {
            $qb->andWhere('(a.firstName LIKE :admin OR a.lastName LIKE :admin)')
               ->setParameter('admin', '%' . $criteria['admin'] . '%');
        }

        return $qb->orderBy('t.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function searchUsers(array $criteria): array
    {
        $qb = $this->entityManager->createQueryBuilder('u')
            ->from(User::class, 'u');

        if (!empty($criteria['search'])) {
            $qb->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        if (!empty($criteria['role'])) {
            $qb->andWhere('u.roleId = :role')
               ->setParameter('role', $criteria['role']);
        }

        if (!empty($criteria['verified'])) {
            $qb->andWhere('u.isVerified = :verified')
               ->setParameter('verified', $criteria['verified']);
        }

        return $qb->orderBy('u.createdAt', 'DESC')
                  ->setMaxResults(50)
                  ->getQuery()
                  ->getResult();
    }

    public function getPopularSearches(): array
    {
        // This would typically track search queries and return popular ones
        // For now, return some sample popular searches
        return [
            'PHP Developer',
            'Frontend Developer',
            'Data Scientist',
            'Project Manager',
            'UX Designer',
            'Full Stack Developer',
            'DevOps Engineer',
            'Marketing Manager',
            'Sales Representative',
        ];
    }

    public function getSearchSuggestions(string $query): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $suggestions = [];
        
        // Job title suggestions
        $jobTitles = $this->jobOfferRepository->createQueryBuilder('j')
            ->select('j.title')
            ->where('j.title LIKE :query')
            ->setParameter('query', $query . '%')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        foreach ($jobTitles as $job) {
            $suggestions[] = ['type' => 'job', 'text' => $job['title']];
        }
        
        // Location suggestions
        $locations = $this->jobOfferRepository->createQueryBuilder('j')
            ->select('DISTINCT j.location')
            ->where('j.location LIKE :query')
            ->setParameter('query', $query . '%')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
        
        foreach ($locations as $location) {
            $suggestions[] = ['type' => 'location', 'text' => $location['location']];
        }
        
        return $suggestions;
    }
}
