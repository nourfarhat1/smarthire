<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FacePlusService
{
    private string $apiKey;
    private string $apiSecret;
    private string $apiServer;
    private HttpClientInterface $httpClient;

    public function __construct(
        HttpClientInterface $httpClient,
        ParameterBagInterface $parameterBag
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $_ENV['FACEPLUS_API_KEY'] ?? '';
        $this->apiSecret = $_ENV['FACEPLUS_API_SECRET'] ?? '';
        $this->apiServer = 'https://api-us.faceplusplus.com';
    }

    /**
     * Detect faces in an image
     */
    public function detectFaces(string $imageData): array
    {
        try {
            $formData = [
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'image_base64' => $imageData,
                'return_attributes' => 'gender,age,ethnicity,emotion',
                'face_token' => 'true',
                'detect_face' => '1'
            ];

            $response = $this->httpClient->request('POST', $this->apiServer . '/facepp/v3/detect', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => http_build_query($formData)
            ]);

            $data = $response->toArray();
            
            if (isset($data['error_message'])) {
                throw new \Exception('Face++ API Error: ' . $data['error_message']);
            }

            return $data;

        } catch (\Exception $e) {
            throw new \Exception('Face detection failed: ' . $e->getMessage());
        }
    }

    /**
     * Create a face set for storing faces
     */
    public function createFaceSet(string $displayName): array
    {
        try {
            $formData = [
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'display_name' => $displayName
            ];

            $response = $this->httpClient->request('POST', $this->apiServer . '/facepp/v3/faceset/create', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => http_build_query($formData)
            ]);

            $data = $response->toArray();
            
            if (isset($data['error_message'])) {
                throw new \Exception('Face++ API Error: ' . $data['error_message']);
            }

            return $data;

        } catch (\Exception $e) {
            throw new \Exception('Face set creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Add a face to a face set
     */
    public function addFaceToSet(string $faceToken, string $faceSetToken): array
    {
        try {
            $formData = [
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'faceset_token' => $faceSetToken,
                'face_tokens' => $faceToken
            ];

            $response = $this->httpClient->request('POST', $this->apiServer . '/facepp/v3/faceset/addface', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => http_build_query($formData)
            ]);

            $data = $response->toArray();
            
            if (isset($data['error_message'])) {
                throw new \Exception('Face++ API Error: ' . $data['error_message']);
            }

            return $data;

        } catch (\Exception $e) {
            throw new \Exception('Adding face to set failed: ' . $e->getMessage());
        }
    }

    /**
     * Search for a face in a face set
     */
    public function searchFace(string $faceToken, string $faceSetToken): array
    {
        try {
            $formData = [
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'face_token' => $faceToken,
                'faceset_token' => $faceSetToken
            ];

            $response = $this->httpClient->request('POST', $this->apiServer . '/facepp/v3/search', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => http_build_query($formData)
            ]);

            $data = $response->toArray();
            
            if (isset($data['error_message'])) {
                throw new \Exception('Face++ API Error: ' . $data['error_message']);
            }

            return $data;

        } catch (\Exception $e) {
            throw new \Exception('Face search failed: ' . $e->getMessage());
        }
    }

    /**
     * Compare two faces
     */
    public function compareFaces(string $faceToken1, string $faceToken2): array
    {
        try {
            $formData = [
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'face_token1' => $faceToken1,
                'face_token2' => $faceToken2
            ];

            $response = $this->httpClient->request('POST', $this->apiServer . '/facepp/v3/compare', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => http_build_query($formData)
            ]);

            $data = $response->toArray();
            
            if (isset($data['error_message'])) {
                throw new \Exception('Face++ API Error: ' . $data['error_message']);
            }

            return $data;

        } catch (\Exception $e) {
            throw new \Exception('Face comparison failed: ' . $e->getMessage());
        }
    }

    /**
     * Get face set details
     */
    public function getFaceSetDetails(string $faceSetToken): array
    {
        try {
            $formData = [
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'faceset_token' => $faceSetToken
            ];

            $response = $this->httpClient->request('POST', $this->apiServer . '/facepp/v3/faceset/getdetail', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => http_build_query($formData)
            ]);

            $data = $response->toArray();
            
            if (isset($data['error_message'])) {
                throw new \Exception('Face++ API Error: ' . $data['error_message']);
            }

            return $data;

        } catch (\Exception $e) {
            throw new \Exception('Getting face set details failed: ' . $e->getMessage());
        }
    }

    /**
     * Remove face from face set
     */
    public function removeFaceFromSet(string $faceToken, string $faceSetToken): array
    {
        try {
            $formData = [
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'faceset_token' => $faceSetToken,
                'face_tokens' => $faceToken
            ];

            $response = $this->httpClient->request('POST', $this->apiServer . '/facepp/v3/faceset/removeface', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => http_build_query($formData)
            ]);

            $data = $response->toArray();
            
            if (isset($data['error_message'])) {
                throw new \Exception('Face++ API Error: ' . $data['error_message']);
            }

            return $data;

        } catch (\Exception $e) {
            throw new \Exception('Removing face from set failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete face set
     */
    public function deleteFaceSet(string $faceSetToken): array
    {
        try {
            $formData = [
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'faceset_token' => $faceSetToken,
                'check_empty' => '1'
            ];

            $response = $this->httpClient->request('POST', $this->apiServer . '/facepp/v3/faceset/delete', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => http_build_query($formData)
            ]);

            $data = $response->toArray();
            
            if (isset($data['error_message'])) {
                throw new \Exception('Face++ API Error: ' . $data['error_message']);
            }

            return $data;

        } catch (\Exception $e) {
            throw new \Exception('Deleting face set failed: ' . $e->getMessage());
        }
    }
}
