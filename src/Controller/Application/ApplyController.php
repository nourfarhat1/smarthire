<?php

namespace App\Controller\Application;

use App\Entity\JobRequest;
use App\Entity\JobOffer;
use App\Repository\JobRequestRepository;
use App\Repository\JobOfferRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/apply')]
#[IsGranted('ROLE_USER')]
class ApplyController extends AbstractController
{
    public function __construct(
        private JobRequestRepository $jobRequestRepository,
        private JobOfferRepository $jobOfferRepository
    ) {
    }

    #[Route('/job/{id}', name: 'app_apply_job', methods: ['GET', 'POST'])]
    public function applyToJob(Request $request, JobOffer $jobOffer): Response
    {
        $user = $this->getUser();

        // Check if user has already applied
        if ($this->jobRequestRepository->hasUserApplied($jobOffer->getId(), $user->getId())) {
            $this->addFlash('warning', 'You have already applied to this job.');
            return $this->redirectToRoute('app_jobs_show', ['id' => $jobOffer->getId()]);
        }

        if ($request->isMethod('POST')) {
            $jobRequest = new JobRequest();
            $jobRequest->setCandidate($user);
            $jobRequest->setJobOffer($jobOffer);
            $jobRequest->setSubmissionDate(new \DateTime());
            $jobRequest->setStatus('PENDING');
            $jobRequest->setJobTitle($jobOffer->getTitle());
            $jobRequest->setLocation($jobOffer->getLocation());
            $jobRequest->setCategorie($jobOffer->getCategoryName());

            // Handle form data
            $coverLetter = $request->request->get('cover_letter');
            $suggestedSalary = $request->request->get('suggested_salary');
            $cvFile = $request->files->get('cv');

            if ($coverLetter) {
                $jobRequest->setCoverLetter($coverLetter);
            }

            if ($suggestedSalary) {
                $jobRequest->setSuggestedSalary($suggestedSalary);
            }

            // Handle CV upload
            if ($cvFile) {
                $filename = uniqid() . '.' . $cvFile->guessExtension();
                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/cvs/';
                $cvFile->move($uploadsDir, $filename);
                $jobRequest->setCvUrl('/uploads/cvs/' . $filename);
            }

            $this->jobRequestRepository->save($jobRequest, true);
            $this->addFlash('success', 'Your application has been submitted successfully!');

            return $this->redirectToRoute('app_applications_general');
        }

        return $this->render('application/apply.html.twig', [
            'job' => $jobOffer,
        ]);
    }

    #[Route('/spontaneous', name: 'app_apply_spontaneous', methods: ['GET', 'POST'])]
    public function spontaneousApplication(Request $request): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $jobRequest = new JobRequest();
            $jobRequest->setCandidate($user);
            $jobRequest->setSubmissionDate(new \DateTime());
            $jobRequest->setStatus('PENDING');
            $jobRequest->setJobTitle('Spontaneous Application');
            $jobRequest->setCategorie($request->request->get('category'));
            $jobRequest->setSuggestedSalary($request->request->get('suggested_salary'));
            $jobRequest->setCoverLetter($request->request->get('cover_letter'));

            // Handle CV upload
            $cvFile = $request->files->get('cv');
            if ($cvFile) {
                $filename = uniqid() . '.' . $cvFile->guessExtension();
                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/cvs/';
                $cvFile->move($uploadsDir, $filename);
                $jobRequest->setCvUrl('/uploads/cvs/' . $filename);
            }

            $this->jobRequestRepository->save($jobRequest, true);
            $this->addFlash('success', 'Your spontaneous application has been submitted successfully!');

            return $this->redirectToRoute('app_applications_general');
        }

        return $this->render('application/spontaneous.html.twig');
    }
}
