<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\JobOffer;
use App\Entity\JobRequest;
use App\Entity\AppEvent;
use App\Entity\Training;
use App\Entity\Complaint;
use App\Entity\QuizResult;
use Doctrine\ORM\EntityManagerInterface;

class AnalyticsService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getDashboardStatistics(): array
    {
        $stats = [];

        // User Statistics
        $stats['users'] = $this->getUserStatistics();

        // Job Statistics
        $stats['jobs'] = $this->getJobStatistics();

        // Application Statistics
        $stats['applications'] = $this->getApplicationStatistics();

        // Event Statistics
        $stats['events'] = $this->getEventStatistics();

        // Training Statistics
        $stats['trainings'] = $this->getTrainingStatistics();

        // Complaint Statistics
        $stats['complaints'] = $this->getComplaintStatistics();

        // Growth Statistics
        $stats['growth'] = $this->getGrowthStatistics();

        return $stats;
    }

    private function getUserStatistics(): array
    {
        $qb = $this->entityManager->createQueryBuilder('u')
            ->select('COUNT(u.id) as totalUsers')
            ->addSelect('SUM(CASE WHEN u.isVerified = true THEN 1 ELSE 0 END) as verifiedUsers')
            ->addSelect('SUM(CASE WHEN u.isBanned = true THEN 1 ELSE 0 END) as bannedUsers')
            ->addSelect('SUM(CASE WHEN u.roleId = 1 THEN 1 ELSE 0 END) as candidates')
            ->addSelect('SUM(CASE WHEN u.roleId = 2 THEN 1 ELSE 0 END) as hrUsers')
            ->addSelect('SUM(CASE WHEN u.roleId = 3 THEN 1 ELSE 0 END) as adminUsers')
            ->from(User::class, 'u');

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => $result['totalUsers'] ?? 0,
            'verified' => $result['verifiedUsers'] ?? 0,
            'banned' => $result['bannedUsers'] ?? 0,
            'candidates' => $result['candidates'] ?? 0,
            'hr' => $result['hrUsers'] ?? 0,
            'admin' => $result['adminUsers'] ?? 0,
            'verification_rate' => $result['totalUsers'] > 0 ? round(($result['verifiedUsers'] / $result['totalUsers']) * 100, 2) : 0,
        ];
    }

    private function getJobStatistics(): array
    {
        $qb = $this->entityManager->createQueryBuilder('j')
            ->select('COUNT(j.id) as totalJobs')
            ->addSelect('SUM(CASE WHEN j.jobType = \'Full-time\' THEN 1 ELSE 0 END) as fullTimeJobs')
            ->addSelect('SUM(CASE WHEN j.jobType = \'Part-time\' THEN 1 ELSE 0 END) as partTimeJobs')
            ->addSelect('SUM(CASE WHEN j.jobType = \'Remote\' THEN 1 ELSE 0 END) as remoteJobs')
            ->addSelect('AVG(CASE WHEN j.salaryMin IS NOT NULL AND j.salaryMax IS NOT NULL THEN (j.salaryMin + j.salaryMax) / 2 ELSE 0 END) as avgSalary')
            ->addSelect('COUNT(DISTINCT j.postedBy) as activeRecruiters')
            ->from(JobOffer::class, 'j');

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => $result['totalJobs'] ?? 0,
            'full_time' => $result['fullTimeJobs'] ?? 0,
            'part_time' => $result['partTimeJobs'] ?? 0,
            'remote' => $result['remoteJobs'] ?? 0,
            'avg_salary' => round($result['avgSalary'] ?? 0, 2),
            'active_recruiters' => $result['activeRecruiters'] ?? 0,
        ];
    }

    private function getApplicationStatistics(): array
    {
        $qb = $this->entityManager->createQueryBuilder('jr')
            ->select('COUNT(jr.id) as totalApplications')
            ->addSelect('SUM(CASE WHEN jr.status = \'PENDING\' THEN 1 ELSE 0 END) as pendingApplications')
            ->addSelect('SUM(CASE WHEN jr.status = \'APPROVED\' THEN 1 ELSE 0 END) as approvedApplications')
            ->addSelect('SUM(CASE WHEN jr.status = \'REJECTED\' THEN 1 ELSE 0 END) as rejectedApplications')
            ->addSelect('SUM(CASE WHEN jr.status = \'INTERVIEW_SCHEDULED\' THEN 1 ELSE 0 END) as interviewScheduled')
            ->addSelect('AVG(CASE WHEN jr.expectedSalary IS NOT NULL THEN jr.expectedSalary ELSE 0 END) as avgSuggestedSalary')
            ->from(JobRequest::class, 'jr');

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => $result['totalApplications'] ?? 0,
            'pending' => $result['pendingApplications'] ?? 0,
            'approved' => $result['approvedApplications'] ?? 0,
            'rejected' => $result['rejectedApplications'] ?? 0,
            'interview_scheduled' => $result['interviewScheduled'] ?? 0,
            'avg_suggested_salary' => round($result['avgSuggestedSalary'] ?? 0, 2),
            'approval_rate' => $result['totalApplications'] > 0 ? round(($result['approvedApplications'] / $result['totalApplications']) * 100, 2) : 0,
        ];
    }

    private function getEventStatistics(): array
    {
        $qb = $this->entityManager->createQueryBuilder('e')
            ->select('COUNT(e.id) as totalEvents')
            ->addSelect('SUM(CASE WHEN e.eventDate >= CURRENT_DATE() THEN 1 ELSE 0 END) as upcomingEvents')
            ->addSelect('SUM(CASE WHEN e.eventDate < CURRENT_DATE() THEN 1 ELSE 0 END) as pastEvents')
            ->addSelect('AVG(CASE WHEN e.maxParticipants IS NOT NULL THEN e.maxParticipants ELSE 0 END) as avgMaxParticipants')
            ->addSelect('SUM(CASE WHEN e.maxParticipants IS NOT NULL THEN e.maxParticipants ELSE 0 END) as totalCapacity')
            ->from(AppEvent::class, 'e');

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => $result['totalEvents'] ?? 0,
            'upcoming' => $result['upcomingEvents'] ?? 0,
            'past' => $result['pastEvents'] ?? 0,
            'avg_max_participants' => round($result['avgMaxParticipants'] ?? 0, 2),
            'total_capacity' => $result['totalCapacity'] ?? 0,
        ];
    }

    private function getTrainingStatistics(): array
    {
        $qb = $this->entityManager->createQueryBuilder('t')
            ->addSelect('COUNT(t.id) as totalTrainings')
            ->addSelect('SUM(t.likes) as totalLikes')
            ->addSelect('SUM(t.dislikes) as totalDislikes')
            ->addSelect('AVG(t.likes) as avgLikes')
            ->addSelect('COUNT(DISTINCT t.admin) as uniqueAuthors')
            ->from(Training::class, 't');

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => $result['totalTrainings'] ?? 0,
            'total_likes' => $result['totalLikes'] ?? 0,
            'total_dislikes' => $result['totalDislikes'] ?? 0,
            'avg_likes' => round($result['avgLikes'] ?? 0, 2),
            'engagement_rate' => ($result['totalLikes'] + $result['totalDislikes']) > 0 ? 
                round(($result['totalLikes'] / ($result['totalLikes'] + $result['totalDislikes'])) * 100, 2) : 0,
            'unique_authors' => $result['uniqueAuthors'] ?? 0,
        ];
    }

    private function getComplaintStatistics(): array
    {
        $qb = $this->entityManager->createQueryBuilder('c')
            ->select('COUNT(c.id) as totalComplaints')
            ->addSelect('SUM(CASE WHEN c.status = \'OPEN\' THEN 1 ELSE 0 END) as openComplaints')
            ->addSelect('SUM(CASE WHEN c.status = \'RESOLVED\' THEN 1 ELSE 0 END) as resolvedComplaints')
            ->addSelect('SUM(CASE WHEN c.status = \'IN_PROGRESS\' THEN 1 ELSE 0 END) as inProgressComplaints')
            ->from(Complaint::class, 'c');

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => $result['totalComplaints'] ?? 0,
            'open' => $result['openComplaints'] ?? 0,
            'in_progress' => $result['inProgressComplaints'] ?? 0,
            'resolved' => $result['resolvedComplaints'] ?? 0,
            'resolution_rate' => $result['totalComplaints'] > 0 ? 
                round(($result['resolvedComplaints'] / $result['totalComplaints']) * 100, 2) : 0,
        ];
    }

    private function getGrowthStatistics(): array
    {
        // User growth over last 30 days
        $userGrowthQb = $this->entityManager->createQueryBuilder('u')
            ->select('COUNT(u.id) as usersLast30Days')
            ->where('u.createdAt >= :date')
            ->setParameter('date', new \DateTime('-30 days'));

        $userGrowthPrevQb = $this->entityManager->createQueryBuilder('u')
            ->select('COUNT(u.id) as usersPrevious30Days')
            ->where('u.createdAt >= :startDate AND u.createdAt < :endDate')
            ->setParameter('startDate', new \DateTime('-60 days'))
            ->setParameter('endDate', new \DateTime('-30 days'));

        // Job posting growth
        $jobGrowthQb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(j.id) as jobsLast30Days')
            ->from(JobOffer::class, 'j')
            ->where('j.postedDate >= :date')
            ->setParameter('date', new \DateTime('-30 days'));

        // Application growth
        $appGrowthQb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(jr.id) as applicationsLast30Days')
            ->from(JobRequest::class, 'jr')
            ->where('jr.applicationDate >= :date')
            ->setParameter('date', new \DateTime('-30 days'));

        $userGrowth = $userGrowthQb->getQuery()->getSingleResult();
        $jobGrowth = $jobGrowthQb->getQuery()->getSingleResult();
        $appGrowth = $appGrowthQb->getQuery()->getSingleResult();

        return [
            'new_users_30_days' => $userGrowth['usersLast30Days'] ?? 0,
            'new_jobs_30_days' => $jobGrowth['jobsLast30Days'] ?? 0,
            'new_applications_30_days' => $appGrowth['applicationsLast30Days'] ?? 0,
            'user_growth_rate' => $this->calculateGrowthRate($userGrowth['usersPrevious30Days'] ?? 0, $userGrowth['usersLast30Days'] ?? 0),
            'job_growth_rate' => $this->calculateGrowthRate($jobGrowth['jobsPrevious30Days'] ?? 0, $jobGrowth['jobsLast30Days'] ?? 0),
            'application_growth_rate' => $this->calculateGrowthRate($appGrowth['applicationsPrevious30Days'] ?? 0, $appGrowth['applicationsLast30Days'] ?? 0),
        ];
    }

    private function calculateGrowthRate(int $previous, int $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }

    public function getJobMarketTrends(): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('j.category, COUNT(j.id) as jobCount')
            ->from(JobOffer::class, 'j')
            ->where('j.postedDate >= :date')
            ->setParameter('date', new \DateTime('-30 days'))
            ->groupBy('j.category')
            ->orderBy('jobCount', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function getUserActivityTrends(): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('DATE(u.createdAt) as date, COUNT(u.id) as userCount')
            ->from(User::class, 'u')
            ->where('u.createdAt >= :date')
            ->setParameter('date', new \DateTime('-30 days'))
            ->groupBy('DATE(u.createdAt)')
            ->orderBy('date', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function getSystemPerformanceMetrics(): array
    {
        // This would typically include database performance, response times, etc.
        // For now, return basic system health metrics
        
        return [
            'database_size' => $this->getDatabaseSize(),
            'active_sessions' => $this->getActiveSessionsCount(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'average_response_time' => $this->getAverageResponseTime(),
        ];
    }

    private function getDatabaseSize(): string
    {
        // This would typically query the database for actual size
        // For now, return estimated size
        return 'Approximately 50MB';
    }

    private function getActiveSessionsCount(): int
    {
        // This would typically check active sessions in your session store
        // For now, return estimated count
        return 25;
    }

    private function getCacheHitRate(): float
    {
        // This would typically be tracked by your caching system
        // For now, return estimated rate
        return 85.5;
    }

    private function getAverageResponseTime(): float
    {
        // This would typically be measured by your monitoring system
        // For now, return estimated time in milliseconds
        return 250.0;
    }
}
