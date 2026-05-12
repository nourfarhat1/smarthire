<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function dashboard(): Response
    {
        return $this->render('dashboard/index.html.twig');
    }

    #[Route('/jobs', name: 'app_jobs')]
    #[IsGranted('ROLE_USER')]
    public function jobs(): Response
    {
        // This would typically fetch jobs from repository
        // For now, return sample data
        $jobs = [
            [
                'id' => 1,
                'title' => 'Senior PHP Developer',
                'location' => 'Tunis, Tunisia',
                'jobType' => 'Full-time',
                'salaryRange' => '$60,000 - $80,000',
                'categoryName' => 'Technology',
                'description' => 'We are looking for an experienced PHP developer to join our growing team...',
                'postedDate' => new \DateTime('-2 days'),
                'recruiter' => ['fullName' => 'John Doe']
            ],
            [
                'id' => 2,
                'title' => 'Frontend Developer',
                'location' => 'Remote',
                'jobType' => 'Full-time',
                'salaryRange' => '$50,000 - $70,000',
                'categoryName' => 'Technology',
                'description' => 'Join our frontend team to build amazing user interfaces...',
                'postedDate' => new \DateTime('-5 days'),
                'recruiter' => ['fullName' => 'Jane Smith']
            ]
        ];
        
        return $this->render('jobs/index.html.twig', [
            'jobs' => $jobs,
        ]);
    }

    #[Route('/jobs/{id}', name: 'app_jobs_show')]
    public function showJob(int $id): Response
    {
        // This would typically fetch job from repository
        // For now, return sample data
        $job = [
            'id' => $id,
            'title' => 'Senior PHP Developer',
            'location' => 'Tunis, Tunisia',
            'jobType' => 'Full-time',
            'salaryRange' => '$60,000 - $80,000',
            'categoryName' => 'Technology',
            'description' => 'We are looking for an experienced PHP developer with strong skills in Laravel, Symfony, and modern PHP frameworks. You will be responsible for developing robust web applications, working with cross-functional teams, and contributing to our technical architecture.',
            'postedDate' => new \DateTime('-2 days'),
            'recruiter' => [
                'fullName' => 'John Doe',
                'email' => 'john.doe@company.com'
            ]
        ];
        
        return $this->render('jobs/show.html.twig', [
            'job' => $job,
        ]);
    }

    #[Route('/jobs/{id}/apply', name: 'app_jobs_apply')]
    public function applyJob(int $id): Response
    {
        // This would handle job application logic
        $this->addFlash('success', 'Your application has been submitted successfully!');
        
        return $this->redirectToRoute('app_jobs');
    }

    #[Route('/applications', name: 'app_applications')]
    public function applications(): Response
    {
        // This would typically fetch applications from repository
        // For now, return sample data
        $applications = [
            [
                'id' => 1,
                'jobTitle' => 'Senior PHP Developer',
                'status' => 'PENDING',
                'submissionDate' => new \DateTime('-3 days'),
                'coverLetter' => 'I am very interested in this position as it aligns perfectly with my skills and experience...',
                'suggestedSalary' => 65000,
                'candidateName' => 'John Candidate',
                'candidateEmail' => 'john.candidate@email.com',
                'jobOffer' => [
                    'id' => 1,
                    'title' => 'Senior PHP Developer',
                    'location' => 'Tunis, Tunisia',
                    'categoryName' => 'Technology',
                    'recruiter' => ['fullName' => 'John Doe']
                ],
                'interviews' => []
            ],
            [
                'id' => 2,
                'jobTitle' => 'Frontend Developer',
                'status' => 'INTERVIEW_SCHEDULED',
                'submissionDate' => new \DateTime('-10 days'),
                'coverLetter' => 'I have 5 years of experience in frontend development...',
                'suggestedSalary' => 55000,
                'candidateName' => 'Jane Applicant',
                'candidateEmail' => 'jane.applicant@email.com',
                'jobOffer' => [
                    'id' => 2,
                    'title' => 'Frontend Developer',
                    'location' => 'Remote',
                    'categoryName' => 'Technology',
                    'recruiter' => ['fullName' => 'Jane Smith']
                ],
                'interviews' => [
                    [
                        'dateTime' => new \DateTime('+2 days'),
                        'location' => 'Video Call',
                        'status' => 'SCHEDULED',
                        'notes' => 'Technical interview with senior developers'
                    ]
                ]
            ]
        ];
        
        return $this->render('applications/index.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/applications/{id}', name: 'app_applications_show')]
    public function showApplication(int $id): Response
    {
        // This would typically fetch application from repository
        // For now, return sample data
        $application = [
            'id' => $id,
            'jobTitle' => 'Senior PHP Developer',
            'status' => 'PENDING',
            'submissionDate' => new \DateTime('-3 days'),
            'updatedAt' => new \DateTime('-1 day'),
            'coverLetter' => 'I am very interested in this position as it aligns perfectly with my skills and experience. I have been working with PHP for over 5 years, with extensive knowledge of modern frameworks like Laravel and Symfony. My experience includes building RESTful APIs, implementing authentication systems, and optimizing database performance.',
            'suggestedSalary' => 65000,
            'categorie' => 'Technology',
            'cvUrl' => 'https://example.com/cv/john-candidate.pdf',
            'candidateName' => 'John Candidate',
            'candidateEmail' => 'john.candidate@email.com',
            'jobOffer' => [
                'id' => 1,
                'title' => 'Senior PHP Developer',
                'location' => 'Tunis, Tunisia',
                'categoryName' => 'Technology',
                'recruiter' => [
                    'fullName' => 'John Doe',
                    'email' => 'john.doe@company.com'
                ]
            ],
            'interviews' => []
        ];
        
        return $this->render('applications/show.html.twig', [
            'application' => $application,
        ]);
    }

    #[Route('/events', name: 'app_events')]
    public function events(): Response
    {
        // This would typically fetch events from repository
        // For now, return sample data
        $events = [
            [
                'id' => 1,
                'name' => 'Tech Conference 2024',
                'location' => 'Tunis, Tunisia',
                'eventDate' => new \DateTime('+2 weeks'),
                'maxParticipants' => 100,
                'description' => 'Annual technology conference featuring the latest trends in web development, AI, and cloud computing.',
                'organizer' => ['fullName' => 'Tech Community'],
                'participants' => [
                    ['user' => ['fullName' => 'Alice Johnson']],
                    ['user' => ['fullName' => 'Bob Wilson']]
                ]
            ],
            [
                'id' => 2,
                'name' => 'PHP Workshop',
                'location' => 'Online',
                'eventDate' => new \DateTime('+1 month'),
                'maxParticipants' => 50,
                'description' => 'Hands-on workshop covering advanced PHP techniques and best practices.',
                'organizer' => ['fullName' => 'Dev Team'],
                'participants' => [
                    ['user' => ['fullName' => 'Charlie Brown']],
                    ['user' => ['fullName' => 'Diana Prince']]
                ]
            ]
        ];
        
        return $this->render('events/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/events/{id}', name: 'app_main_events_general_show')]
    public function showEvent(int $id): Response
    {
        // This would typically fetch event from repository
        // For now, return sample data
        $event = [
            'id' => $id,
            'name' => 'Tech Conference 2024',
            'location' => 'Tunis, Tunisia',
            'eventDate' => new \DateTime('+2 weeks'),
            'maxParticipants' => 100,
            'description' => 'Join us for the biggest technology conference of the year! This event brings together developers, designers, and tech enthusiasts from across the region to learn about the latest trends in web development, artificial intelligence, and cloud computing.',
            'organizer' => [
                'fullName' => 'Tech Community',
                'email' => 'info@techcommunity.com'
            ],
            'participants' => [
                ['user' => ['fullName' => 'Alice Johnson'], 'status' => 'CONFIRMED'],
                ['user' => ['fullName' => 'Bob Wilson'], 'status' => 'PENDING']
            ]
        ];
        
        return $this->render('events/show.html.twig', [
            'event' => $event,
            'isRegistered' => true // This would check if current user is registered
        ]);
    }

    #[Route('/main-complaints', name: 'app_complaints')]
    public function complaints(): Response
    {
        // This would typically fetch complaints from repository
        // For now, return sample data
        $complaints = [
            [
                'id' => 1,
                'subject' => 'Login Issue',
                'status' => 'OPEN',
                'submissionDate' => new \DateTime('-1 day'),
                'description' => 'Unable to login to my account. I have tried resetting my password but still cannot access.',
                'user' => ['fullName' => 'John User'],
                'type' => ['name' => 'Technical Issue', 'urgencyLevel' => 'High'],
                'responses' => []
            ],
            [
                'id' => 2,
                'subject' => 'Feature Request',
                'status' => 'RESOLVED',
                'submissionDate' => new \DateTime('-5 days'),
                'description' => 'Would like to request a dark mode feature for better usability.',
                'user' => ['fullName' => 'Jane User'],
                'type' => ['name' => 'Feature Request', 'urgencyLevel' => 'Medium'],
                'responses' => [
                    [
                        'message' => 'Thank you for your suggestion. We have added this to our development roadmap.',
                        'responseDate' => new \DateTime('-4 days'),
                        'adminName' => 'Admin User'
                    ]
                ]
            ]
        ];
        
        return $this->render('complaints/index.html.twig', [
            'complaints' => $complaints,
        ]);
    }

    #[Route('/main-complaints/{id}', name: 'app_complaints_show')]
    public function showComplaint(int $id): Response
    {
        // This would typically fetch complaint from repository
        // For now, return sample data
        $complaint = [
            'id' => $id,
            'subject' => 'Login Issue',
            'status' => 'OPEN',
            'submissionDate' => new \DateTime('-1 day'),
            'description' => 'I have been experiencing issues with my account login for the past two days. When I enter my correct credentials, the system shows an error message saying "Invalid credentials". I have tried resetting my password multiple times, but the issue persists. I need urgent access to my account as I have pending applications.',
            'user' => [
                'fullName' => 'John User',
                'email' => 'john.user@email.com'
            ],
            'type' => [
                'name' => 'Technical Issue',
                'urgencyLevel' => 'High'
            ],
            'responses' => []
        ];
        
        return $this->render('complaints/show.html.twig', [
            'complaint' => $complaint,
        ]);
    }

    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function profile(): Response
    {
        return $this->render('profile/index.html.twig');
    }

    #[Route('/admin', name: 'app_admin')]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(): Response
    {
        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/hr', name: 'app_hr')]
    #[IsGranted('ROLE_HR')]
    public function hr(): Response
    {
        return $this->redirectToRoute('app_hr_dashboard');
    }
}
