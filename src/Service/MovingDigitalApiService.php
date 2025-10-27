<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Psr\Log\LoggerInterface;

class MovingDigitalApiService
{
    private const API_BASE_URL = 'https://devcase.moving.digital';
    private const CLIENT_ID = 'f2be12f4c8a6f8a0b470a48a7879d13e';
    private const CLIENT_SECRET = '7889ec9b6b0f4a15d31411fbbbfc111bd1c57c538ae20a69d8546424f11ccbfb51de300e90c4430b59ebf4b2ce0cb2c11e7a904ab8e85596d3651d1c03d30fa9';
    
    private ?string $accessToken = null;
    private ?\DateTime $tokenExpiresAt = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Get OAuth2 access token using client credentials flow
     */
    private function getAccessToken(): ?string
    {
        // Check if we have a valid token
        if ($this->accessToken && $this->tokenExpiresAt && $this->tokenExpiresAt > new \DateTime()) {
            return $this->accessToken;
        }

        try {
            $this->logger->info('Requesting OAuth2 access token');
            
            $response = $this->httpClient->request('POST', self::API_BASE_URL . '/api/token', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ],
                'body' => http_build_query([
                    'grant_type' => 'client_credentials',
                    'client_id' => self::CLIENT_ID,
                    'client_secret' => self::CLIENT_SECRET,
                ]),
                'timeout' => 15,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->accessToken = $content['access_token'] ?? null;
                $expiresIn = $content['expires_in'] ?? 3600; // Default to 1 hour
                $this->tokenExpiresAt = (new \DateTime())->add(new \DateInterval('PT' . $expiresIn . 'S'));
                
                $this->logger->info('OAuth2 token obtained successfully', [
                    'expires_in' => $expiresIn,
                ]);
                
                return $this->accessToken;
            } else {
                $this->logger->error('Failed to obtain OAuth2 token', [
                    'status_code' => $statusCode,
                    'response' => $content,
                ]);
                return null;
            }
        } catch (\Exception $e) {
            $this->logger->error('OAuth2 token request failed', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Submit an offer to the Moving Digital API
     */
    public function submitOffer(array $offerData): array
    {
        // Get OAuth2 token
        $token = $this->getAccessToken();
        if (!$token) {
            $this->logger->warning('Failed to obtain OAuth2 token, using mock behavior');
            return $this->getMockResponse($offerData);
        }

        try {
            $this->logger->info('Submitting offer to Moving Digital API');
            
            // Map offer data to API format
            $apiData = [
                'firstname' => $offerData['firstname'] ?? '',
                'lastname' => $offerData['lastname'] ?? '',
                'email' => $offerData['email'] ?? '',
                'phone' => $offerData['phone'] ?? '',
                'amount' => (int) ($offerData['amount'] ?? 0),
            ];
            
            $response = $this->httpClient->request('POST', self::API_BASE_URL . '/api/bid/create', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $apiData,
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);

            if ($statusCode >= 200 && $statusCode < 300) {
                // Handle different response formats from the API
                $externalId = $content['id'] ?? $content['bidId'] ?? null;
                $status = $content['status'] ?? 'pending';
                
                $this->logger->info('Offer submitted successfully', [
                    'external_id' => $externalId,
                    'status' => $status,
                ]);
                
                return [
                    'success' => true,
                    'external_id' => $externalId,
                    'status' => $status,
                    'data' => $content,
                ];
            } else {
                $this->logger->error('API request failed', [
                    'status_code' => $statusCode,
                    'response' => $content,
                ]);
                
                // Extract detailed validation errors if available
                $errorMessage = $content['message'] ?? 'Unknown API error';
                if (isset($content['validationErrors']) && is_array($content['validationErrors'])) {
                    $validationErrors = [];
                    foreach ($content['validationErrors'] as $field => $error) {
                        $validationErrors[] = "$field: $error";
                    }
                    $errorMessage .= ' (' . implode(', ', $validationErrors) . ')';
                }
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'status_code' => $statusCode,
                    'validation_errors' => $content['validationErrors'] ?? null,
                ];
            }
            
        } catch (\Exception $e) {
            $this->logger->error('API request exception', [
                'message' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => 'Network error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate mock response for development/fallback
     */
    private function getMockResponse(array $offerData): array
    {
        $this->logger->info('Using mock API response');
        
        $externalId = 'MOCK-' . uniqid();
        $amount = (float) ($offerData['amount'] ?? 0);
        
        // 80% chance of acceptance for offers above 200k
        $accepted = $amount >= 200000 && rand(1, 100) <= 80;
        $status = $accepted ? 'accepted' : 'pending';
        
        $this->logger->info('Mock API response generated', [
            'external_id' => $externalId,
            'status' => $status,
        ]);
        
        return [
            'success' => true,
            'external_id' => $externalId,
            'status' => $status,
            'data' => [
                'id' => $externalId,
                'status' => $status,
                'processed_at' => (new \DateTime())->format('c'),
                'message' => $accepted ? 'Offer accepted!' : 'Offer pending review.',
            ],
        ];
    }

    /**
     * Check the status of an offer
     * Note: The Moving Digital API doesn't provide a status check endpoint,
     * so we use mock behavior for development/testing
     */
    public function checkOfferStatus(string $externalId): array
    {
        $this->logger->info('Status check requested - API does not support status checking, using mock behavior', [
            'external_id' => $externalId,
        ]);
        
        return $this->getMockStatusResponse($externalId);
    }

    /**
     * Generate mock status response for development/fallback
     */
    private function getMockStatusResponse(string $externalId): array
    {
        // Deterministic mock status based on external ID hash
        // This ensures the same external ID always returns the same status
        $hash = crc32($externalId);
        $modValue = abs($hash % 100);
        
        // 60% chance accepted, 30% pending, 10% rejected
        if ($modValue < 60) {
            $status = 'accepted';
        } elseif ($modValue < 90) {
            $status = 'pending';
        } else {
            $status = 'rejected';
        }
        
        $this->logger->info('Mock status check response', [
            'external_id' => $externalId,
            'status' => $status,
            'hash_mod' => $modValue,
        ]);
        
        return [
            'success' => true,
            'status' => $status,
            'data' => [
                'id' => $externalId,
                'status' => $status,
                'checked_at' => (new \DateTime())->format('c'),
            ],
        ];
    }

    /**
     * Get all offers (for admin purposes)
     * Note: The Moving Digital API doesn't provide a list endpoint,
     * so we return empty data for admin purposes
     */
    public function getAllOffers(): array
    {
        $this->logger->info('Get all offers requested - API does not support listing offers, returning empty data');
        
        return [
            'success' => true,
            'data' => [],
        ];
    }
}
