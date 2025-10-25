<?php

namespace App\Controller;

use App\Service\OfferService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    public function __construct(
        private readonly OfferService $offerService
    ) {
    }

    #[Route('/admin/offers', name: 'admin_offers')]
    public function offers(): Response
    {
        $offers = $this->offerService->getAllOffers();

        return $this->render('admin/offers.html.twig', [
            'offers' => $offers,
        ]);
    }
}
