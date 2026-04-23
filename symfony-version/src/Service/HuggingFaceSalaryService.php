<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class HuggingFaceSalaryService
{
    private HttpClientInterface $httpClient;
    private ParameterBagInterface $parameterBag;

    public function __construct(
        HttpClientInterface $httpClient,
        ParameterBagInterface $parameterBag
    ) {
        $this->httpClient = $httpClient;
        $this->parameterBag = $parameterBag;
    }

    /**
     * Generate salary suggestion based on job details using rule-based approach
     */
    public function generateSalarySuggestion(string $jobTitle, string $jobDescription = '', string $location = '', string $experience = ''): array
    {
        try {
            return $this->generateRuleBasedSalary($jobTitle, $location, $experience);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to generate salary suggestion: ' . $e->getMessage());
        }
    }

    /**
     * Generate salary using rule-based approach with job title and location factors
     */
    private function generateRuleBasedSalary(string $jobTitle, string $location, string $experience): array
    {
        // Base salary ranges by job category
        $baseRanges = [
            'software' => ['min' => 60000, 'max' => 120000, 'avg' => 85000],
            'developer' => ['min' => 55000, 'max' => 110000, 'avg' => 80000],
            'engineer' => ['min' => 65000, 'max' => 130000, 'avg' => 90000],
            'manager' => ['min' => 70000, 'max' => 140000, 'avg' => 100000],
            'analyst' => ['min' => 50000, 'max' => 90000, 'avg' => 65000],
            'designer' => ['min' => 45000, 'max' => 85000, 'avg' => 60000],
            'marketing' => ['min' => 40000, 'max' => 80000, 'avg' => 55000],
            'sales' => ['min' => 35000, 'max' => 85000, 'avg' => 55000],
            'hr' => ['min' => 40000, 'max' => 75000, 'avg' => 55000],
            'finance' => ['min' => 50000, 'max' => 100000, 'avg' => 70000],
            'operations' => ['min' => 40000, 'max' => 80000, 'avg' => 55000],
            'default' => ['min' => 35000, 'max' => 70000, 'avg' => 50000]
        ];

        // Location multipliers
        $locationMultipliers = [
            'new york' => 1.4, 'san francisco' => 1.5, 'seattle' => 1.3, 'boston' => 1.2,
            'los angeles' => 1.2, 'chicago' => 1.1, 'austin' => 1.1, 'denver' => 1.0,
            'atlanta' => 0.9, 'miami' => 0.9, 'dallas' => 1.0, 'houston' => 0.9,
            'london' => 1.3, 'paris' => 1.2, 'berlin' => 1.1, 'toronto' => 1.0,
            'remote' => 1.0, 'anywhere' => 0.9, 'default' => 1.0
        ];

        // Experience multipliers
        $experienceMultipliers = [
            'junior' => 0.7, 'entry' => 0.6, 'intern' => 0.4, 'trainee' => 0.5,
            'mid' => 1.0, 'intermediate' => 1.0, 'associate' => 0.9,
            'senior' => 1.3, 'lead' => 1.4, 'principal' => 1.5, 'staff' => 1.4,
            'manager' => 1.5, 'director' => 1.7, 'head' => 1.8, 'vp' => 2.0,
            'cto' => 2.2, 'cfo' => 2.1, 'ceo' => 2.5, 'default' => 1.0
        ];

        // Determine base range from job title
        $baseRange = $baseRanges['default'];
        $jobTitleLower = strtolower($jobTitle);
        
        foreach ($baseRanges as $category => $range) {
            if ($category !== 'default' && strpos($jobTitleLower, $category) !== false) {
                $baseRange = $range;
                break;
            }
        }

        // Apply location multiplier
        $locationMultiplier = $locationMultipliers['default'];
        $locationLower = strtolower($location);
        
        foreach ($locationMultipliers as $loc => $multiplier) {
            if ($loc !== 'default' && strpos($locationLower, $loc) !== false) {
                $locationMultiplier = $multiplier;
                break;
            }
        }

        // Apply experience multiplier
        $experienceMultiplier = $experienceMultipliers['default'];
        $experienceLower = strtolower($experience);
        
        foreach ($experienceMultipliers as $exp => $multiplier) {
            if ($exp !== 'default' && strpos($experienceLower, $exp) !== false) {
                $experienceMultiplier = $multiplier;
                break;
            }
        }

        // Calculate final salaries
        $combinedMultiplier = $locationMultiplier * $experienceMultiplier;
        
        $minSalary = round($baseRange['min'] * $combinedMultiplier);
        $maxSalary = round($baseRange['max'] * $combinedMultiplier);
        $avgSalary = round($baseRange['avg'] * $combinedMultiplier);

        // Determine confidence based on how well we matched
        $confidence = 'medium';
        if (strpos($jobTitleLower, 'software') !== false || strpos($jobTitleLower, 'engineer') !== false) {
            $confidence = 'high';
        } elseif (strpos($jobTitleLower, 'manager') !== false || strpos($jobTitleLower, 'director') !== false) {
            $confidence = 'high';
        }

        return [
            'min_salary' => (float) $minSalary,
            'max_salary' => (float) $maxSalary,
            'average_salary' => (float) $avgSalary,
            'currency' => 'USD',
            'confidence' => $confidence,
            'raw_response' => "Rule-based calculation for {$jobTitle} in {$location}"
        ];
    }

    /**
     * Build a detailed prompt for salary generation
     */
    private function buildSalaryPrompt(string $jobTitle, string $jobDescription, string $location, string $experience): string
    {
        $prompt = "Generate salary estimate for: ";
        $prompt .= $jobTitle;
        
        if (!empty($location)) {
            $prompt .= " in " . $location;
        }
        
        if (!empty($experience)) {
            $prompt .= " (" . $experience . " level)";
        }
        
        $prompt .= ". Return JSON: {\"min_salary\": 45000, \"max_salary\": 65000, \"average_salary\": 55000, \"currency\": \"USD\", \"confidence\": \"high\"}";

        return $prompt;
    }

    /**
     * Parse the salary response from Hugging Face
     */
    private function parseSalaryResponse(string $response): array
    {
        // Try to extract JSON from the response
        $jsonPattern = '/\{[^}]+\}/';
        if (preg_match($jsonPattern, $response, $matches)) {
            $jsonStr = $matches[0];
            $data = json_decode($jsonStr, true);
            
            if (json_last_error() === JSON_ERROR_NONE && $data) {
                return [
                    'min_salary' => (float) ($data['min_salary'] ?? 0),
                    'max_salary' => (float) ($data['max_salary'] ?? 0),
                    'average_salary' => (float) ($data['average_salary'] ?? 0),
                    'currency' => $data['currency'] ?? 'USD',
                    'confidence' => $data['confidence'] ?? 'medium',
                    'raw_response' => $response
                ];
            }
        }

        // Fallback parsing if JSON extraction fails
        return $this->extractNumbersFromText($response);
    }

    /**
     * Fallback method to extract salary numbers from text
     */
    private function extractNumbersFromText(string $text): array
    {
        // Look for salary patterns like "$45,000 - $65,000"
        $patterns = [
            '/\$(\d{1,3}(,\d{3})*)(?:\s*-\s*\$(\d{1,3}(,\d{3})*))?/',
            '/(\d{1,3}(,\d{3})*)(?:\s*-\s*(\d{1,3}(,\d{3})*))?/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $min = isset($matches[1]) ? (float) str_replace(',', '', $matches[1]) : 0;
                $max = isset($matches[2]) ? (float) str_replace(',', '', $matches[2]) : $min * 1.2;
                $avg = ($min + $max) / 2;

                return [
                    'min_salary' => $min,
                    'max_salary' => $max,
                    'average_salary' => $avg,
                    'currency' => 'USD',
                    'confidence' => 'low',
                    'raw_response' => $text
                ];
            }
        }

        // Ultimate fallback
        return [
            'min_salary' => 35000.0,
            'max_salary' => 55000.0,
            'average_salary' => 45000.0,
            'currency' => 'USD',
            'confidence' => 'very_low',
            'raw_response' => $text
        ];
    }

    /**
     * Check if the service is available
     */
    public function isAvailable(): bool
    {
        return true;
    }
}
