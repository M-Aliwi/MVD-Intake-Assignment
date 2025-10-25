<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Form\OfferFormType;
use App\Service\OfferService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OfferController extends AbstractController
{
    public function __construct(
        private readonly OfferService $offerService
    ) {
    }

    #[Route('/', name: 'offer_form', methods: ['GET', 'POST'])]
    public function form(Request $request): Response
    {
        $offer = new Offer();
        $form = $this->createForm(OfferFormType::class, $offer);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $savedOffer = $this->offerService->createOffer($offer);
                
                return $this->redirectToRoute('offer_status', [
                    'id' => $savedOffer->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Er is een fout opgetreden bij het plaatsen van uw bod. Probeer het opnieuw.');
            }
        }

        return $this->render('offer/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/status/{id}', name: 'offer_status', methods: ['GET'])]
    public function status(int $id): Response
    {
        $offer = $this->offerService->getOfferById($id);

        if (!$offer) {
            throw $this->createNotFoundException('Bod niet gevonden.');
        }

        return $this->render('offer/status.html.twig', [
            'offer' => $offer,
        ]);
    }

    #[Route('/api/status/{id}', name: 'api_offer_status', methods: ['GET'])]
    public function apiStatus(int $id): Response
    {
        $offer = $this->offerService->getOfferById($id);

        if (!$offer) {
            return $this->json(['error' => 'Bod niet gevonden.'], 404);
        }

        return $this->json([
            'id' => $offer->getId(),
            'status' => $offer->getStatus(),
            'external_id' => $offer->getExternalId(),
            'created_at' => $offer->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $offer->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);
    }
}
