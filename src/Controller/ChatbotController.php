<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;

class ChatbotController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $client,
        private EntityManagerInterface $em
    ) {}

    #[Route('/chatbot', name: 'chatbot_ask', methods: ['POST'])]
    public function ask(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $userMessage = $data['message'] ?? '';

            // Get jobs from DB
           $jobs = $this->em->getRepository(\App\Entity\JobOffer::class)->findAll();
            $jobList = '';
            foreach ($jobs as $job) {
                $jobList .= '- ' . $job->getTitle() . "\n"
                 . ' | Type: ' . $job->getJobType() 
                 . ' | Location: ' . $job->getLocation() 
                 . ' | Salary: ' . ($job->getSalaryRange() ?? 'Not specified')
                 . ' | Description: ' . substr($job->getDescription(), 0, 100) . "...\n";

            }

            $response = $this->client->request('POST',
                'https://api.groq.com/openai/v1/chat/completions',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . ($_ENV['GROQ_API_KEY'] ?? 'your_groq_api_key_here'),
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => 'llama-3.3-70b-versatile',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'You are SmartHire\'s smart career assistant. You help users with:
- Job offers: explaining job details, requirements, salary, location
- Interview preparation: common questions, how to answer them, body language, dress code
- Career advice: choosing the right job, salary negotiation, career growth 
Here are the current job offers available:
' . $jobList . '
You help candidates prepare for interviews for these specific jobs.
Only answer interview-related questions.'
                            ],
                            [
                                'role' => 'user',
                                'content' => $userMessage
                            ]
                        ],
                        'max_tokens' => 1024,
                    ],
                ]
            );

            $result = $response->toArray(false);
            $reply = $result['choices'][0]['message']['content'] ?? 'API Error: ' . json_encode($result);

            return $this->json(['reply' => $reply]);

        } catch (\Exception $e) {
            return $this->json(['reply' => 'Error: ' . $e->getMessage()]);
        }
    }
}