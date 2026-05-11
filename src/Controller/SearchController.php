<?php

namespace App\Controller;

use App\Repository\JobOfferRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    public function __construct(
        private JobOfferRepository $jobOfferRepository
    ) {
    }

    #[Route('/api/search/jobs', name: 'api_search_jobs', methods: ['GET'])]
    public function searchJobs(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = min($request->query->get('limit', 8), 20); // Max 20 results

        if (strlen($query) < 2) {
            return new JsonResponse(['hits' => []]);
        }

        try {
            $qb = $this->jobOfferRepository->createQueryBuilder('j')
                ->where('LOWER(j.title) LIKE LOWER(:query) OR LOWER(j.description) LIKE LOWER(:query)')
                ->setParameter('query', '%' . $query . '%')
                ->orderBy('j.postedDate', 'DESC')
                ->setMaxResults($limit);

            $jobs = $qb->getQuery()->getResult();
            
            // Debug: Log the SQL query and results
            error_log('Search query: ' . $query);
            error_log('SQL: ' . $qb->getQuery()->getSQL());
            error_log('Results count: ' . count($jobs));

            $hits = [];
            foreach ($jobs as $job) {
                $hits[] = [
                    'objectID' => $job->getId(),
                    'id' => $job->getId(),
                    'title' => $this->highlightText($job->getTitle(), $query),
                    'description' => $this->truncateAndHighlight($job->getDescription(), $query, 100),
                    'location' => $job->getLocation(),
                    'jobType' => $job->getJobType(),
                    'category' => $job->getCategory()?->getName(),
                    'salaryRange' => $job->getSalaryRange(),
                    'postedDate' => $job->getPostedDate()->format('Y-m-d'),
                    'recruiter' => $job->getRecruiter() ? $job->getRecruiter()->getFullName() : 'Not specified',
                    '_highlightResult' => [
                        'title' => ['value' => $this->highlightText($job->getTitle(), $query)],
                        'description' => ['value' => $this->truncateAndHighlight($job->getDescription(), $query, 100)]
                    ],
                    '_snippetResult' => [
                        'description' => ['value' => $this->truncateAndHighlight($job->getDescription(), $query, 50)]
                    ]
                ];
            }

            return new JsonResponse([
                'hits' => $hits,
                'nbHits' => count($hits),
                'query' => $query
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Search temporarily unavailable',
                'hits' => []
            ], 500);
        }
    }

    private function highlightText(string $text, string $query): string
    {
        if (empty($query) || empty($text)) {
            return $text;
        }

        // Simple case-insensitive highlighting
        $pattern = '/(' . preg_quote($query, '/') . ')/i';
        $replacement = '<strong>$1</strong>';
        
        return preg_replace($pattern, $replacement, $text);
    }

    private function truncateAndHighlight(string $text, string $query, int $maxLength): string
    {
        if (empty($text)) {
            return '';
        }

        // First highlight the text
        $highlighted = $this->highlightText($text, $query);
        
        // Then truncate if needed
        if (strlen($highlighted) <= $maxLength) {
            return $highlighted;
        }

        // Find the first occurrence of the highlighted term
        $highlightPattern = '/<strong>.*?<\/strong>/i';
        if (preg_match($highlightPattern, $highlighted, $matches)) {
            $highlightPos = strpos($highlighted, $matches[0]);
            $highlightLength = strlen($matches[0]);
            
            // Try to show context around the highlighted term
            $startPos = max(0, $highlightPos - 30);
            $endPos = min(strlen($highlighted), $highlightPos + $highlightLength + 30);
            
            $truncated = substr($highlighted, $startPos, $endPos - $startPos);
            
            // Add ellipsis if we cut from the middle
            if ($startPos > 0) {
                $truncated = '...' . $truncated;
            }
            if ($endPos < strlen($highlighted)) {
                $truncated = $truncated . '...';
            }
            
            return $truncated;
        }

        // If no highlight found, just truncate from the beginning
        return substr($highlighted, 0, $maxLength) . '...';
    }
}
