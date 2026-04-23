<?php

namespace App\Tests\Unit\Service;

use App\Service\JobService;
use App\Entity\JobOffer;
use App\Entity\JobRequest;
use App\Entity\User;
use App\Repository\JobOfferRepository;
use App\Repository\JobRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class JobServiceTest extends TestCase
{
    private JobService $jobService;
    private JobOfferRepository $jobOfferRepository;
    private JobRequestRepository $jobRequestRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->jobOfferRepository = $this->createMock(JobOfferRepository::class);
        $this->jobRequestRepository = $this->createMock(JobRequestRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->jobService = new JobService(
            $this->jobOfferRepository,
            $this->jobRequestRepository,
            $this->entityManager
        );
    }

    public function testHasUserAppliedForJobReturnsTrueWhenApplicationExists(): void
    {
        $user = $this->createMock(User::class);
        $jobOffer = $this->createMock(JobOffer::class);
        $existingApplication = $this->createMock(JobRequest::class);

        $this->jobRequestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['candidate' => $user, 'jobOffer' => $jobOffer])
            ->willReturn($existingApplication);

        $result = $this->jobService->hasUserAppliedForJob($user, $jobOffer);

        $this->assertTrue($result);
    }

    public function testHasUserAppliedForJobReturnsFalseWhenNoApplicationExists(): void
    {
        $user = $this->createMock(User::class);
        $jobOffer = $this->createMock(JobOffer::class);

        $this->jobRequestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['candidate' => $user, 'jobOffer' => $jobOffer])
            ->willReturn(null);

        $result = $this->jobService->hasUserAppliedForJob($user, $jobOffer);

        $this->assertFalse($result);
    }

    public function testApplyForJobFailsWhenUserAlreadyApplied(): void
    {
        $user = $this->createMock(User::class);
        $jobOffer = $this->createMock(JobOffer::class);
        $existingApplication = $this->createMock(JobRequest::class);

        $this->jobRequestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['candidate' => $user, 'jobOffer' => $jobOffer])
            ->willReturn($existingApplication);

        $result = $this->jobService->applyForJob($user, $jobOffer, []);

        $this->assertFalse($result['success']);
        $this->assertEquals('You have already applied for this job', $result['error']);
        $this->assertTrue($result['existing_application']);
    }

    public function testApplyForJobFailsWhenJobIsInactive(): void
    {
        $user = $this->createMock(User::class);
        $jobOffer = $this->createMock(JobOffer::class);

        $this->jobRequestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['candidate' => $user, 'jobOffer' => $jobOffer])
            ->willReturn(null);

        $jobOffer->method('isActive')->willReturn(false);

        $result = $this->jobService->applyForJob($user, $jobOffer, []);

        $this->assertFalse($result['success']);
        $this->assertEquals('This job is no longer accepting applications', $result['error']);
        $this->assertTrue($result['job_inactive']);
    }

    public function testApplyForJobFailsWhenDeadlinePassed(): void
    {
        $user = $this->createMock(User::class);
        $jobOffer = $this->createMock(JobOffer::class);
        $pastDate = new \DateTime('2023-01-01');

        $this->jobRequestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['candidate' => $user, 'jobOffer' => $jobOffer])
            ->willReturn(null);

        $jobOffer->method('isActive')->willReturn(true);
        $jobOffer->method('getDeadline')->willReturn($pastDate);

        $result = $this->jobService->applyForJob($user, $jobOffer, []);

        $this->assertFalse($result['success']);
        $this->assertEquals('The application deadline for this job has passed', $result['error']);
        $this->assertTrue($result['deadline_passed']);
    }

    public function testApplyForJobSucceedsWithValidData(): void
    {
        $user = $this->createMock(User::class);
        $jobOffer = $this->createMock(JobOffer::class);
        $futureDate = new \DateTime('2025-12-31');

        $this->jobRequestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['candidate' => $user, 'jobOffer' => $jobOffer])
            ->willReturn(null);

        $jobOffer->method('isActive')->willReturn(true);
        $jobOffer->method('getDeadline')->willReturn($futureDate);

        $this->entityManager->expects($this->once())
            ->method('persist');
        $this->entityManager->expects($this->once())
            ->method('flush');

        $applicationData = [
            'coverLetter' => 'Test cover letter',
            'cvFile' => 'cv.pdf',
            'expectedSalary' => 50000
        ];

        $result = $this->jobService->applyForJob($user, $jobOffer, $applicationData);

        $this->assertTrue($result['success']);
        $this->assertEquals('Application submitted successfully', $result['message']);
        $this->assertArrayHasKey('application_id', $result);
    }

    public function testApplyForJobFailsWithDatabaseError(): void
    {
        $user = $this->createMock(User::class);
        $jobOffer = $this->createMock(JobOffer::class);
        $futureDate = new \DateTime('2025-12-31');

        $this->jobRequestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['candidate' => $user, 'jobOffer' => $jobOffer])
            ->willReturn(null);

        $jobOffer->method('isActive')->willReturn(true);
        $jobOffer->method('getDeadline')->willReturn($futureDate);

        $this->entityManager->expects($this->once())
            ->method('persist');
        $this->entityManager->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception('Database error'));

        $result = $this->jobService->applyForJob($user, $jobOffer, []);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to submit application', $result['error']);
    }

    public function testCanUserApplyForJobReturnsTrueWhenAllChecksPass(): void
    {
        $user = $this->createMock(User::class);
        $jobOffer = $this->createMock(JobOffer::class);
        $futureDate = new \DateTime('2025-12-31');

        $this->jobRequestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['candidate' => $user, 'jobOffer' => $jobOffer])
            ->willReturn(null);

        $jobOffer->method('isActive')->willReturn(true);
        $jobOffer->method('getDeadline')->willReturn($futureDate);
        $jobOffer->method('getPostedBy')->willReturn($this->createMock(User::class));

        $result = $this->jobService->canUserApplyForJob($user, $jobOffer);

        $this->assertTrue($result['can_apply']);
        $this->assertEmpty($result['reasons']);
    }

    public function testCanUserApplyForJobReturnsFalseWhenAlreadyApplied(): void
    {
        $user = $this->createMock(User::class);
        $jobOffer = $this->createMock(JobOffer::class);
        $existingApplication = $this->createMock(JobRequest::class);

        $this->jobRequestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['candidate' => $user, 'jobOffer' => $jobOffer])
            ->willReturn($existingApplication);

        $result = $this->jobService->canUserApplyForJob($user, $jobOffer);

        $this->assertFalse($result['can_apply']);
        $this->assertContains('You have already applied for this job', $result['reasons']);
    }

    public function testCanUserApplyForJobReturnsFalseWhenJobInactive(): void
    {
        $user = $this->createMock(User::class);
        $jobOffer = $this->createMock(JobOffer::class);

        $this->jobRequestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['candidate' => $user, 'jobOffer' => $jobOffer])
            ->willReturn(null);

        $jobOffer->method('isActive')->willReturn(false);

        $result = $this->jobService->canUserApplyForJob($user, $jobOffer);

        $this->assertFalse($result['can_apply']);
        $this->assertContains('This job is no longer accepting applications', $result['reasons']);
    }

    public function testCanUserApplyForJobReturnsFalseWhenDeadlinePassed(): void
    {
        $user = $this->createMock(User::class);
        $jobOffer = $this->createMock(JobOffer::class);
        $pastDate = new \DateTime('2023-01-01');

        $this->jobRequestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['candidate' => $user, 'jobOffer' => $jobOffer])
            ->willReturn(null);

        $jobOffer->method('isActive')->willReturn(true);
        $jobOffer->method('getDeadline')->willReturn($pastDate);

        $result = $this->jobService->canUserApplyForJob($user, $jobOffer);

        $this->assertFalse($result['can_apply']);
        $this->assertContains('The application deadline has passed', $result['reasons']);
    }

    public function testCanUserApplyForJobReturnsFalseWhenUserPostedJob(): void
    {
        $user = $this->createMock(User::class);
        $jobOffer = $this->createMock(JobOffer::class);
        $futureDate = new \DateTime('2025-12-31');

        $this->jobRequestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['candidate' => $user, 'jobOffer' => $jobOffer])
            ->willReturn(null);

        $jobOffer->method('isActive')->willReturn(true);
        $jobOffer->method('getDeadline')->willReturn($futureDate);
        $jobOffer->method('getPostedBy')->willReturn($user);

        $result = $this->jobService->canUserApplyForJob($user, $jobOffer);

        $this->assertFalse($result['can_apply']);
        $this->assertContains('You cannot apply to jobs you have posted', $result['reasons']);
    }

    public function testGetUserApplications(): void
    {
        $user = $this->createMock(User::class);
        $applications = [
            $this->createMock(JobRequest::class),
            $this->createMock(JobRequest::class)
        ];

        $this->jobRequestRepository->expects($this->once())
            ->method('findBy')
            ->with(['candidate' => $user], ['applicationDate' => 'DESC'])
            ->willReturn($applications);

        $result = $this->jobService->getUserApplications($user);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(JobRequest::class, $result[0]);
        $this->assertInstanceOf(JobRequest::class, $result[1]);
    }

    public function testGetJobApplications(): void
    {
        $jobOffer = $this->createMock(JobOffer::class);
        $applications = [
            $this->createMock(JobRequest::class),
            $this->createMock(JobRequest::class),
            $this->createMock(JobRequest::class)
        ];

        $this->jobRequestRepository->expects($this->once())
            ->method('findBy')
            ->with(['jobOffer' => $jobOffer], ['applicationDate' => 'DESC'])
            ->willReturn($applications);

        $result = $this->jobService->getJobApplications($jobOffer);

        $this->assertCount(3, $result);
        foreach ($result as $application) {
            $this->assertInstanceOf(JobRequest::class, $application);
        }
    }

    public function testWithdrawApplicationSucceeds(): void
    {
        $user = $this->createMock(User::class);
        $jobOffer = $this->createMock(JobOffer::class);
        $application = $this->createMock(JobRequest::class);

        $this->jobRequestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['candidate' => $user, 'jobOffer' => $jobOffer])
            ->willReturn($application);

        $application->method('getStatus')->willReturn('PENDING');

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($application);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->jobService->withdrawApplication($user, $jobOffer);

        $this->assertTrue($result['success']);
        $this->assertEquals('Application withdrawn successfully', $result['message']);
    }

    public function testWithdrawApplicationFailsWhenNoApplicationExists(): void
    {
        $user = $this->createMock(User::class);
        $jobOffer = $this->createMock(JobOffer::class);

        $this->jobRequestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['candidate' => $user, 'jobOffer' => $jobOffer])
            ->willReturn(null);

        $result = $this->jobService->withdrawApplication($user, $jobOffer);

        $this->assertFalse($result['success']);
        $this->assertEquals('No application found for this job', $result['error']);
    }

    public function testWithdrawApplicationFailsWhenApplicationApproved(): void
    {
        $user = $this->createMock(User::class);
        $jobOffer = $this->createMock(JobOffer::class);
        $application = $this->createMock(JobRequest::class);

        $this->jobRequestRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['candidate' => $user, 'jobOffer' => $jobOffer])
            ->willReturn($application);

        $application->method('getStatus')->willReturn('APPROVED');

        $result = $this->jobService->withdrawApplication($user, $jobOffer);

        $this->assertFalse($result['success']);
        $this->assertEquals('This application cannot be withdrawn in its current status', $result['error']);
    }

    public function testGetUserApplicationStats(): void
    {
        $user = $this->createMock(User::class);
        
        // Create mock applications with different statuses
        $pendingApp = $this->createMock(JobRequest::class);
        $pendingApp->method('getStatus')->willReturn('PENDING');
        $pendingApp->method('getInterviews')->willReturn(new \ArrayIterator());

        $approvedApp = $this->createMock(JobRequest::class);
        $approvedApp->method('getStatus')->willReturn('APPROVED');
        $approvedApp->method('getInterviews')->willReturn(new \ArrayIterator());

        $rejectedApp = $this->createMock(JobRequest::class);
        $rejectedApp->method('getStatus')->willReturn('REJECTED');
        $rejectedApp->method('getInterviews')->willReturn(new \ArrayIterator());

        $interviewApp = $this->createMock(JobRequest::class);
        $interviewApp->method('getStatus')->willReturn('PENDING');
        $interviewApp->method('getInterviews')->willReturn(new \ArrayIterator(['interview']));

        $applications = [$pendingApp, $approvedApp, $rejectedApp, $interviewApp];

        $this->jobRequestRepository->expects($this->once())
            ->method('findBy')
            ->with(['candidate' => $user], ['applicationDate' => 'DESC'])
            ->willReturn($applications);

        $result = $this->jobService->getUserApplicationStats($user);

        $this->assertEquals(4, $result['total_applications']);
        $this->assertEquals(2, $result['pending_applications']); // pendingApp + interviewApp
        $this->assertEquals(1, $result['approved_applications']);
        $this->assertEquals(1, $result['rejected_applications']);
        $this->assertEquals(1, $result['interview_scheduled']);
        $this->assertCount(5, $result['recent_applications']); // Should return max 5
    }

    public function testGetJobApplicationStats(): void
    {
        $jobOffer = $this->createMock(JobOffer::class);
        
        // Create mock applications
        $app1 = $this->createMock(JobRequest::class);
        $app1->method('getStatus')->willReturn('PENDING');
        $app1->method('getInterviews')->willReturn(new \ArrayIterator());
        $app1->method('getApplicationDate')->willReturn(new \DateTime('2024-01-01'));

        $app2 = $this->createMock(JobRequest::class);
        $app2->method('getStatus')->willReturn('APPROVED');
        $app2->method('getInterviews')->willReturn(new \ArrayIterator());
        $app2->method('getApplicationDate')->willReturn(new \DateTime('2024-01-02'));

        $app3 = $this->createMock(JobRequest::class);
        $app3->method('getStatus')->willReturn('REJECTED');
        $app3->method('getInterviews')->willReturn(new \ArrayIterator());
        $app3->method('getApplicationDate')->willReturn(new \DateTime('2024-01-01'));

        $applications = [$app1, $app2, $app3];

        $this->jobRequestRepository->expects($this->once())
            ->method('findBy')
            ->with(['jobOffer' => $jobOffer], ['applicationDate' => 'DESC'])
            ->willReturn($applications);

        $result = $this->jobService->getJobApplicationStats($jobOffer);

        $this->assertEquals(3, $result['total_applications']);
        $this->assertEquals(1, $result['pending_applications']);
        $this->assertEquals(1, $result['approved_applications']);
        $this->assertEquals(1, $result['rejected_applications']);
        $this->assertEquals(0, $result['interview_scheduled']);
        $this->assertArrayHasKey('applications_by_date', $result);
        $this->assertArrayHasKey('2024-01-01', $result['applications_by_date']);
        $this->assertArrayHasKey('2024-01-02', $result['applications_by_date']);
    }

    public function testGetRecommendedJobs(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $jobs = [
            $this->createMock(JobOffer::class),
            $this->createMock(JobOffer::class),
            $this->createMock(JobOffer::class)
        ];

        $this->jobOfferRepository->expects($this->once())
            ->method('findActiveJobsNotAppliedByUser')
            ->with(1, [], 10)
            ->willReturn($jobs);

        $result = $this->jobService->getRecommendedJobs($user, 10);

        $this->assertCount(3, $result);
        foreach ($result as $job) {
            $this->assertInstanceOf(JobOffer::class, $job);
        }
    }

    /**
     * @dataProvider applicationStatusProvider
     */
    public function testApplicationStatusHandling(string $status, int $expectedPending, int $expectedApproved, int $expectedRejected): void
    {
        $user = $this->createMock(User::class);
        
        $applications = [];
        for ($i = 0; $i < 5; $i++) {
            $app = $this->createMock(JobRequest::class);
            $app->method('getStatus')->willReturn($status);
            $app->method('getInterviews')->willReturn(new \ArrayIterator());
            $applications[] = $app;
        }

        $this->jobRequestRepository->expects($this->once())
            ->method('findBy')
            ->with(['candidate' => $user], ['applicationDate' => 'DESC'])
            ->willReturn($applications);

        $result = $this->jobService->getUserApplicationStats($user);

        $this->assertEquals($expectedPending, $result['pending_applications']);
        $this->assertEquals($expectedApproved, $result['approved_applications']);
        $this->assertEquals($expectedRejected, $result['rejected_applications']);
    }

    public static function applicationStatusProvider(): array
    {
        return [
            ['PENDING', 5, 0, 0],
            ['APPROVED', 0, 5, 0],
            ['REJECTED', 0, 0, 5],
        ];
    }
}
