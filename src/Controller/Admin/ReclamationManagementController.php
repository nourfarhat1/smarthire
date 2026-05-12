<?php

namespace App\Controller\Admin;

use App\Entity\Complaint;
use App\Entity\Response as ComplaintResponse;
use App\Entity\ComplaintType as ComplaintTypeEntity;
use App\Form\ComplaintType;
use App\Repository\ComplaintRepository;
use App\Repository\ComplaintTypeRepository;
use App\Repository\ResponseRepository;
use App\Service\AIService;
use App\Service\TranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/reclamations')]
#[IsGranted('ROLE_ADMIN')]
class ReclamationManagementController extends AbstractController
{
    public function __construct(
        private ComplaintRepository $complaintRepository,
        private ComplaintTypeRepository $complaintTypeRepository,
        private ResponseRepository $responseRepository,
        private AIService $aiService,
        private TranslationService $translationService,
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    #[Route('/', name: 'app_admin_reclamations')]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        $type = $request->query->get('type', '');
        
        // Build query with filters
        $qb = $this->complaintRepository->createQueryBuilder('c')
            ->leftJoin('c.type', 't')
            ->leftJoin('c.user', 'u')
            ->orderBy('c.submissionDate', 'DESC');

        // Apply search filter
        if (!empty($search)) {
            $qb->andWhere('c.subject LIKE :search OR c.description LIKE :search OR u.fullName LIKE :search')
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
        
        // Get statistics for analytics
        $stats = [
            'total' => count($complaints),
            'pending' => count(array_filter($complaints, fn($c) => $c->getStatus() === 'PENDING')),
            'open' => count(array_filter($complaints, fn($c) => $c->getStatus() === 'OPEN')),
            'resolved' => count(array_filter($complaints, fn($c) => $c->getStatus() === 'RESOLVED')),
            'by_type' => [],
            'by_priority' => [
                'high' => count(array_filter($complaints, fn($c) => $c->getPriority() === 'HIGH')),
                'medium' => count(array_filter($complaints, fn($c) => $c->getPriority() === 'MEDIUM')),
                'low' => count(array_filter($complaints, fn($c) => $c->getPriority() === 'LOW')),
            ]
        ];
        
        // Count by type
        foreach ($complaints as $complaint) {
            $typeName = $complaint->getType()?->getName() ?? 'Unknown';
            if (!isset($stats['by_type'][$typeName])) {
                $stats['by_type'][$typeName] = 0;
            }
            $stats['by_type'][$typeName]++;
        }
        
        return $this->render('admin/reclamations/index.html.twig', [
            'complaints' => $complaints,
            'complaintTypes' => $this->complaintTypeRepository->findAll(),
            'stats' => $stats,
            'search' => $search,
            'selectedStatus' => $status,
            'selectedType' => $type,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_reclamations_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $complaint = $this->complaintRepository->find($id);
        if (!$complaint) {
            $this->addFlash('error', 'Complaint not found.');
            return $this->redirectToRoute('app_admin_reclamations');
        }
        
        $responses = $this->responseRepository->findBy(['complaint' => $complaint], ['responseDate' => 'DESC']);
        $complaintTypes = $this->complaintTypeRepository->findAll();
        
        return $this->render('admin/reclamations/show.html.twig', [
            'complaint' => $complaint,
            'responses' => $responses,
            'complaint_types' => $complaintTypes,
        ]);

    }

    #[Route('/{id}/update-type', name: 'app_admin_reclamations_update_type', methods: ['POST'])]
    public function updateType(Request $request, Complaint $complaint): Response
    {
        // Check if the form was submitted
        if (!$request->request->has('submit_type_update')) {
            error_log('Form not submitted properly - missing submit button');
            return $this->redirectToRoute('app_admin_reclamations_show', ['id' => $complaint->getId()]);
        }
        
        $typeId = $request->request->get('type');
        $newTypeName = $request->request->get('newType');
        $complaintId = $request->request->get('complaint_id');
        
        // Debug logging
        error_log('=== UPDATE TYPE METHOD CALLED ===');
        error_log('Complaint ID: ' . $complaint->getId());
        error_log('Complaint ID from form: ' . $complaintId);
        error_log('Request Method: ' . $request->getMethod());
        error_log('Type ID: ' . $typeId);
        error_log('New Type Name: ' . $newTypeName);
        error_log('All POST data: ' . print_r($request->request->all(), true));
        
        try {
            if ($newTypeName && !empty(trim($newTypeName))) {
                // Create new complaint type
                $newType = new ComplaintTypeEntity();
                $newType->setName(trim($newTypeName));
                $this->complaintTypeRepository->save($newType, true);
                $complaint->setType($newType);
                error_log('Created new type: ' . $newTypeName);
            } elseif ($typeId) {
                // Use existing complaint type
                $type = $this->complaintTypeRepository->find($typeId);
                if ($type) {
                    $complaint->setType($type);
                    error_log('Set existing type: ' . $type->getName());
                } else {
                    error_log('Invalid complaint type selected: ' . $typeId);
                    $this->addFlash('error', 'Invalid complaint type selected.');
                    return $this->redirectToRoute('app_admin_reclamations_show', ['id' => $complaint->getId()]);
                }
            } else {
                // Remove type (set to null)
                $complaint->setType(null);
                error_log('Removed type from complaint');
            }
            
            $this->complaintRepository->save($complaint, true);
            $this->addFlash('success', 'Complaint type updated successfully!');
            error_log('Complaint type updated successfully');
            
        } catch (\Exception $e) {
            error_log('Error updating complaint type: ' . $e->getMessage());
            error_log('Exception trace: ' . $e->getTraceAsString());
            $this->addFlash('error', 'Error updating complaint type: ' . $e->getMessage());
        }
        
        error_log('Redirecting to complaint show page');
        return $this->redirectToRoute('app_admin_reclamations_show', ['id' => $complaint->getId()]);
    }

    #[Route('/{id}/respond', name: 'app_admin_reclamations_respond', methods: ['GET', 'POST'])]
    public function respond(Request $request, Complaint $complaint): Response
    {
        if ($request->isMethod('POST')) {
            $message = $request->request->get('message');
            
            if (!empty($message)) {
                $response = new ComplaintResponse();
                $response->setComplaint($complaint);
                $response->setMessage($message);
                $response->setResponseDate(new \DateTime());
                $this->responseRepository->save($response, true);
                
                // Update complaint status to RESOLVED
                $complaint->setStatus('RESOLVED');
                $this->complaintRepository->save($complaint, true);
                
                $this->addFlash('success', 'Response added successfully!');
                return $this->redirectToRoute('app_admin_reclamations_show', ['id' => $complaint->getId()]);
            } else {
                $this->addFlash('error', 'Please enter a response message.');
            }
        }

        return $this->render('admin/reclamations/respond.html.twig', [
            'complaint' => $complaint,
        ]);
    }

    #[Route('/{id}/resolve', name: 'app_admin_reclamations_resolve', methods: ['POST'])]
    public function resolve(Request $request, Complaint $complaint): Response
    {
        if ($this->isCsrfTokenValid('resolve' . $complaint->getId(), $request->request->get('_token'))) {
            $complaint->setStatus('RESOLVED');
            $this->complaintRepository->save($complaint, true);
            $this->addFlash('success', 'Complaint marked as resolved!');
        }

        return $this->redirectToRoute('app_admin_reclamations');
    }

    #[Route('/{id}/reopen', name: 'app_admin_reclamations_reopen', methods: ['POST'])]
    public function reopen(Request $request, Complaint $complaint): Response
    {
        if ($this->isCsrfTokenValid('reopen' . $complaint->getId(), $request->request->get('_token'))) {
            $complaint->setStatus('OPEN');
            $this->complaintRepository->save($complaint, true);
            $this->addFlash('success', 'Complaint reopened!');
        }

        return $this->redirectToRoute('app_admin_reclamations');
    }

    protected function isCsrfTokenValid(string $id, ?string $token): bool
    {
        return $this->csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken($id, $token));
    }

    #[Route('/{id}/summarize', name: 'app_admin_reclamations_summarize', methods: ['POST'])]
    public function summarizeComplaint(Complaint $complaint): JsonResponse
    {
        try {
            $summary = $this->aiService->summarizeText($complaint->getDescription());
            
            return new JsonResponse([
                'success' => true,
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    #[Route('/{id}/translate', name: 'app_admin_reclamations_translate', methods: ['POST'])]
    public function translateComplaint(Complaint $complaint): JsonResponse
    {
        try {
            $text = $complaint->getSubject() . '. ' . $complaint->getDescription();
            $result = $this->translationService->translateWithDetection($text, 'en');
            
            return new JsonResponse([
                'success' => true,
                'translation' => $result['translation'],
                'detectedLanguage' => $result['detectedLanguage']
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    #[Route('/{id}/ai-response', name: 'app_admin_reclamations_ai_response', methods: ['POST'])]
    public function generateAIResponse(Complaint $complaint): JsonResponse
    {
        try {
            $complaintText = $complaint->getSubject() . '. ' . $complaint->getDescription();
            $aiResponse = $this->aiService->generateComplaintResponse($complaintText);
            
            return new JsonResponse([
                'success' => true,
                'response' => $aiResponse['suggestedResponse'],
                'urgency' => $aiResponse['urgency'] ?? 'Medium',
                'sentiment' => $aiResponse['sentiment'] ?? 'Neutral'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    #[Route('/response/{id}/edit', name: 'app_admin_reclamations_edit_response', methods: ['GET', 'POST'])]
    public function editResponse(Request $request, ComplaintResponse $response): Response
    {
        if ($request->isMethod('POST')) {
            $message = $request->request->get('message');
            
            if (!empty($message)) {
                $response->setMessage($message);
                $response->setResponseDate(new \DateTime());
                $this->responseRepository->save($response, true);
                
                $this->addFlash('success', 'Response updated successfully!');
                return $this->redirectToRoute('app_admin_reclamations_show', ['id' => $response->getComplaint()->getId()]);
            } else {
                $this->addFlash('error', 'Please enter a response message.');
            }
        }

        return $this->render('admin/reclamations/edit_response.html.twig', [
            'response' => $response,
        ]);
    }

    #[Route('/response/{id}/delete', name: 'app_admin_reclamations_delete_response', methods: ['POST'])]
    public function deleteResponse(Request $request, ComplaintResponse $response): Response
    {
        $complaintId = $response->getComplaint()->getId();
        
        if ($this->isCsrfTokenValid('delete_response' . $response->getId(), $request->request->get('_token'))) {
            $this->responseRepository->remove($response, true);
            $this->addFlash('success', 'Response deleted successfully!');
        }

        return $this->redirectToRoute('app_admin_reclamations_show', ['id' => $complaintId]);
    }

    #[Route('/analytics', name: 'app_admin_reclamations_analytics')]
    public function analytics(): Response
    {
        $complaints = $this->complaintRepository->findAll();
        
        // Calculate detailed analytics
        $analytics = [
            'total' => count($complaints),
            'by_status' => [
                'PENDING' => count(array_filter($complaints, fn($c) => $c->getStatus() === 'PENDING')),
                'OPEN' => count(array_filter($complaints, fn($c) => $c->getStatus() === 'OPEN')),
                'RESOLVED' => count(array_filter($complaints, fn($c) => $c->getStatus() === 'RESOLVED')),
                'CLOSED' => count(array_filter($complaints, fn($c) => $c->getStatus() === 'CLOSED')),
            ],
            'by_priority' => [
                'HIGH' => count(array_filter($complaints, fn($c) => $c->getPriority() === 'HIGH')),
                'MEDIUM' => count(array_filter($complaints, fn($c) => $c->getPriority() === 'MEDIUM')),
                'LOW' => count(array_filter($complaints, fn($c) => $c->getPriority() === 'LOW')),
            ],
            'by_type' => [],
            'monthly_trends' => [],
            'avg_resolution_time' => 0,
        ];

        // Count by type
        foreach ($complaints as $complaint) {
            $typeName = $complaint->getType()?->getName() ?? 'Unknown';
            if (!isset($analytics['by_type'][$typeName])) {
                $analytics['by_type'][$typeName] = 0;
            }
            $analytics['by_type'][$typeName]++;
        }

        // Calculate monthly trends
        $monthlyData = [];
        foreach ($complaints as $complaint) {
            $month = $complaint->getSubmissionDate()->format('Y-m');
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = 0;
            }
            $monthlyData[$month]++;
        }
        $analytics['monthly_trends'] = $monthlyData;

        return $this->render('admin/reclamations/analytics.html.twig', [
            'analytics' => $analytics,
        ]);
    }
}
