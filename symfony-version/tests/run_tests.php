<?php

// Simple test runner to avoid CSRF issues
require_once __DIR__ . '/../vendor/autoload.php';

use App\Service\ProfanityFilterService;
use App\Service\AIService;
use App\Service\JobService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

echo "=== Running Service Tests ===\n\n";

// Test ProfanityFilterService
echo "Testing ProfanityFilterService...\n";

$parameterBag = new class implements ParameterBagInterface {
    public function get(string $name): mixed {
        $defaults = [
            'app.profanity_api_url' => 'https://www.purgomalum.com/service/containsprofanity',
            'app.ai_api_key' => 'test_key',
            'app.ai_api_url' => 'https://api.test.com',
            'app.ai_model' => 'test-model'
        ];
        return $defaults[$name] ?? null;
    }
};

$httpClient = new class implements HttpClientInterface {
    public function request(string $method, string $url, array $options = []): mixed {
        return new class {
            public function getContent(): string {
                return 'false'; // Simulate clean text response
            }
        };
    }
    
    public function stream($method, string $url, array $options = []): mixed { return null; }
    public function withOptions(array $options): static { return $this; }
};

$profanityFilter = new ProfanityFilterService($parameterBag, $httpClient);

// Test clean text
$cleanText = "This is a professional and clean message.";
$containsProfanity = $profanityFilter->containsProfanity($cleanText);
echo "✓ Clean text test: " . ($containsProfanity ? "FAIL" : "PASS") . "\n";

// Test profanity text
$dirtyText = "This message contains damn profanity.";
$containsProfanity = $profanityFilter->containsProfanity($dirtyText);
echo "✓ Profanity text test: " . ($containsProfanity ? "PASS" : "FAIL") . "\n";

// Test filtering
$filtered = $profanityFilter->filterProfanity("This is damn and shit content.");
$hasFiltered = !str_contains($filtered, 'damn') && !str_contains($filtered, 'shit');
echo "✓ Text filtering test: " . ($hasFiltered ? "PASS" : "FAIL") . "\n";

// Test appropriateness
$isAppropriate = $profanityFilter->isAppropriate("This is a clean message.");
echo "✓ Appropriateness test (clean): " . ($isAppropriate ? "PASS" : "FAIL") . "\n";

$isNotAppropriate = $profanityFilter->isAppropriate("This contains fuck and shit.");
echo "✓ Appropriateness test (dirty): " . ($isNotAppropriate ? "FAIL" : "PASS") . "\n";

echo "\n";

// Test AIService
echo "Testing AIService...\n";

$aiService = new AIService($parameterBag, $httpClient);

// Test CV analysis (with fallback)
$cvText = "John Doe
Email: john.doe@example.com
Phone: 123-456-7890

Skills: PHP, JavaScript, MySQL
Experience: 5 years development";

try {
    $result = $aiService->analyzeResume($cvText);
    $hasRequiredFields = isset($result['firstName']) && isset($result['skills']) && isset($result['experience']);
    echo "✓ CV analysis test: " . ($hasRequiredFields ? "PASS" : "FAIL") . "\n";
} catch (Exception $e) {
    echo "✗ CV analysis test: FAIL - " . $e->getMessage() . "\n";
}

// Test matching score
try {
    $candidateSkills = ['PHP', 'JavaScript', 'MySQL'];
    $requirements = 'Looking for PHP developer with JavaScript and MySQL experience.';
    $score = $aiService->calculateMatchingScore($candidateSkills, $requirements);
    $hasScore = isset($score['score']) && $score['score'] >= 0;
    echo "✓ Matching score test: " . ($hasScore ? "PASS" : "FAIL") . "\n";
} catch (Exception $e) {
    echo "✗ Matching score test: FAIL - " . $e->getMessage() . "\n";
}

echo "\n";

// Test JobService
echo "Testing JobService...\n";

// Create mock repositories
$jobOfferRepository = new class {
    public function findActiveJobsNotAppliedByUser(int $userId, array $jobIds, int $limit): array {
        return [];
    }
};

$jobRequestRepository = new class {
    private $applications = [];
    
    public function findOneBy(array $criteria): ?object {
        // Simulate no existing application
        return null;
    }
    
    public function findBy(array $criteria, array $orderBy = []): array {
        return $this->applications;
    }
};

$entityManager = new class {
    public function persist(object $entity): void {}
    public function flush(): void {}
    public function remove(object $entity): void {}
};

$jobService = new JobService($jobOfferRepository, $jobRequestRepository, $entityManager);

// Create mock user and job
$user = new class {
    public function getId(): int { return 1; }
};

$jobOffer = new class {
    public function isActive(): bool { return true; }
    public function getDeadline(): ?\DateTime { return new \DateTime('2025-12-31'); }
    public function getPostedBy(): object { return new class { public function getId(): int { return 2; } }; }
};

// Test duplicate application check
$hasApplied = $jobService->hasUserAppliedForJob($user, $jobOffer);
echo "✓ Duplicate application check: " . ($hasApplied ? "FAIL" : "PASS") . "\n";

// Test job application
$result = $jobService->applyForJob($user, $jobOffer, ['coverLetter' => 'Test cover letter']);
$applicationSuccess = $result['success'] && isset($result['application_id']);
echo "✓ Job application test: " . ($applicationSuccess ? "PASS" : "FAIL") . "\n";

// Test can apply check
$canApply = $jobService->canUserApplyForJob($user, $jobOffer);
$canApplySuccess = $canApply['can_apply'] && empty($canApply['reasons']);
echo "✓ Can apply check: " . ($canApplySuccess ? "PASS" : "FAIL") . "\n";

echo "\n=== Test Summary ===\n";
echo "All basic service tests completed.\n";
echo "For full test suite, run: php bin/phpunit (after fixing CSRF issues)\n";
