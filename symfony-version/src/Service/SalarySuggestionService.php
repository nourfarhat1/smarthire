<?php

namespace App\Service;

class SalarySuggestionService
{
    private string $adzunaApiKey;
    private string $adzunaAppId;

    public function __construct(string $adzunaAppId, string $adzunaApiKey)
    {
        $this->adzunaAppId = $adzunaAppId;
        $this->adzunaApiKey = $adzunaApiKey;
        
        error_log("SalaryService constructor - AppId: " . $this->adzunaAppId . ", ApiKey: " . substr($this->adzunaApiKey, 0, 10) . "...");
        
        if (empty($this->adzunaApiKey) || empty($this->adzunaAppId)) {
            throw new \RuntimeException('Adzuna credentials (ID or Key) are not configured in .env');
        }
    }

    public function fetchSuggestedSalary(string $jobTitle, string $location): ?string
    {
        try {
            $ch = curl_init();
            
            $what = urlencode($jobTitle);
            $where = urlencode($location);
            
            $url = "https://api.adzuna.com/v1/api/jobs/gb/search/1" .
                   "?app_id=" . $this->adzunaAppId .
                   "&app_key=" . $this->adzunaApiKey .
                   "&what=" . $what .
                   "&where=" . $where .
                   "&content-type=application/json";
                
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            error_log("API call - HTTP Code: $httpCode, cURL Error: '$curlError'");
            error_log("API Response: " . ($response ?: 'NULL'));
            
            if ($httpCode === 200) {
                return $this->parseSearchResponse($response);
            }

            return null;

        } catch (\Exception $e) {
            error_log('Adzuna API error: ' . $e->getMessage());
            return null;
        }
    }
    
    private function parseSearchResponse(string $response): ?string
    {
        error_log("Raw API Response: " . $response);
        
        $jsonData = json_decode($response, true);
        
        if (!$jsonData) {
            error_log("JSON decode failed");
            return null;
        }

        error_log("JSON decode successful. Mean field exists: " . (isset($jsonData['mean']) ? "YES" : "NO"));
        
        if (isset($jsonData['mean'])) {
            error_log("Mean value: " . $jsonData['mean']);
            if ($jsonData['mean'] > 0) {
                $formatted = number_format((float)$jsonData['mean'], 0, '.', ',');
                error_log("Returning formatted salary: " . $formatted);
                return $formatted;
            } else {
                error_log("Mean value is <= 0");
            }
        }

        error_log("No valid mean field found, returning null");
        return null; 
    }
}