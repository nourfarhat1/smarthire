<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ProfanityFilterService
{
    private HttpClientInterface $client;
    private string $apiUrl;
    private array $localBadWords;

    public function __construct(HttpClientInterface $client, string $profanityApiUrl)
    {
        $this->client = $client;
        $this->apiUrl = $profanityApiUrl;
        
        // Local fallback bad words list
        $this->localBadWords = [
            'damn', 'hell', 'shit', 'fuck', 'bitch', 'bastard', 'ass', 'asshole',
            'crap', 'darn', 'heck', 'piss', 'screw', 'screwed', 'screwing',
            'fucking', 'fck', 'fk', 'fuk', 'f***', 'sh1t', 'sh*t', 'b*tch', 'a$$',
            
            // French profanity (since this is a French project)
            'merde', 'putain', 'connard', 'connasse', 'salope', 'enculé', 'encule',
            'bordel', 'chier', 'chieuse', 'couillon', 'couillonne', 'trouduc',
            'trou du cul', 'fdp', 'ntm', 'ta mère', 'ta mere',
            
            // Common internet slang/profanity
            'wtf', 'stfu', 'gtfo', 'ffs', 'fml', 'lmao', 'lmfao', 'roflmao',
            // Mild profanity that might be filtered
            'idiot', 'stupid', 'moron', 'dumb', 'loser', 'jerk', 'fool',
        ];
    }

    /**
     * Enhanced profanity detection with multiple methods
     */
    public function containsProfanity(string $text): bool
    {
        try {
            // Try external API first
            $response = $this->client->request('GET', $this->apiUrl . '?text=' . urlencode($text));
            
            if ($response->getStatusCode() === 200) {
                $result = $response->getContent();
                return filter_var($result, FILTER_VALIDATE_BOOLEAN);
            }
        } catch (\Exception $e) {
            // If API fails, use local detection
        }
        
        // Fallback to local detection
        return $this->containsLocalProfanity($text);
    }

    /**
     * Local profanity detection using enhanced pattern matching
     */
    private function containsLocalProfanity(string $text): bool
    {
        $text = strtolower($text);
        
        // Direct word matching
        foreach ($this->localBadWords as $word) {
            if (str_contains($text, $word)) {
                return true;
            }
        }
        
        // Pattern matching for variations
        $patterns = [
            '/\b(f+u+c+k+)\b/i',
            '/\b(s+h+i+t+)\b/i',
            '/\b(a+s+s+h+o+l+)\b/i',
            '/\b(b+i+t+c+h+)\b/i',
            '/\b(d+a+m+n+)\b/i',
            '/\b(h+e+l+l+)\b/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Filter profanity from text by replacing with asterisks
     */
    public function filterProfanity(string $text): string
    {
        $filtered = $text;
        
        // Replace each bad word with asterisks
        foreach ($this->localBadWords as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            $filtered = preg_replace_callback($pattern, function($matches) {
                return str_repeat('*', strlen($matches[0]));
            }, $filtered);
        }
        
        return $filtered;
    }

    /**
     * Get detailed profanity analysis
     */
    public function analyzeProfanity(string $text): array
    {
        $foundWords = [];
        $textLower = strtolower($text);
        
        foreach ($this->localBadWords as $word) {
            if (str_contains($textLower, $word)) {
                $foundWords[] = $word;
            }
        }
        
        return [
            'containsProfanity' => !empty($foundWords),
            'profanityWords' => $foundWords,
            'profanityCount' => count($foundWords),
            'severity' => $this->calculateSeverity($foundWords),
            'filteredText' => $this->filterProfanity($text)
        ];
    }

    /**
     * Calculate severity based on found profanity
     */
    private function calculateSeverity(array $foundWords): string
    {
        $severeWords = ['fuck', 'fucking', 'shit', 'bitch', 'cunt', 'asshole', 'putain', 'enculé'];
        
        foreach ($foundWords as $word) {
            if (in_array($word, $severeWords)) {
                return 'HIGH';
            }
        }
        
        if (count($foundWords) > 2) {
            return 'MEDIUM';
        }
        
        return 'LOW';
    }

    /**
     * Check if text is appropriate for submission
     */
    public function isAppropriate(string $text): bool
    {
        $validation = $this->validateText($text);
        
        // Allow up to 1 mild profanity word for every 50 words
        $allowedRatio = 50;
        $profanityRatio = $validation['word_count'] > 0 ? 
            ($validation['profanity_count'] / $validation['word_count']) * 100 : 0;
        
        // Block if more than 2% profanity or if it contains severe profanity
        if ($profanityRatio > 2 || $this->containsSevereProfanity($text)) {
            return false;
        }
        
        return true;
    }

    /**
     * Check for severe profanity that should always be blocked
     */
    private function containsSevereProfanity(string $text): array
    {
        $severeWords = [
            'fuck', 'fucking', 'motherfucker', 'mother fucker', 'shit', 'bullshit',
            'enculé', 'encule', 'connard', 'connasse', 'salope', 'putain', 'merde'
        ];
        
        $foundSevere = [];
        $normalizedText = strtolower($text);
        
        foreach ($severeWords as $severeWord) {
            if (stripos($normalizedText, $severeWord) !== false) {
                $foundSevere[] = $severeWord;
            }
        }
        
        return $foundSevere;
    }

    /**
     * Add custom bad words to the local list
     */
    public function addCustomBadWords(array $words): void
    {
        $this->localBadWords = array_merge(
            $this->localBadWords,
            array_map('strtolower', $words)
        );
        $this->localBadWords = array_unique($this->localBadWords);
    }

    /**
     * Get the current bad words list
     */
    public function getBadWordsList(): array
    {
        return $this->localBadWords;
    }
}
