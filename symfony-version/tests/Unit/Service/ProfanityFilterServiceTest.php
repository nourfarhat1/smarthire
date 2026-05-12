<?php

namespace App\Tests\Unit\Service;

use App\Service\ProfanityFilterService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ProfanityFilterServiceTest extends KernelTestCase
{
    private ProfanityFilterService $profanityFilter;
    private HttpClientInterface $httpClient;

    protected function setUp(): void
    {
        self::bootKernel();
        
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')->willReturnMap([
            ['app.profanity_api_url', 'https://www.purgomalum.com/service/containsprofanity'],
            ['app.ai_api_key', 'test_key'],
            ['app.ai_api_url', 'https://api.test.com'],
            ['app.ai_model', 'test-model']
        ]);

        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->profanityFilter = new ProfanityFilterService($this->httpClient, $parameterBag);
    }

    public function testContainsProfanityWithCleanText(): void
    {
        // Mock API response for clean text
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('false');
        
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $cleanText = "This is a clean and professional message.";
        $result = $this->profanityFilter->containsProfanity($cleanText);

        $this->assertFalse($result);
    }

    public function testContainsProfanityWithDirtyText(): void
    {
        // Mock API response for profanity
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('true');
        
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $dirtyText = "This message contains some damn profanity.";
        $result = $this->profanityFilter->containsProfanity($dirtyText);

        $this->assertTrue($result);
    }

    public function testContainsProfanityWithApiFailure(): void
    {
        // Mock API failure
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('API Error'));

        $text = "This message contains shit profanity.";
        $result = $this->profanityFilter->containsProfanity($text);

        // Should fall back to local filtering
        $this->assertTrue($result);
    }

    public function testFilterProfanity(): void
    {
        $text = "This is a damn test with some shit words and hell content.";
        $filtered = $this->profanityFilter->filterProfanity($text);

        $this->assertStringNotContainsString('damn', $filtered);
        $this->assertStringNotContainsString('shit', $filtered);
        $this->assertStringNotContainsString('hell', $filtered);
        $this->assertStringContainsString('***', $filtered);
    }

    public function testGetProfanityWords(): void
    {
        $text = "This contains damn and shit but not clean words.";
        $profanityWords = $this->profanityFilter->getProfanityWords($text);

        $this->assertContains('damn', $profanityWords);
        $this->assertContains('shit', $profanityWords);
        $this->assertNotContains('clean', $profanityWords);
    }

    public function testValidateTextWithCleanText(): void
    {
        $cleanText = "This is a professional and clean message.";
        $validation = $this->profanityFilter->validateText($cleanText);

        $this->assertFalse($validation['has_profanity']);
        $this->assertEmpty($validation['profanity_words']);
        $this->assertEquals($cleanText, $validation['filtered_text']);
        $this->assertEquals(0, $validation['profanity_count']);
    }

    public function testValidateTextWithProfanity(): void
    {
        $dirtyText = "This message contains damn profanity and shit words.";
        $validation = $this->profanityFilter->validateText($dirtyText);

        $this->assertTrue($validation['has_profanity']);
        $this->assertNotEmpty($validation['profanity_words']);
        $this->assertNotEquals($dirtyText, $validation['filtered_text']);
        $this->assertGreaterThan(0, $validation['profanity_count']);
    }

    public function testIsAppropriateWithCleanText(): void
    {
        $cleanText = "This is a professional message with no inappropriate content.";
        $this->assertTrue($this->profanityFilter->isAppropriate($cleanText));
    }

    public function testIsAppropriateWithMildProfanity(): void
    {
        // Text with mild profanity under 2% threshold
        $text = str_repeat("clean word ", 100) . " damn " . str_repeat("clean word ", 100);
        $this->assertTrue($this->profanityFilter->isAppropriate($text));
    }

    public function testIsAppropriateWithSevereProfanity(): void
    {
        $text = "This message contains fuck and severe profanity.";
        $this->assertFalse($this->profanityFilter->isAppropriate($text));
    }

    public function testIsAppropriateWithHighProfanityRatio(): void
    {
        // Text with high profanity ratio (>2%)
        $text = "damn shit fuck hell crap ass";
        $this->assertFalse($this->profanityFilter->isAppropriate($text));
    }

    /**
     * @dataProvider cleanTextProvider
     */
    public function testCleanTexts(string $text): void
    {
        $this->assertFalse($this->profanityFilter->containsProfanity($text));
        $this->assertTrue($this->profanityFilter->isAppropriate($text));
    }

    public static function cleanTextProvider(): array
    {
        return [
            ['This is a professional message.'],
            ['I would like to apply for the position.'],
            ['Thank you for considering my application.'],
            ['Looking forward to hearing from you.'],
            ['Best regards, John Doe'],
            ['Please find attached my resume.'],
            ['I have experience in PHP and JavaScript.'],
            ['The weather is nice today.'],
            ['I enjoy working with teams.'],
            ['This is a test message.'],
        ];
    }

    /**
     * @dataProvider dirtyTextProvider
     */
    public function testDirtyTexts(string $text): void
    {
        $this->assertTrue($this->profanityFilter->containsProfanity($text));
        $this->assertFalse($this->profanityFilter->isAppropriate($text));
    }

    public static function dirtyTextProvider(): array
    {
        return [
            ['This is damn annoying.'],
            ['What the hell is this?'],
            ['This is total shit.'],
            ['Fuck this bullshit.'],
            ['You are an asshole.'],
            ['This crap is terrible.'],
            ['Son of a bitch!'],
            ['Motherfucker!'],
            ['What the ass?'],
            ['Piece of crap.'],
        ];
    }

    public function testAddCustomBadWords(): void
    {
        $originalBadWords = $this->profanityFilter->getBadWordsList();
        $originalCount = count($originalBadWords);

        $this->profanityFilter->addCustomBadWords(['customword', 'anotherbad']);
        
        $newBadWords = $this->profanityFilter->getBadWordsList();
        $this->assertGreaterThan($originalCount, count($newBadWords));
        $this->assertContains('customword', $newBadWords);
        $this->assertContains('anotherbad', $newBadWords);
    }

    public function testFrenchProfanity(): void
    {
        $frenchText = "Ce message contient de la merde et des putains de connards.";
        $this->assertTrue($this->profanityFilter->containsProfanity($frenchText));
        $this->assertFalse($this->profanityFilter->isAppropriate($frenchText));
    }

    public function testMixedLanguageProfanity(): void
    {
        $mixedText = "This damn message contains de la merde et some shit.";
        $this->assertTrue($this->profanityFilter->containsProfanity($mixedText));
        $this->assertFalse($this->profanityFilter->isAppropriate($mixedText));
    }
}
