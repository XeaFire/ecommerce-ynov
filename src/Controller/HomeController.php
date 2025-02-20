<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        // Test simple pour vérifier que le template fonctionne
        return $this->render('home/index.html.twig', [
            'featuredProducts' => [
                [
                    'name' => 'Test Product',
                    'price' => 99.99,
                    'image' => 'https://placehold.co/300x300',
                    'category' => 'test'
                ]
            ]
        ]);
    }
} 