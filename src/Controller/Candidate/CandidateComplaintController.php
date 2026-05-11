<?php

namespace App\Controller\Candidate;

use App\Entity\Complaint;
use App\Entity\ComplaintType;
use App\Repository\ComplaintRepository;
use App\Repository\ComplaintTypeRepository;
use App\Service\ProfanityFilterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/candidate/complaints')]
#[IsGranted('ROLE_CANDIDATE')]
class CandidateComplaintController extends AbstractController
{
    public function __construct(
        private ComplaintRepository $complaintRepository,
        private ComplaintTypeRepository $complaintTypeRepository,
        private ProfanityFilterService $profanityFilter
    ) {
    }

    #[Route('/', name: 'app_candidate_complaints')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        $type = $request->query->get('type', '');

        // Get candidate user complaints with filtering
        $qb = $this->complaintRepository->createQueryBuilder('c')
            ->leftJoin('c.type', 't')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.submissionDate', 'DESC');

        // Apply search filter
        if (!empty($search)) {
            $qb->andWhere('c.subject LIKE :search OR c.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Apply status filter
        if (!empty($status)) {
            $qb->andWhere('c.status = :status')
               ->setParameter('status', $status);
        }

        // Apply type filter
        if (!empty($type)) {
            $qb->andWhere('t.id = :type')
               ->setParameter('type', $type);
        }

        $complaints = $qb->getQuery()->getResult();

        // Get complaint types for filter dropdown
        $complaintTypes = $this->complaintTypeRepository->findAll();

        // Calculate statistics
        $pendingCount = count(array_filter($complaints, fn($c) => $c->getStatus() === 'PENDING'));
        $openCount = count(array_filter($complaints, fn($c) => $c->getStatus() === 'OPEN'));
        $resolvedCount = count(array_filter($complaints, fn($c) => $c->getStatus() === 'RESOLVED'));

        return $this->render('candidate/complaints/index.html.twig', [
            'complaints' => $complaints,
            'complaintTypes' => $complaintTypes,
            'pendingCount' => $pendingCount,
            'openCount' => $openCount,
            'resolvedCount' => $resolvedCount,
            'search' => $search,
            'selectedStatus' => $status,
            'selectedType' => $type,
        ]);
    }

    #[Route('/new', name: 'app_candidate_complaints_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        $complaint = new Complaint();
        $complaint->setUser($user);
        
        $types = $this->complaintTypeRepository->findAll();
        
        if ($request->isMethod('POST')) {
            $subject = $request->request->get('subject');
            $description = $request->request->get('description');
            $typeId = $request->request->get('type');
            
            // Check for bad words using profanity filter
            $badWords = ['fuck', 'shit', 'damn', 'hell', 'bitch', 'ass', 'crap', 'merde', 'putain', 'connard', 'bastard', 'idiot'];
            $hasBadWord = false;
            $detectedWords = [];
            
            foreach ($badWords as $word) {
                if (stripos($subject . ' ' . $description, $word) !== false) {
                    $hasBadWord = true;
                    $detectedWords[] = $word;
                }
            }
            
            // Use profanity filter service as well
            $subjectAnalysis = $this->profanityFilter->analyzeProfanity($subject);
            $descriptionAnalysis = $this->profanityFilter->analyzeProfanity($description);
            
            if ($hasBadWord || $subjectAnalysis['containsProfanity'] || $descriptionAnalysis['containsProfanity']) {
                $this->addFlash('error', 'Your complaint contains inappropriate language. Please remove profanity and try again.');
                
                // Filter the bad words for display
                $filteredSubject = $subject;
                $filteredDescription = $description;
                foreach ($detectedWords as $word) {
                    $filteredSubject = str_ireplace($word, str_repeat('*', strlen($word)), $filteredSubject);
                    $filteredDescription = str_ireplace($word, str_repeat('*', strlen($word)), $filteredDescription);
                }
                
                return $this->render('candidate/complaints/new.html.twig', [
                    'types' => $types,
                    'filteredSubject' => $filteredSubject,
                    'filteredDescription' => $filteredDescription,
                    'profanityWords' => array_unique($detectedWords)
                ]);
            }
            
            // Basic validation
            if (empty($subject) || empty($description) || empty($typeId)) {
                $this->addFlash('error', 'Please fill in all required fields.');
                return $this->render('candidate/complaints/new.html.twig', [
                    'types' => $types,
                ]);
            }
            
            if (strlen($description) < 20) {
                $this->addFlash('error', 'Description must be at least 20 characters.');
                return $this->render('candidate/complaints/new.html.twig', [
                    'types' => $types,
                ]);
            }
            
            // Set complaint data
            $complaint->setSubject($subject);
            $complaint->setDescription($description);
            $complaint->setStatus('PENDING');
            $complaint->setSubmissionDate(new \DateTime());
            $complaint->setPriority('MEDIUM');
            
            // Set complaint type
            $type = $this->complaintTypeRepository->find($typeId);
            if ($type) {
                $complaint->setType($type);
            }

            // Save to database
            $this->complaintRepository->save($complaint, true);
            $this->addFlash('success', 'Your complaint has been submitted successfully! We will review it and get back to you soon.');

            return $this->redirectToRoute('app_candidate_complaints_show', ['id' => $complaint->getId()]);
        }

        return $this->render('candidate/complaints/new.html.twig', [
            'types' => $types,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_candidate_complaints_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Complaint $complaint): Response
    {
        // Check if the user owns this complaint
        if ($complaint->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only edit your own complaints.');
        }

        // Check if the complaint is still pending (candidates can only edit pending complaints)
        if ($complaint->getStatus() !== 'PENDING') {
            throw $this->createAccessDeniedException('You can only edit pending complaints.');
        }

        $types = $this->complaintTypeRepository->findAll();

        if ($request->isMethod('POST')) {
            $subject = $request->request->get('subject');
            $description = $request->request->get('description');
            $typeId = $request->request->get('type');
            
            // Check for bad words
            $badWords = ['fuck', 'shit', 'damn', 'hell', 'bitch', 'ass', 'crap', 'merde', 'putain', 'connard', 'bastard', 'idiot'];
            $hasBadWord = false;
            $detectedWords = [];
            
            foreach ($badWords as $word) {
                if (stripos($subject . ' ' . $description, $word) !== false) {
                    $hasBadWord = true;
                    $detectedWords[] = $word;
                }
            }
            
            // Use profanity filter service as well
            $subjectAnalysis = $this->profanityFilter->analyzeProfanity($subject);
            $descriptionAnalysis = $this->profanityFilter->analyzeProfanity($description);
            
            if ($hasBadWord || $subjectAnalysis['containsProfanity'] || $descriptionAnalysis['containsProfanity']) {
                $this->addFlash('error', 'Your complaint contains inappropriate language. Please remove profanity and try again.');
                
                // Filter the bad words for display
                $filteredSubject = $subject;
                $filteredDescription = $description;
                foreach ($detectedWords as $word) {
                    $filteredSubject = str_ireplace($word, str_repeat('*', strlen($word)), $filteredSubject);
                    $filteredDescription = str_ireplace($word, str_repeat('*', strlen($word)), $filteredDescription);
                }
                
                return $this->render('candidate/complaints/edit.html.twig', [
                    'complaint' => $complaint,
                    'types' => $types,
                    'filteredSubject' => $filteredSubject,
                    'filteredDescription' => $filteredDescription,
                    'profanityWords' => array_unique($detectedWords)
                ]);
            }
            
            // Basic validation
            if (empty($subject) || empty($description) || empty($typeId)) {
                $this->addFlash('error', 'Please fill in all required fields.');
                return $this->redirectToRoute('app_candidate_complaints_edit', ['id' => $complaint->getId()]);
            }
            
            if (strlen($description) < 20) {
                $this->addFlash('error', 'Description must be at least 20 characters.');
                return $this->redirectToRoute('app_candidate_complaints_edit', ['id' => $complaint->getId()]);
            }
            
            // Update complaint data
            $complaint->setSubject($subject);
            $complaint->setDescription($description);
            
            // Update complaint type
            $type = $this->complaintTypeRepository->find($typeId);
            if ($type) {
                $complaint->setType($type);
            }

            // Save to database
            $this->complaintRepository->save($complaint, true);
            $this->addFlash('success', 'Your complaint has been updated successfully!');

            return $this->redirectToRoute('app_candidate_complaints_show', ['id' => $complaint->getId()]);
        }

        return $this->render('candidate/complaints/edit.html.twig', [
            'complaint' => $complaint,
            'types' => $types,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_candidate_complaints_delete', methods: ['POST'])]
    public function delete(Request $request, Complaint $complaint): Response
    {
        // Check if the user owns this complaint
        if ($complaint->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only delete your own complaints.');
        }

        // Check if the complaint is still pending (candidates can only delete pending complaints)
        if ($complaint->getStatus() !== 'PENDING') {
            throw $this->createAccessDeniedException('You can only delete pending complaints.');
        }

        $this->complaintRepository->remove($complaint, true);
        $this->addFlash('success', 'Your complaint has been deleted successfully!');

        return $this->redirectToRoute('app_candidate_complaints');
    }

    #[Route('/{id}', name: 'app_candidate_complaints_show')]
    public function show(Complaint $complaint): Response
    {
        // Check if the user owns this complaint
        if ($complaint->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only view your own complaints.');
        }

        return $this->render('candidate/complaints/show.html.twig', [
            'complaint' => $complaint,
        ]);
    }
}
