<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;

class ArticleController extends AbstractController
{
    #[Route('/articles', name: 'app_articles')]
    public function index(ArticleRepository $articleRepository): Response
    {
        return $this->render('article/index.html.twig', [
            'articles' => $articleRepository->findAll()
        ]);
    }

    #[Route('/sell', name: 'app_sell')]
    #[IsGranted('ROLE_USER')]
    public function sell(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setSeller($this->getUser());
            
            // Gestion de l'upload d'image
            if ($imageFile = $form->get('image')->getData()) {
                $slugger = new AsciiSlugger();
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('articles_directory'),
                        $newFilename
                    );
                    $article->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image');
                    return $this->redirectToRoute('app_sell');
                }
            }

            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', 'Votre article a été mis en vente !');
            return $this->redirectToRoute('app_articles');
        }

        return $this->render('article/sell.html.twig', [
            'form' => $form->createView()
        ]);
    }
} 