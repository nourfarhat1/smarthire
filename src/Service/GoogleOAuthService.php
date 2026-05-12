<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GoogleOAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private HttpClientInterface $httpClient;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        ParameterBagInterface $parameterBag,
        HttpClientInterface $httpClient,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->clientId = $parameterBag->get('app.google_client_id');
        $this->clientSecret = $parameterBag->get('app.google_client_secret');
        $this->redirectUri = $parameterBag->get('app.google_redirect_uri');
        $this->httpClient = $httpClient;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Get Google OAuth authorization URL
     */
    public function getAuthorizationUrl(): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken(string $code): array
    {
        $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
            'body' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUri,
            ],
        ]);

        $data = $response->toArray();
        
        if (!isset($data['access_token'])) {
            throw new \Exception('Failed to obtain access token: ' . json_encode($data));
        }

        return $data;
    }

    /**
     * Get user information from Google
     */
    public function getUserInfo(string $accessToken): array
    {
        $response = $this->httpClient->request('GET', 'https://www.googleapis.com/oauth2/v2/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        $data = $response->toArray();
        
        if (!isset($data['email'])) {
            throw new \Exception('Failed to obtain user information: ' . json_encode($data));
        }

        return $data;
    }

    /**
     * Verify Google ID token
     */
    public function verifyIdToken(string $idToken): array
    {
        $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/tokeninfo', [
            'body' => [
                'id_token' => $idToken,
            ],
        ]);

        $data = $response->toArray();
        
        if (!isset($data['email'])) {
            throw new \Exception('Failed to verify ID token: ' . json_encode($data));
        }

        // Verify the token was issued for our client
        if ($data['aud'] !== $this->clientId) {
            throw new \Exception('Invalid token audience');
        }

        return $data;
    }
}
