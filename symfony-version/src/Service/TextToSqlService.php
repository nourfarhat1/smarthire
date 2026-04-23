<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Persistence\ManagerRegistry;

class TextToSqlService
{
    private OllamaService $ollamaService;
    private Connection $connection;
    private AbstractSchemaManager $schemaManager;

    public function __construct(
        OllamaService $ollamaService,
        ManagerRegistry $doctrine
    ) {
        $this->ollamaService = $ollamaService;
        $this->connection = $doctrine->getConnection();
        $this->schemaManager = $this->connection->createSchemaManager();
    }

    /**
     * Convert natural language to SQL query
     */
    public function naturalLanguageToSql(string $question, string $currentUserId): array
    {
        // Get table schema
        $schema = $this->getTableSchema('job_request');
        
        // Build the prompt for Ollama
        $prompt = $this->buildSqlPrompt($question, $schema, $currentUserId);
        
        // Get SQL from Ollama
        $sqlResponse = $this->ollamaService->generate($prompt);
        
        // Extract SQL from response
        $sql = $this->extractSqlFromResponse($sqlResponse);
        
        // Validate and sanitize SQL
        $validatedSql = $this->validateAndSanitizeSql($sql);
        
        return [
            'sql' => $validatedSql,
            'original_question' => $question,
            'schema_used' => $schema
        ];
    }

    /**
     * Execute the generated SQL safely
     */
    public function executeSql(string $sql): array
    {
        try {
            // Execute with read-only connection
            $stmt = $this->connection->executeQuery($sql);
            $results = $stmt->fetchAllAssociative();
            
            return [
                'success' => true,
                'data' => $results,
                'row_count' => count($results)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get table schema information
     */
    private function getTableSchema(string $tableName): array
    {
        $table = $this->schemaManager->listTableDetails($tableName);
        
        $schema = [
            'table_name' => $tableName,
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
    }

    /**
     * Build prompt for SQL generation
     */
    private function buildSqlPrompt(string $question, array $schema, string $currentUserId): string
    {
        $schemaDescription = $this->formatSchemaForPrompt($schema);
        
        return <<<PROMPT
You are a SQL expert. Generate a SQL query to answer this question: "{$question}"

Database Schema:
{$schemaDescription}

Important Rules:
1. Only use SELECT queries - NO INSERT, UPDATE, DELETE, DROP, etc.
2. Filter results for candidate_id = {$currentUserId} when applicable
3. Use proper table and column names from the schema
4. Return ONLY the SQL query, no explanations
5. Handle date formatting properly
6. Use COUNT(), SUM(), AVG() for aggregations when needed

Question: {$question}

Generate the SQL query:
PROMPT;
    }

    /**
     * Format schema for prompt
     */
    private function formatSchemaForPrompt(array $schema): string
    {
        $description = "Table: {$schema['table_name']}\nColumns:\n";
        
        foreach ($schema['columns'] as $column) {
            $nullable = $column['nullable'] ? 'NULL' : 'NOT NULL';
            $description .= "- {$column['name']} ({$column['type']}) {$nullable}\n";
        }
        
        return $description;
    }

    /**
     * Extract SQL from LLM response
     */
    private function extractSqlFromResponse(string $response): string
    {
        // Remove code blocks and extra text
        $sql = preg_replace('/```sql\s*/i', '', $response);
        $sql = preg_replace('/```\s*/', '', $sql);
        $sql = trim($sql);
        
        // Extract first SQL statement
        if (preg_match('/(SELECT\s+.*?)(?:;|$)/is', $sql, $matches)) {
            return $matches[1];
        }
        
        return $sql;
    }

    /**
     * Validate and sanitize SQL for security
     */
    private function validateAndSanitizeSql(string $sql): string
    {
        $sql = trim($sql);
        
        // Only allow SELECT statements
        if (!preg_match('/^\s*SELECT\s+/i', $sql)) {
            throw new \InvalidArgumentException('Only SELECT queries are allowed');
        }
        
        // Block dangerous keywords
        $dangerousKeywords = [
            'DROP', 'DELETE', 'UPDATE', 'INSERT', 'CREATE', 'ALTER',
            'TRUNCATE', 'EXEC', 'EXECUTE', 'UNION', 'INTO', 'OUTFILE'
        ];
        
        foreach ($dangerousKeywords as $keyword) {
            if (preg_match("/\b{$keyword}\b/i", $sql)) {
                throw new \InvalidArgumentException("Dangerous keyword '{$keyword}' not allowed");
            }
        }
        
        // Remove semicolons to prevent multiple statements
        $sql = preg_replace('/;.*$/', '', $sql);
        
        return $sql;
    }

    /**
     * Generate natural language response from query results
     */
    public function generateNaturalLanguageResponse(string $question, array $results): string
    {
        if (empty($results)) {
            return "No results found for your question: {$question}";
        }

        $resultCount = count($results);
        $firstResult = $results[0];
        
        // Build context for response generation
        $context = "Question: {$question}\n";
        $context .= "Results: {$resultCount} records found\n";
        $context .= "Sample data: " . json_encode($firstResult) . "\n";
        
        $prompt = <<<PROMPT
Based on the database query results, provide a natural language answer to the user's question.

{$context}

Provide a clear, conversational response. If there are multiple results, summarize them.
PROMPT;

        return $this->ollamaService->generate($prompt);
    }
}
