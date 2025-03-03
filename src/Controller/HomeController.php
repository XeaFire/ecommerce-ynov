<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository): Response
    {
        // Récupérer les 4 derniers articles ajoutés
        $latestArticles = $articleRepository->findBy([], ['id' => 'DESC'], 4);
        
        return $this->render('home/index.html.twig', [
            'latestArticles' => $latestArticles
        ]);
    }
} 