<?php

namespace App\Controller;

use App\Service\OllamaCloudService;
use App\Repository\JobRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatController extends AbstractController
{
    private OllamaCloudService $ollamaCloudService;
    private JobRequestRepository $jobRequestRepository;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        OllamaCloudService $ollamaCloudService,
        JobRequestRepository $jobRequestRepository,
        TokenStorageInterface $tokenStorage
    ) {
        $this->ollamaCloudService = $ollamaCloudService;
        $this->jobRequestRepository = $jobRequestRepository;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Send question to local Llama 3.2 model
     */
    #[Route('/api/chat', name: 'api_chat_query', methods: ['POST'])]
    #[IsGranted('ROLE_CANDIDATE')]
    public function query(Request $request, HttpClientInterface $httpClient): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userPrompt = $data['prompt'] ?? '';

        if (empty($userPrompt)) {
            return new JsonResponse(['error' => 'Prompt is empty'], 400);
        }

        try {
            // Get current user and their real data
            $user = $this->getUser();
            $totalJobRequests = $this->jobRequestRepository->count([]);
            $userJobRequests = $this->jobRequestRepository->findBy(['candidate' => $user]);
            $userJobRequestCount = count($userJobRequests);
            
            // Count by status for the current user
            $pendingCount = 0;
            $acceptedCount = 0;
            $rejectedCount = 0;
            
            foreach ($userJobRequests as $jobRequest) {
                switch ($jobRequest->getStatus()) {
                    case 'PENDING':
                        $pendingCount++;
                        break;
                    case 'ACCEPTED':
                        $acceptedCount++;
                        break;
                    case 'REJECTED':
                        $rejectedCount++;
                        break;
                }
            }

            // Build comprehensive prompt with real data
            $databaseContext = "You are a SmartHire database assistant. Here is the current user's data:\n";
            $databaseContext .= "- User: {$user->getFirstName()} {$user->getLastName()}\n";
            $databaseContext .= "- Total job requests in system: {$totalJobRequests}\n";
            $databaseContext .= "- User's job requests: {$userJobRequestCount}\n";
            $databaseContext .= "- User's pending applications: {$pendingCount}\n";
            $databaseContext .= "- User's accepted applications: {$acceptedCount}\n";
            $databaseContext .= "- User's rejected applications: {$rejectedCount}\n";
            $databaseContext .= "\nUser question: {$userPrompt}\n";
            $databaseContext .= "Provide a helpful response based on this real data.";

            // No 'headers' or 'Authorization' needed for local models!
            $response = $httpClient->request('POST', 'http://localhost:11434/api/generate', [
                'json' => [
                    'model' => 'llama3.2',
                    'prompt' => $databaseContext,
                    'stream' => false,
                ],
                'timeout' => 30, // Local is usually faster than Cloud
            ]);

            $result = $response->toArray();
            
            return new JsonResponse([
                'answer' => $result['response'] ?? 'AI returned an empty response.',
                'data_used' => [
                    'total_requests' => $totalJobRequests,
                    'user_requests' => $userJobRequestCount,
                    'pending' => $pendingCount,
                    'accepted' => $acceptedCount,
                    'rejected' => $rejectedCount
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Local AI Connection failed: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/chat/status', name: 'api_chat_status', methods: ['GET'])]
    #[IsGranted('ROLE_CANDIDATE')]
    public function chatStatus(): JsonResponse
    {
        try {
            $isAvailable = $this->ollamaCloudService->isAvailable();
            $models = $this->ollamaCloudService->getModels();

            return new JsonResponse([
                'ollama_available' => $isAvailable,
                'models' => $models,
                'status' => $isAvailable ? 'online' : 'offline',
                'current_model' => 'llama3.2' // Updated status indicator
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to check Ollama status: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/chat', name: 'app_chat_interface', methods: ['GET'])]
    #[IsGranted('ROLE_CANDIDATE')]
    public function chatInterface(): Response
    {
        return $this->render('candidate/chat/index.html.twig');
    }
}