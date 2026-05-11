<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OllamaService
{
    private HttpClientInterface $httpClient;
    private string $ollamaUrl;
    private string $model;

    public function __construct(HttpClientInterface $httpClient, string $ollamaUrl = 'http://localhost:11434', string $model = 'llama2')
    {
        $this->httpClient = $httpClient;
        $this->ollamaUrl = $ollamaUrl;
        $this->model = $model;
    }

    /**
     * Send a prompt to Ollama and get the response
     */
    public function generate(string $prompt, array $options = []): string
    {
        $payload = [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => array_merge([
                'temperature' => 0.1,
                'top_p' => 0.9,
                'max_tokens' => 1000
            ], $options)
        ];

        try {
            $response = $this->httpClient->request('POST', $this->ollamaUrl . '/api/generate', [
                'json' => $payload,
                'timeout' => 30
            ]);

            $data = $response->toArray();
            return $data['response'] ?? 'No response generated';
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to communicate with Ollama: ' . $e->getMessage());
        }
    }

    /**
     * Check if Ollama is available
     */
    public function isAvailable(): bool
    {
        try {
            $response = $this->httpClient->request('GET', $this->ollamaUrl . '/api/tags', [
                'timeout' => 5
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get available models
     */
    public function getModels(): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->ollamaUrl . '/api/tags', [
                'timeout' => 10
            ]);
            $data = $response->toArray();
            return $data['models'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
