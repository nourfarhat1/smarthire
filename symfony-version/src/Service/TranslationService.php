<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TranslationService
{
    private HttpClientInterface $client;
    private string $apiUrl;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->apiUrl = 'https://api.mymemory.translated.net/get';
    }

    public function translate(string $text, string $sourceLang = 'auto', string $targetLang = 'en'): string
    {
        if (empty($text) || trim($text) === '') {
            return $text;
        }

        // Auto-detect source language if not provided
        if ($sourceLang === 'auto') {
            $sourceLang = $this->detectLanguage($text);
        }
        
        // If source and target are the same, return original
        if ($sourceLang === $targetLang) {
            return $text;
        }

        try {
            $response = $this->client->request('GET', $this->apiUrl, [
                'query' => [
                    'q' => $text,
                    'langpair' => $sourceLang . '|' . $targetLang
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                return $data['responseData']['translatedText'] ?? $text;
            }
        } catch (\Exception $e) {
            // Log error if needed
            return 'Translation failed: ' . $e->getMessage();
        }

        return 'Translation failed. Check internet connection.';
    }

    public function translateWithDetection(string $text, string $targetLang = 'en'): array
    {
        if (empty($text) || trim($text) === '') {
            return [
                'success' => false,
                'translation' => $text,
                'detectedLanguage' => 'unknown'
            ];
        }

        $sourceLang = $this->detectLanguage($text);
        $translation = $this->translate($text, $sourceLang, $targetLang);
        
        return [
            'success' => true,
            'translation' => $translation,
            'detectedLanguage' => $sourceLang
        ];
    }

    public function detectLanguage(string $text): string
    {
        // Simple language detection based on common patterns
        $patterns = [
            'fr' => '/[àâäéèêëïîôöùûüÿç]/i',
            'es' => '/[ñáéíóúü]/i',
            'de' => '/[äöüß]/i',
            'ar' => '/[\x{0600}-\x{06FF}]/u',
            'zh' => '/[\x{4e00}-\x{9fff}]/u',
        ];

        foreach ($patterns as $lang => $pattern) {
            if (preg_match($pattern, $text)) {
                return $lang;
            }
        }

        return 'en'; // Default to English
    }
}
