<?php

namespace App\Service;

use App\Entity\JobOffer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AlgoliaService
{
    private $entityManager;
    private $appId;
    private $adminKey;
    private $searchKey;
    private $httpClient;

    public function __construct(
        EntityManagerInterface $entityManager,
        HttpClientInterface $httpClient
    ) {
        $this->entityManager = $entityManager;
        $this->httpClient = $httpClient;
        $this->appId = $_ENV['ALGOLIA_APP_ID'] ?? 'YOUR_APP_ID';
        $this->adminKey = $_ENV['ALGOLIA_ADMIN_KEY'] ?? 'YOUR_ADMIN_API_KEY';
        $this->searchKey = $_ENV['ALGOLIA_SEARCH_KEY'] ?? 'YOUR_SEARCH_ONLY_KEY';
        
        // Set up synonyms for enhanced typo tolerance
        $this->setupSynonyms();
    }

    /**
     * Set up synonyms for enhanced typo tolerance using Symfony HttpClient
     */
    public function setupSynonyms(): void
    {
        try {
            $synonyms = [
                // Job title synonyms
                [
                    'objectID' => 'developer_synonym',
                    'type' => 'synonym',
                    'synonyms' => ['developer', 'dev', 'programmer', 'engineer', 'coder', 'software engineer', 'developr']
                ],
                [
                    'objectID' => 'designer_synonym',
                    'type' => 'synonym',
                    'synonyms' => ['designer', 'designr', 'graphic designer', 'ui designer', 'ux designer']
                ],
                [
                    'objectID' => 'manager_synonym',
                    'type' => 'synonym',
                    'synonyms' => ['manager', 'mgr', 'team lead', 'supervisor', 'coordinator', 'mangr']
                ],
                [
                    'objectID' => 'graph_synonym',
                    'type' => 'synonym',
                    'synonyms' => ['graph', 'grph','grpha','grphb','grphc','grhp']
                ],
                [
                    'objectID' => 'analyst_synonym',
                    'type' => 'synonym',
                    'synonyms' => ['analyst', 'analystr', 'data analyst', 'business analyst']
                ],
                // Job type synonyms
                [
                    'objectID' => 'senior_synonym',
                    'type' => 'synonym',
                    'synonyms' => ['senior', 'sr', 'lead', 'principal']
                ],
                [
                    'objectID' => 'junior_synonym',
                    'type' => 'synonym',
                    'synonyms' => ['junior', 'jr', 'entry level', 'associate']
                ],
                // Technology synonyms
                [
                    'objectID' => 'javascript_synonym',
                    'type' => 'synonym',
                    'synonyms' => ['javascript', 'js', 'java script', 'javascrpt']
                ],
                [
                    'objectID' => 'python_synonym',
                    'type' => 'synonym',
                    'synonyms' => ['python', 'py', 'pyton']
                ],
                // Location synonyms
                [
                    'objectID' => 'remote_synonym',
                    'type' => 'synonym',
                    'synonyms' => ['remote', 'work from home', 'wfh', 'telecommute', 'remot']
                ],
                [
                    'objectID' => 'office_synonym',
                    'type' => 'synonym',
                    'synonyms' => ['office', 'onsite', 'in office', 'on-site', 'onsite']
                ]
            ];

            // Send all synonyms to Algolia using PUT with objectID
            foreach ($synonyms as $synonym) {
                $this->sendToAlgolia(
                    '/1/indexes/job_offers/synonyms/' . $synonym['objectID'],
                    'PUT',
                    $synonym
                );
            }

            error_log("Synonyms configured for enhanced typo tolerance using Symfony HttpClient");

        } catch (\Exception $e) {
            error_log("Failed to setup synonyms: " . $e->getMessage());
        }
    }

    /**
     * Index a single job offer using official Algolia client
     */
    public function indexJob(JobOffer $job): bool
    {
        try {
            $jobData = [
                'objectID' => $job->getId(),
                'title' => $job->getTitle(),
                'description' => $job->getDescription(),
                'location' => $job->getLocation(),
                'salary' => $job->getSalaryRange(),
                'job_type' => $job->getJobType(),
                'posted_date' => $job->getPostedDate()->format('Y-m-d'),
                'category' => $job->getCategory() ? $job->getCategory()->getName() : 'Not specified',
                'recruiter' => $job->getRecruiter() ? $job->getRecruiter()->getFullName() : 'Not specified'
            ];

            // Use official Algolia client
            $this->index->saveObject($jobData);
            
            error_log("Job indexed: " . $job->getTitle() . " (ID: " . $job->getId() . ")");
            return true;

        } catch (\Exception $e) {
            error_log("Failed to index job: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Index all existing jobs using official Algolia client
     */
    public function indexAllJobs(): int
    {
        try {
            $jobs = $this->entityManager->getRepository(JobOffer::class)->findAll();
            $jobData = [];
            $indexedCount = 0;

            foreach ($jobs as $job) {
                $jobData[] = [
                    'objectID' => $job->getId(),
                    'title' => $job->getTitle(),
                    'description' => $job->getDescription(),
                    'location' => $job->getLocation(),
                    'salary' => $job->getSalaryRange(),
                    'job_type' => $job->getJobType(),
                    'posted_date' => $job->getPostedDate()->format('Y-m-d'),
                    'category' => $job->getCategory() ? $job->getCategory()->getName() : 'Not specified',
                    'recruiter' => $job->getRecruiter() ? $job->getRecruiter()->getFullName() : 'Not specified'
                ];
                $indexedCount++;
            }

            // Batch index all jobs for better performance
            $this->index->saveObjects($jobData);

            error_log("Indexed {$indexedCount} jobs to Algolia using official client");
            return $indexedCount;

        } catch (\Exception $e) {
            error_log("Failed to index all jobs: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Search jobs with filters and enhanced typo tolerance
     */
    public function searchJobs(string $query, array $filters = [], int $limit = 20): array
    {
        // Check if Algolia is properly configured
        if (!$this->isAlgoliaConfigured()) {
            error_log("Algolia not configured, falling back to database search");
            return $this->searchDatabase($query, $filters, $limit);
        }

        try {
            // First try with enhanced typo tolerance
            $enhancedResponse = $this->performSearch($query, $filters, $limit, 'strict');
            
            if ($enhancedResponse && isset($enhancedResponse['hits']) && count($enhancedResponse['hits']) > 0) {
                error_log("Enhanced search for '{$query}' returned " . count($enhancedResponse['hits']) . " results");
                return $enhancedResponse;
            }

            // If no results, try with normal tolerance
            error_log("No results with strict tolerance, trying normal tolerance for '{$query}'");
            $normalResponse = $this->performSearch($query, $filters, $limit, 'normal');
            
            if ($normalResponse && isset($normalResponse['hits']) && count($normalResponse['hits']) > 0) {
                error_log("Normal tolerance search for '{$query}' returned " . count($normalResponse['hits']) . " results");
                return $normalResponse;
            }

            // If still no results, try with stupid tolerance (maximum typo tolerance)
            error_log("No results with normal tolerance, trying stupid tolerance for '{$query}'");
            $stupidResponse = $this->performSearch($query, $filters, $limit, 'stupid');
            
            if ($stupidResponse && isset($stupidResponse['hits']) && count($stupidResponse['hits']) > 0) {
                error_log("Stupid tolerance search for '{$query}' returned " . count($stupidResponse['hits']) . " results");
                return $stupidResponse;
            }

            // Last resort: try fuzzy search with all words optional
            error_log("No results with any tolerance, trying fuzzy search for '{$query}'");
            $fuzzyResponse = $this->performSearch($query, $filters, $limit, 'strict', true);
            
            if ($fuzzyResponse && isset($fuzzyResponse['hits'])) {
                error_log("Fuzzy search for '{$query}' returned " . count($fuzzyResponse['hits']) . " results");
                return $fuzzyResponse;
            }

            error_log("No results found for '{$query}' with any search method");
            return ['hits' => [], 'nbHits' => 0];

        } catch (\Exception $e) {
            error_log("Enhanced search failed: " . $e->getMessage());
            return ['hits' => [], 'nbHits' => 0];
        }
    }

    /**
     * Check if Algolia is properly configured
     */
    private function isAlgoliaConfigured(): bool
    {
        $appId = $this->appId ?? null;
        $adminKey = $this->adminKey ?? null;
        
        return $appId && $appId !== 'YOUR_APP_ID' && $adminKey && $adminKey !== 'YOUR_ADMIN_API_KEY';
    }

    /**
     * Fallback database search when Algolia is not available
     */
    private function searchDatabase(string $query, array $filters = [], int $limit = 20): array
    {
        try {
            $jobRepository = $this->entityManager->getRepository('App\Entity\JobOffer');
            
            // Basic database search
            $qb = $jobRepository->createQueryBuilder('j');
            
            if (!empty($query)) {
                $qb->where('j.title LIKE :query OR j.description LIKE :query')
                   ->setParameter('query', '%' . $query . '%');
            }
            
            $qb->setMaxResults($limit);
            $jobs = $qb->getQuery()->getResult();
            
            // Convert to Algolia-like format
            $hits = [];
            foreach ($jobs as $job) {
                $hits[] = [
                    'objectID' => $job->getId(),
                    'id' => $job->getId(),
                    'title' => $job->getTitle(),
                    'description' => $job->getDescription(),
                    'location' => $job->getLocation(),
                    'salary' => $job->getSalaryRange(),
                    'job_type' => $job->getJobType(),
                    'posted_date' => $job->getPostedDate()->format('Y-m-d'),
                    'category' => $job->getCategory() ? $job->getCategory()->getName() : 'Not specified',
                    'recruiter' => $job->getRecruiter() ? $job->getRecruiter()->getFullName() : 'Not specified',
                    '_highlightResult' => [
                        'title' => ['value' => $job->getTitle()],
                        'description' => ['value' => substr($job->getDescription(), 0, 150)]
                    ]
                ];
            }
            
            error_log("Database search for '{$query}' returned " . count($hits) . " results");
            return ['hits' => $hits, 'nbHits' => count($hits)];
            
        } catch (\Exception $e) {
            error_log("Database search failed: " . $e->getMessage());
            return ['hits' => [], 'nbHits' => 0];
        }
    }

    /**
     * Perform search with specific tolerance level using cURL
     */
    private function performSearch(string $query, array $filters, int $limit, string $toleranceLevel, bool $fuzzy = false): array
    {
        $searchParams = [
            'query' => $query,
            'hitsPerPage' => $limit,
            'attributesToHighlight' => ['title', 'description'],
            'attributesToSnippet' => ['description:50'],
            'typoTolerance' => [
                'enabled' => true,
                'minWordSizeForTypos' => 2, // Lowered from 3 for more tolerance
                'typoToleranceLevel' => $toleranceLevel
            ],
            'removeStopWords' => false,
            'ignorePlurals' => false,
            'alternativesAsExact' => true,
            'advancedSyntax' => true,
            'optionalWords' => [
                'senior', 'junior', 'lead', 'manager', 'remote', 'hybrid', 'onsite', 'full-time', 'part-time', 'contract', 'freelance',
                'developer', 'engineer', 'programmer', 'designer', 'manager', 'analyst', 'coordinator', 'specialist'
            ],
            'queryType' => $fuzzy ? 'prefixAll' : 'prefixLast',
            'removeWordsIfNoResults' => $fuzzy ? 'allOptional' : 'none'
        ];

        if (!empty($filters)) {
            $searchParams['filters'] = implode(' AND ', $filters);
        }

        return $this->sendToAlgolia('/1/indexes/job_offers/query', 'POST', $searchParams);
    }

    /**
     * Send request to Algolia API using Symfony HttpClient
     */
    private function sendToAlgolia(string $endpoint, string $method, array $data): ?array
    {
        $url = 'https://' . $this->appId . '-dsn.algolia.net' . $endpoint;

        $headers = [
            'Content-Type' => 'application/json',
            'X-Algolia-API-Key' => $this->adminKey
        ];

        try {
            $options = [];
            
            if ($method === 'POST') {
                $options['json'] = $data;
                $response = $this->httpClient->request('POST', $url, $options);
            } elseif ($method === 'PUT') {
                $options['json'] = $data;
                $response = $this->httpClient->request('PUT', $url, $options);
            } elseif ($method === 'GET') {
                $response = $this->httpClient->request('GET', $url, $options);
            } else {
                $options['json'] = $data;
                $response = $this->httpClient->request($method, $url, $options);
            }

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200 || $statusCode === 201) {
                return json_decode($response->getContent(), true);
            }

            error_log("Algolia API request failed. HTTP {$statusCode}: " . $response->getContent());
            return null;

        } catch (\Exception $e) {
            error_log("Algolia API request exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete job from index using official Algolia client
     */
    public function deleteJob(int $jobId): bool
    {
        try {
            $this->index->deleteObject($jobId);
            
            error_log("Job deleted from Algolia: ID {$jobId}");
            return true;

        } catch (\Exception $e) {
            error_log("Failed to delete job from Algolia: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get search analytics
     */
    public function getSearchAnalytics(): array
    {
        try {
            $response = $this->sendToAlgolia('/1/searches', 'GET', []);
            
            if ($response) {
                return $response;
            }

            return [];

        } catch (\Exception $e) {
            error_log("Failed to get search analytics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get popular searches for graph visualization
     */
    public function getPopularSearches(): array
    {
        try {
            $response = $this->sendToAlgolia('/1/searches/popular', 'GET', []);
            
            if ($response) {
                return $response;
            }

            return [];

        } catch (\Exception $e) {
            error_log("Failed to get popular searches: " . $e->getMessage());
            return [];
        }
    }
}
