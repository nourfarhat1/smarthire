<?php

namespace App\Tests\Unit\Service;

use App\Service\AIService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AIServiceTest extends KernelTestCase
{
    private AIService $aiService;
    private HttpClientInterface $httpClient;

    protected function setUp(): void
    {
        self::bootKernel();
        
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')->willReturnMap([
            ['app.ai_api_key', 'test_api_key'],
            ['app.ai_api_url', 'https://api.test.com/chat/completions'],
            ['app.ai_model', 'test-model']
        ]);

        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->aiService = new AIService($parameterBag, $this->httpClient);
    }

    public function testAnalyzeResumeWithSuccessfulResponse(): void
    {
        // Mock successful AI response
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'firstName' => 'John',
                            'lastName' => 'Doe',
                            'phone' => '123-456-7890',
                            'email' => 'john.doe@example.com',
                            'skills' => ['PHP', 'JavaScript', 'MySQL'],
                            'experience' => ['5 years PHP development', '3 years JavaScript'],
                            'summary' => 'Experienced full-stack developer with expertise in PHP and JavaScript.'
                        ])
                    ]
                ]
            ]
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $cvText = "John Doe
Email: john.doe@example.com
Phone: 123-456-7890

Experience:
- 5 years of PHP development
- 3 years of JavaScript development
- MySQL database management

Skills:
- PHP
- JavaScript
- MySQL
- HTML/CSS
- Git

Summary:
Experienced full-stack developer looking for new opportunities.";

        $result = $this->aiService->analyzeResume($cvText);

        $this->assertIsArray($result);
        $this->assertEquals('John', $result['firstName']);
        $this->assertEquals('Doe', $result['lastName']);
        $this->assertEquals('john.doe@example.com', $result['email']);
        $this->assertEquals('123-456-7890', $result['phone']);
        $this->assertContains('PHP', $result['skills']);
        $this->assertContains('JavaScript', $result['skills']);
        $this->assertContains('MySQL', $result['skills']);
        $this->assertNotEmpty($result['experience']);
        $this->assertNotEmpty($result['summary']);
    }

    public function testAnalyzeResumeWithInvalidJsonResponse(): void
    {
        // Mock invalid JSON response
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Invalid JSON response'
                    ]
                ]
            ]
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $cvText = "Sample CV text for testing.";
        $result = $this->aiService->analyzeResume($cvText);

        // Should fall back to manual extraction
        $this->assertIsArray($result);
        $this->assertArrayHasKey('firstName', $result);
        $this->assertArrayHasKey('lastName', $result);
        $this->assertArrayHasKey('skills', $result);
    }

    public function testAnalyzeResumeWithApiFailure(): void
    {
        // Mock API failure
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('API Error'));

        $cvText = "Sample CV text for testing.";
        $result = $this->aiService->analyzeResume($cvText);

        // Should fall back to manual extraction
        $this->assertIsArray($result);
        $this->assertArrayHasKey('firstName', $result);
        $this->assertArrayHasKey('lastName', $result);
        $this->assertArrayHasKey('skills', $result);
    }

    public function testExtractTextFromPDF(): void
    {
        // Create a temporary test file
        $testFile = tempnam(sys_get_temp_dir(), 'test_pdf_');
        file_put_contents($testFile, 'Sample PDF content for testing');

        $result = $this->aiService->extractTextFromPDF($testFile);

        $this->assertIsString($result);
        $this->assertStringContainsString('Sample PDF content', $result);

        // Clean up
        unlink($testFile);
    }

    public function testExtractTextFromNonExistentFile(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('PDF file not found');

        $this->aiService->extractTextFromPDF('/non/existent/file.pdf');
    }

    public function testCalculateMatchingScore(): void
    {
        $candidateSkills = ['PHP', 'JavaScript', 'MySQL', 'Git', 'Docker'];
        $requirements = 'Looking for a PHP developer with JavaScript and MySQL experience. Docker knowledge is a plus.';

        $result = $this->aiService->calculateMatchingScore($candidateSkills, $requirements);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('matched_skills', $result);
        $this->assertArrayHasKey('missing_skills', $result);
        $this->assertArrayHasKey('total_required', $result);
        $this->assertArrayHasKey('total_matched', $result);

        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
        $this->assertContains('PHP', $result['matched_skills']);
        $this->assertContains('JavaScript', $result['matched_skills']);
        $this->assertContains('MySQL', $result['matched_skills']);
    }

    public function testCalculateMatchingScoreWithNoSkills(): void
    {
        $candidateSkills = [];
        $requirements = 'Looking for a PHP developer with JavaScript experience.';

        $result = $this->aiService->calculateMatchingScore($candidateSkills, $requirements);

        $this->assertEquals(0, $result['score']);
        $this->assertEquals(0, $result['total_matched']);
        $this->assertEmpty($result['matched_skills']);
    }

    public function testGenerateJobDescription(): void
    {
        $jobTitle = 'Senior PHP Developer';
        $requirements = ['PHP', 'Symfony', 'MySQL', 'REST API'];
        $companyInfo = 'Tech Company Inc.';

        // Mock AI response
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'content' => 'We are looking for a Senior PHP Developer with expertise in PHP, Symfony, MySQL, and REST API development. Join our innovative team at Tech Company Inc.'
                    ]
                ]
            ]
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $result = $this->aiService->generateJobDescription($jobTitle, $requirements, $companyInfo);

        $this->assertIsString($result);
        $this->assertStringContainsString($jobTitle, $result);
        $this->assertStringContainsString('PHP', $result);
        $this->assertStringContainsString('Symfony', $result);
    }

    public function testGenerateJobDescriptionWithApiFailure(): void
    {
        // Mock API failure
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('API Error'));

        $jobTitle = 'Senior PHP Developer';
        $requirements = ['PHP', 'Symfony'];
        $companyInfo = 'Tech Company Inc.';

        $result = $this->aiService->generateJobDescription($jobTitle, $requirements, $companyInfo);

        // Should return fallback description
        $this->assertIsString($result);
        $this->assertStringContainsString($jobTitle, $result);
    }

    public function testAnalyzeCandidateProfile(): void
    {
        $candidateData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'skills' => ['PHP', 'JavaScript', 'MySQL'],
            'experience' => ['5 years PHP development']
        ];

        // Mock AI response
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Strong candidate with good technical skills. Consider learning more frameworks.'
                    ]
                ]
            ]
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $result = $this->aiService->analyzeCandidateProfile($candidateData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('strengths', $result);
        $this->assertArrayHasKey('improvements', $result);
        $this->assertNotEmpty($result['recommendations']);
    }

    public function testCompareFaces(): void
    {
        $capturedImage = tempnam(sys_get_temp_dir(), 'captured_');
        $storedImage = tempnam(sys_get_temp_dir(), 'stored_');
        
        file_put_contents($capturedImage, 'fake image data');
        file_put_contents($storedImage, 'fake image data');

        $result = $this->aiService->compareFaces($capturedImage, $storedImage);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('match_confidence', $result);
        $this->assertArrayHasKey('is_match', $result);
        $this->assertArrayHasKey('analysis_description', $result);
        $this->assertArrayHasKey('key_differences', $result);

        $this->assertGreaterThanOrEqual(0, $result['match_confidence']);
        $this->assertLessThanOrEqual(100, $result['match_confidence']);
        $this->assertIsBool($result['is_match']);

        // Clean up
        unlink($capturedImage);
        unlink($storedImage);
    }

    public function testExtractFaceFeatures(): void
    {
        $imagePath = tempnam(sys_get_temp_dir(), 'face_');
        file_put_contents($imagePath, 'fake image data');

        $result = $this->aiService->extractFaceFeatures($imagePath);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('face_detected', $result);
        $this->assertArrayHasKey('face_count', $result);
        $this->assertArrayHasKey('facial_features', $result);
        $this->assertArrayHasKey('confidence_score', $result);
        $this->assertArrayHasKey('image_quality_assessment', $result);

        // Clean up
        unlink($imagePath);
    }

    public function testValidateFaceImage(): void
    {
        // Create a test image file (simulated)
        $imagePath = tempnam(sys_get_temp_dir(), 'test_image_');
        
        // Create a simple image header (1x1 pixel GIF)
        $gifData = base64_decode('R0lGODdhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
        file_put_contents($imagePath, $gifData);

        $result = $this->aiService->validateFaceImage($imagePath);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_valid', $result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('quality_score', $result);

        // Clean up
        unlink($imagePath);
    }

    public function testValidateFaceImageWithNonExistentFile(): void
    {
        $result = $this->aiService->validateFaceImage('/non/existent/image.jpg');

        $this->assertFalse($result['is_valid']);
        $this->assertArrayHasKey('issues', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertEquals(0, $result['quality_score']);
    }

    /**
     * @dataProvider cvTextProvider
     */
    public function testAnalyzeResumeWithVariousTexts(string $cvText, array $expectedFields): void
    {
        // Mock successful AI response
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'firstName' => $expectedFields['firstName'] ?? 'Test',
                            'lastName' => $expectedFields['lastName'] ?? 'User',
                            'phone' => $expectedFields['phone'] ?? '123-456-7890',
                            'email' => $expectedFields['email'] ?? 'test@example.com',
                            'skills' => $expectedFields['skills'] ?? ['PHP'],
                            'experience' => $expectedFields['experience'] ?? ['Test experience'],
                            'summary' => $expectedFields['summary'] ?? 'Test summary'
                        ])
                    ]
                ]
            ]
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $result = $this->aiService->analyzeResume($cvText);

        foreach ($expectedFields as $field => $value) {
            $this->assertEquals($value, $result[$field]);
        }
    }

    public static function cvTextProvider(): array
    {
        return [
            [
                'cvText' => 'John Doe - PHP Developer',
                'expectedFields' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'skills' => ['PHP']
                ]
            ],
            [
                'cvText' => 'Jane Smith - Full Stack Developer',
                'expectedFields' => [
                    'firstName' => 'Jane',
                    'lastName' => 'Smith',
                    'skills' => ['PHP']
                ]
            ]
        ];
    }
}
