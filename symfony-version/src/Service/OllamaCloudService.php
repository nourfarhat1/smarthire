<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class OllamaCloudService
{
    private HttpClientInterface $httpClient;
    private ParameterBagInterface $parameterBag;
    private Connection $connection;
    private AbstractSchemaManager $schemaManager;

    public function __construct(
        HttpClientInterface $httpClient,
        ParameterBagInterface $parameterBag,
        ManagerRegistry $doctrine
    ) {
        $this->httpClient = $httpClient;
        $this->parameterBag = $parameterBag;
        $this->connection = $doctrine->getConnection();
        $this->schemaManager = $this->connection->createSchemaManager();
    }

    /**
     * Send a message to kimi-k2.5:cloud model with database schema context
     */
    public function sendMessage(string $userMessage): string
    {
        // Get database schema for context
        $databaseSchema = $this->getDatabaseSchema();
        
        // Build the complete prompt with schema context
        $fullPrompt = $this->buildPromptWithSchema($userMessage, $databaseSchema);

        // Debug: Log request details
        error_log('OllamaCloudService: Sending request to kimi-k2.5:cloud');
        error_log('OllamaCloudService: Prompt length: ' . strlen($fullPrompt));

        try {
            $response = $this->httpClient->request('POST', 'http://localhost:11434/api/generate', [
                'json' => [
                    'model' => 'kimi-k2.5:cloud',
                    'stream' => false,
                    'prompt' => $fullPrompt,
                    'options' => [
                        'temperature' => 0.1,
                        'top_p' => 0.9
                    ]
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Host' => 'localhost:11434',
                    'Origin' => 'http://127.0.0.1:8000',
                    'Referer' => 'http://127.0.0.1:8000'
                ],
                'timeout' => 45
            ]);

            // Debug: Log the full request
            $requestHeaders = [
                'Content-Type' => 'application/json',
                'Host' => 'localhost:11434',
                'Origin' => 'http://127.0.0.1:8000',
                'Referer' => 'http://127.0.0.1:8000'
            ];
            error_log('OllamaCloudService: Request headers: ' . json_encode($requestHeaders));

            $data = $response->toArray();
            error_log('OllamaCloudService: Response status: ' . $response->getStatusCode());
            return $data['response'] ?? 'No response received from kimi-k2.5:cloud';

        } catch (\Exception $e) {
            error_log('OllamaCloudService: Exception: ' . $e->getMessage());
            throw new \RuntimeException('Failed to communicate with Ollama Cloud: ' . $e->getMessage());
        }
    }

    /**
     * Fetch job_requests table schema
     */
    private function getDatabaseSchema(): array
    {
        try {
            $table = $this->schemaManager->listTableDetails('job_requests');
            
            $schema = [
                'table_name' => 'job_requests',
                'columns' => []
            ];

            foreach ($table->getColumns() as $column) {
                $schema['columns'][] = [
                    'name' => $column->getName(),
                    'type' => $column->getType()->getName(),
                    'length' => $column->getLength(),
                    'nullable' => !$column->getNotnull(),
                    'default' => $column->getDefault()
                ];
            }

            return $schema;
        } catch (\Exception $e) {
            // Fallback schema if table doesn't exist
            return [
                'table_name' => 'job_requests',
                'columns' => [
                    ['name' => 'id', 'type' => 'integer', 'nullable' => false],
                    ['name' => 'status', 'type' => 'string', 'nullable' => false]
                ]
            ];
        }
    }

    /**
     * Build prompt with database schema context
     */
    private function buildPromptWithSchema(string $userMessage, array $schema): string
    {
        $schemaDescription = $this->formatSchemaForPrompt($schema);
        
        $systemPrompt = "You are a helpful database assistant. Use the following database schema to answer questions about job requests:\n\n";
        $systemPrompt .= $schemaDescription;
        $systemPrompt .= "\n\nBased on this schema, answer the user's question. Provide clear, helpful responses.\n\n";
        $systemPrompt .= "Question: " . $userMessage;
        
        return $systemPrompt;
    }

    /**
     * Format database schema for prompt
     */
    private function formatSchemaForPrompt(array $schema): string
    {
        $description = "Database Schema:\n";
        $description .= "Table: {$schema['table_name']}\n";
        $description .= "Columns:\n";
        
        foreach ($schema['columns'] as $column) {
            $nullable = $column['nullable'] ? 'NULL' : 'NOT NULL';
            $description .= "- {$column['name']} ({$column['type']}) {$nullable}\n";
        }
        
        $description .= "\nCommon status values: 'PENDING', 'ACCEPTED', 'REJECTED', 'INTERVIEWING'\n";
        
        return $description;
    }

    /**
     * Check if Ollama service is available
     */
    public function isAvailable(): bool
    {
        try {
            $response = $this->httpClient->request('GET', 'http://localhost:11434/api/tags', [
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
            $response = $this->httpClient->request('GET', 'http://localhost:11434/api/tags', [
                'timeout' => 10
            ]);
            $data = $response->toArray();
            return $data['models'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
