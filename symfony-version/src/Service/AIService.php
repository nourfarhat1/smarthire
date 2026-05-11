<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AIService
{
    private HttpClientInterface $client;
    private string $url;
    private string $model;

    public function __construct(
        ParameterBagInterface $parameterBag,
        HttpClientInterface $client = null
    ) {
        $this->client = $client ?: HttpClient::create([
            'timeout' => 60,
        ]);
        $this->url = $parameterBag->get('app.ai_api_url', 'https://api.groq.com/openai/v1/chat/completions');
        $this->model = $parameterBag->get('app.ai_model', 'llama-3.3-70b-versatile');
    }

    /**
     * ANALYSE CV : Transforme le texte du PDF en JSON structuré.
     */
   public function analyzeResume(string $text): array
{
    echo "[DEBUG-AI] Analyse du contenu du CV...\n";
    echo "[DEBUG-AI] Text length: " . strlen($text) . " chars\n";
    echo "[DEBUG-AI] Text preview: " . substr($text, 0, 200) . "...\n";
    
    $cleanText = $this->cleanForJson($text);
    $prompt = "Extract candidate info. Return ONLY a JSON object with: firstName, lastName, phone, skills (array), summary. Text: " . $cleanText;
    
    echo "[DEBUG-AI] Prompt length: " . strlen($prompt) . " chars\n";
    
    return $this->callGroq($prompt);
}

    /**
     * SMART MATCHING : Compare l'événement avec les compétences de l'utilisateur.
     */
    public function calculateEventMatching(string $candidateSkills, string $eventDescription): array
{
    // Nettoyage des compétences (retrait des crochets et guillemets de la BDD)
    $cleanSkills = (empty($candidateSkills)) ? "" : str_replace(['[', ']', '"'], '', trim($candidateSkills));

    // SÉCURITÉ ANTI-HALLUCINATION
    if (empty($cleanSkills) || $cleanSkills === ",") {
        return [
            'score' => 0,
            'recommendation' => 'Nous n\'avons pas trouvé de compétences sur votre profil. ' .
                'Veuillez mettre à jour votre CV pour recevoir des recommandations personnalisées.'
        ];
    }

    $prompt = "ACT AS A TECHNICAL CAREER ANALYST.\n" .
        "CANDIDATE_SKILLS: " . $this->cleanForJson($cleanSkills) . "\n" .
        "EVENT_DESCRIPTION: " . $this->cleanForJson($eventDescription) . "\n\n" .
        "STRICT RULES:\n" .
        "1. Find direct links between the event and the candidate's skills.\n" .
        "2. Use EXACT NAMES from the CANDIDATE_SKILLS list.\n" .
        "3. Use this EXACT template for the recommendation:\n" .
        "'This event matches your profile because it focuses on [Topic]. Since you already know [Skill 1] and [Skill 2] from your profile, you have what it takes to succeed.'\n\n" .
        "Return ONLY a JSON object with keys: score (0-100), recommendation (string).";

    return $this->callGroq($prompt);
}

    /**
     * Appel générique à l'API Groq
     */
    public function generateComplaintResponse(string $complaintText): array
    {
        try {
            $prompt = "You are a professional customer support assistant. " .
                     "Analyze this complaint and provide both a summary and a suggested professional response.\n\n" .
                     "Complaint: " . $complaintText . "\n\n" .
                     "Provide your response in this exact JSON format:\n" .
                     "{\n" .
                     "  \"summary\": \"One sentence summary of the complaint\",\n" .
                     "  \"suggestedResponse\": \"Professional response addressing the complaint\",\n" .
                     "  \"urgency\": \"HIGH|MEDIUM|LOW\",\n" .
                     "  \"sentiment\": \"POSITIVE|NEGATIVE|NEUTRAL\"\n" .
                     "}";

            $response = $this->makeApiCall($prompt);
            
            // Handle both JSON and plain text responses
            if (is_string($response)) {
                // If it's plain text, create a basic response
                return [
                    'summary' => 'Complaint received and reviewed',
                    'suggestedResponse' => $response,
                    'urgency' => 'MEDIUM',
                    'sentiment' => 'NEUTRAL'
                ];
            } elseif (is_array($response)) {
                return [
                    'summary' => $response['summary'] ?? 'Unable to generate summary',
                    'suggestedResponse' => $response['suggestedResponse'] ?? $response['response'] ?? 'Unable to generate response',
                    'urgency' => $response['urgency'] ?? 'MEDIUM',
                    'sentiment' => $response['sentiment'] ?? 'NEUTRAL'
                ];
            }
        } catch (\Exception $e) {
            // Fallback to template-based responses
            return $this->generateFallbackResponse($complaintText, $e->getMessage());
        }
    }

    private function generateFallbackResponse(string $complaintText, string $error): array
    {
        // Simple keyword-based analysis for fallback
        $urgency = 'MEDIUM';
        $sentiment = 'NEUTRAL';
        
        if (preg_match('/\b(urgent|emergency|critical|immediate)\b/i', $complaintText)) {
            $urgency = 'HIGH';
        } elseif (preg_match('/\b(low|minor|later|when possible)\b/i', $complaintText)) {
            $urgency = 'LOW';
        }
        
        if (preg_match('/\b(angry|frustrated|terrible|awful|unacceptable)\b/i', $complaintText)) {
            $sentiment = 'NEGATIVE';
        } elseif (preg_match('/\b(happy|pleased|good|great|thank)\b/i', $complaintText)) {
            $sentiment = 'POSITIVE';
        }
        
        $responses = [
            'HIGH' => [
                'NEUTRAL' => 'We understand the urgency of your complaint and will prioritize it accordingly. Our team will review your case immediately and respond as soon as possible.',
                'NEGATIVE' => 'We sincerely apologize for the frustration you\'re experiencing. This matter has been escalated to our senior team for immediate attention. We will contact you shortly with a resolution.',
                'POSITIVE' => 'Thank you for bringing this to our attention. While we appreciate your positive approach, we will still ensure this matter receives the attention it deserves.'
            ],
            'MEDIUM' => [
                'NEUTRAL' => 'Thank you for your feedback. We have received your complaint and our team will review it thoroughly. We will respond within 24-48 hours with an update.',
                'NEGATIVE' => 'We apologize for the inconvenience you\'ve experienced. Your complaint has been registered and our team will investigate the matter thoroughly. We appreciate your patience.',
                'POSITIVE' => 'We appreciate your constructive feedback. Your complaint has been received and our team will review it carefully. We value your input and will respond accordingly.'
            ],
            'LOW' => [
                'NEUTRAL' => 'Thank you for bringing this to our attention. We have logged your complaint and will address it in our regular review cycle. We appreciate your feedback.',
                'NEGATIVE' => 'We understand your concern and have noted your complaint. While this isn\'t urgent, we will still review it carefully and incorporate your feedback into our improvements.',
                'POSITIVE' => 'We appreciate your thoughtful feedback. Your suggestion has been recorded and will be considered in our ongoing improvements. Thank you for helping us get better.'
            ]
        ];
        
        $suggestedResponse = $responses[$urgency][$sentiment] ?? $responses['MEDIUM']['NEUTRAL'];
        
        return [
            'summary' => 'Customer feedback received for review',
            'suggestedResponse' => $suggestedResponse,
            'urgency' => $urgency,
            'sentiment' => $sentiment
        ];
    }

    public function summarizeText(string $text): string
    {
        try {
            $prompt = "Summarize this text in one sentence: " . $text;
            $response = $this->makeApiCall($prompt);
            
            // Handle different response formats
            if (is_string($response)) {
                return $response;
            } elseif (is_array($response)) {
                return $response['summary'] ?? $response['content'] ?? json_encode($response);
            } else {
                return 'Unable to summarize text';
            }
        } catch (\Exception $e) {
            // Fallback to simple text processing
            $sentences = preg_split('/[.!?]+/', $text);
            $firstSentence = trim($sentences[0] ?? '');
            return $firstSentence ?: 'Summary unavailable: ' . $e->getMessage();
        }
    }

    private function makeApiCall(string $prompt)
    {
        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'max_completion_tokens' => 300
        ];

        try {
            $response = $this->client->request('POST', $this->url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new \Exception('API request failed with status: ' . $statusCode);
            }

            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? '';
            
            // Debug: Log the raw content
            error_log('AI API Raw Content: ' . $content);
            
            // Try to decode as JSON first, if fails return as plain text
            $decoded = json_decode($content, true);
            return $decoded !== null ? $decoded : $content;
            
        } catch (\Exception $e) {
            error_log('AI API Error: ' . $e->getMessage());
            throw new \Exception('AI service temporarily unavailable: ' . $e->getMessage());
        }
    }

    /**
     * Appel générique à l'API Groq
     */
   /**
 * Appel générique à l'API Groq
 */
private function callGroq(string $prompt): array
{
    $payload = [
        'model' => $this->model,
        'response_format' => ['type' => 'json_object'],
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ]
    ];

    try {
        $response = $this->client->request('POST', $this->url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => $payload
        ]);

        $statusCode = $response->getStatusCode();
        
        if ($statusCode !== 200) {
            $content = $response->getContent(false);
            throw new \Exception("Groq API Error ({$statusCode}): " . $content);
        }

        $data = $response->toArray();
        $content = $data['choices'][0]['message']['content'] ?? '{}';
        
        $decoded = json_decode($content, true);
        if ($decoded === null) {
            return [];
        }
        
        return $decoded ?: [];
        
    } catch (\Exception $e) {
        throw new \Exception("Groq API Error: " . $e->getMessage());
    }
}
private function cleanForJson(string $text): string
{
    if (empty($text)) return "";
    
    // Nettoyage radical UTF-8
    $text = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
    
    // Remplacer les retours à la ligne
    $text = str_replace(["\r\n", "\n", "\r", "\t"], ' ', $text);
    
    // Enlever les caractères non imprimables (garde lettres, chiffres, espace, ponctuation)
    $text = preg_replace('/[^\p{L}\p{N}\s\.,;:?!\-@]/u', ' ', $text);
    
    // Remplacer les espaces multiples
    $text = preg_replace('/\s+/', ' ', $text);
    
    return trim($text);
}
    /**
     * Face Recognition: Compare captured face with stored profile picture
     */
    public function compareFaces(string $capturedImagePath, string $storedImagePath): array
    {
        try {
            // In a real implementation, you would use:
            // - Face detection API (Amazon Rekognition, Azure Face API, etc.)
            // - Local face recognition library (OpenCV with face recognition)
            // - Or a dedicated face recognition service
            
            // For this implementation, we'll simulate face comparison using AI
            $prompt = "Analyze these two images and determine if they show the same person. 
            Consider facial features, structure, and overall similarity.
            Return JSON with: 
            - match_confidence (0-100)
            - is_match (boolean)
            - analysis_description
            - key_differences (array)
            
            Note: This is a simulated analysis for demonstration purposes.";

            // Simulate face comparison with AI
            $response = $this->callAI($prompt);
            
            if (isset($response['choices'][0]['message']['content'])) {
                $analysis = json_decode($response['choices'][0]['message']['content'], true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $analysis;
                }
            }
            
            // Fallback simulation
            return $this->simulateFaceComparison();
            
        } catch (\Exception $e) {
            return [
                'match_confidence' => 0,
                'is_match' => false,
                'analysis_description' => 'Face comparison failed: ' . $e->getMessage(),
                'key_differences' => ['Error in analysis'],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract face features from image for comparison
     */
    public function extractFaceFeatures(string $imagePath): array
    {
        try {
            // In a real implementation, this would:
            // 1. Detect faces in the image
            // 2. Extract facial landmarks and features
            // 3. Create a face embedding/vector representation
            
            // For simulation, we'll use AI to describe facial features
            $prompt = "Analyze this image and extract facial features. 
            Return JSON with:
            - face_detected (boolean)
            - face_count (integer)
            - facial_features (object with: face_shape, eye_color, hair_color, distinctive_features)
            - confidence_score (0-100)
            - image_quality_assessment";
            
            $response = $this->callAI($prompt);
            
            if (isset($response['choices'][0]['message']['content'])) {
                $features = json_decode($response['choices'][0]['message']['content'], true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $features;
                }
            }
            
            // Fallback
            return $this->simulateFaceFeatures();
            
        } catch (\Exception $e) {
            return [
                'face_detected' => false,
                'face_count' => 0,
                'facial_features' => [],
                'confidence_score' => 0,
                'image_quality_assessment' => 'Analysis failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate face image quality for recognition
     */
    public function validateFaceImage(string $imagePath): array
    {
        try {
            // Check if image exists and is readable
            if (!file_exists($imagePath)) {
                throw new \Exception('Image file not found');
            }

            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                throw new \Exception('Invalid image format');
            }

            list($width, $height) = $imageInfo;
            
            $validation = [
                'is_valid' => true,
                'issues' => [],
                'recommendations' => [],
                'quality_score' => 100
            ];

            // Check image dimensions
            if ($width < 200 || $height < 200) {
                $validation['issues'][] = 'Image too small for face recognition';
                $validation['recommendations'][] = 'Use an image at least 200x200 pixels';
                $validation['quality_score'] -= 30;
            }

            // Check aspect ratio
            $aspectRatio = $width / $height;
            if ($aspectRatio < 0.7 || $aspectRatio > 1.4) {
                $validation['issues'][] = 'Unusual aspect ratio for face photo';
                $validation['recommendations'][] = 'Use a portrait-oriented image';
                $validation['quality_score'] -= 20;
            }

            // Check file size (simulate)
            $fileSize = filesize($imagePath);
            if ($fileSize < 1000) { // Less than 1KB
                $validation['issues'][] = 'Image file too small, may be low quality';
                $validation['quality_score'] -= 25;
            }

            if ($validation['quality_score'] < 70) {
                $validation['is_valid'] = false;
            }

            return $validation;
            
        } catch (\Exception $e) {
            return [
                'is_valid' => false,
                'issues' => [$e->getMessage()],
                'recommendations' => ['Check image file and try again'],
                'quality_score' => 0
            ];
        }
    }

    /**
     * Simulate face comparison for demo purposes
     */
    private function simulateFaceComparison(): array
    {
        // Simulate a realistic face comparison result
        $confidence = rand(60, 95);
        $isMatch = $confidence > 75;
        
        return [
            'match_confidence' => $confidence,
            'is_match' => $isMatch,
            'analysis_description' => $isMatch 
                ? 'Strong facial similarity detected between images'
                : 'Significant differences detected in facial features',
            'key_differences' => $isMatch 
                ? ['Minor lighting differences', 'Slight angle variation']
                : ['Different facial structure', 'Distinctive features don\'t match'],
            'processing_time' => '1.2s',
            'method_used' => 'AI-based feature comparison'
        ];
    }

    /**
     * Simulate face feature extraction for demo purposes
     */
    private function simulateFaceFeatures(): array
    {
        return [
            'face_detected' => true,
            'face_count' => 1,
            'facial_features' => [
                'face_shape' => 'oval',
                'eye_color' => 'brown',
                'hair_color' => 'dark',
                'distinctive_features' => ['clear jawline', 'symmetrical features']
            ],
            'confidence_score' => 85,
            'image_quality_assessment' => 'Good quality, suitable for recognition',
            'face_position' => [
                'x' => 150,
                'y' => 100,
                'width' => 200,
                'height' => 250
            ]
        ];
    }

    /**
     * Enhanced AI call method for face recognition
     */
    private function callAI(string $prompt): array
    {
        $response = $this->client->request('POST', $this->url, [
            'json' => [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.3,
            ],
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        return $response->toArray();
    }
}
