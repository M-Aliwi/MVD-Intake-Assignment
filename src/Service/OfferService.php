<?php

namespace App\Service;

use App\Entity\Offer;
use App\Repository\OfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class OfferService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OfferRepository $offerRepository,
        private readonly MovingDigitalApiService $apiService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Create a new offer and submit it to the API
     */
    public function createOffer(Offer $offer): Offer
    {
        try {
            // Set default property ID (you might want to make this configurable)
            $offer->setPropertyId('default-property-001');
            
            // Save to database first
            $this->entityManager->persist($offer);
            $this->entityManager->flush();

            $this->logger->info('Offer saved to database', [
                'offer_id' => $offer->getId(),
                'name' => $offer->getName(),
                'email' => $offer->getEmail(),
            ]);

            // Prepare data for API
            $apiData = $this->prepareApiData($offer);
            
            // Submit to Moving Digital API
            $apiResponse = $this->apiService->submitOffer($apiData);
            
            if ($apiResponse['success']) {
                // Update offer with external ID and status
                $offer->setExternalId($apiResponse['external_id']);
                $offer->setStatus($apiResponse['status']);
                
                // Store API response metadata
                $offer->setMeta([
                    'api_response' => $apiResponse['data'],
                    'submitted_at' => new \DateTime(),
                ]);
                
                $this->entityManager->flush();
                
                $this->logger->info('Offer submitted to API successfully', [
                    'offer_id' => $offer->getId(),
                    'external_id' => $offer->getExternalId(),
                    'status' => $offer->getStatus(),
                ]);
            } else {
                // API submission failed, but we still have the offer in our database
                $offer->setStatus('api_error');
                $offer->setMeta([
                    'api_error' => $apiResponse['error'],
                    'api_error_at' => new \DateTime(),
                ]);
                
                $this->entityManager->flush();
                
                $this->logger->error('API submission failed', [
                    'offer_id' => $offer->getId(),
                    'error' => $apiResponse['error'],
                ]);
            }

            return $offer;
            
        } catch (\Exception $e) {
            $this->logger->error('Error creating offer', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new \RuntimeException('Failed to create offer: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get an offer by ID
     */
    public function getOfferById(int $id): ?Offer
    {
        return $this->offerRepository->find($id);
    }

    /**
     * Update offer status from API
     */
    public function updateOfferStatus(Offer $offer): bool
    {
        if (!$offer->getExternalId()) {
            $this->logger->warning('Cannot update status: no external ID', [
                'offer_id' => $offer->getId(),
            ]);
            return false;
        }

        try {
            $apiResponse = $this->apiService->checkOfferStatus($offer->getExternalId());
            
            if ($apiResponse['success']) {
                $newStatus = $apiResponse['status'];
                
                if ($newStatus !== $offer->getStatus()) {
                    $offer->setStatus($newStatus);
                    
                    // Update metadata
                    $meta = $offer->getMeta() ?? [];
                    $meta['last_status_check'] = new \DateTime();
                    $meta['api_status_data'] = $apiResponse['data'];
                    $offer->setMeta($meta);
                    
                    $this->entityManager->flush();
                    
                    $this->logger->info('Offer status updated', [
                        'offer_id' => $offer->getId(),
                        'external_id' => $offer->getExternalId(),
                        'old_status' => $offer->getStatus(),
                        'new_status' => $newStatus,
                    ]);
                    
                    return true;
                }
            } else {
                $this->logger->error('Failed to check offer status', [
                    'offer_id' => $offer->getId(),
                    'external_id' => $offer->getExternalId(),
                    'error' => $apiResponse['error'],
                ]);
            }
            
            return false;
            
        } catch (\Exception $e) {
            $this->logger->error('Exception during status update', [
                'offer_id' => $offer->getId(),
                'external_id' => $offer->getExternalId(),
                'message' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Get all offers with pending status
     */
    public function getPendingOffers(): array
    {
        return $this->offerRepository->findBy(['status' => 'pending']);
    }

    /**
     * Get all offers
     */
    public function getAllOffers(): array
    {
        return $this->offerRepository->findAll();
    }

    /**
     * Prepare data for API submission
     */
    private function prepareApiData(Offer $offer): array
    {
        return [
            'property_id' => $offer->getPropertyId(),
            'name' => $offer->getName(),
            'email' => $offer->getEmail(),
            'phone' => $offer->getPhone(),
            'amount' => (float) $offer->getAmount(),
            'conditions' => $offer->getConditions(),
            'submitted_at' => $offer->getCreatedAt()->format('c'),
        ];
    }
}
