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

    #[Route('/articles/{id}', name: 'app_article_show', requirements: ['id' => '\d+'])]
    public function show(Article $article): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $article
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
                
                // Obtenir l'extension directement depuis le nom du fichier
                $originalExtension = pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION);
                
                // Vérifier si l'extension est valide
                if (!$this->isValidImageExtension($originalExtension)) {
                    $this->addFlash('error', 'Le format de l\'image n\'est pas valide. Utilisez JPG, PNG ou GIF.');
                    return $this->redirectToRoute('app_sell');
                }
                
                $newFilename = $safeFilename.'-'.uniqid().'.'.$originalExtension;

                // Vérifier si le répertoire existe, sinon le créer
                $uploadDir = $this->getParameter('articles_directory');
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                try {
                    // Déplacer le fichier
                    $imageFile->move(
                        $uploadDir,
                        $newFilename
                    );
                    $article->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image: ' . $e->getMessage());
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
    
    #[Route('/articles/{id}/edit', name: 'app_article_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Article $article, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est le propriétaire de l'article
        if ($article->getSeller() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier un article qui ne vous appartient pas.');
            return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
        }
        
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
        
        // Stocker l'ancienne image pour pouvoir la supprimer si nécessaire
        $oldImage = $article->getImage();
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload d'image
            if ($imageFile = $form->get('image')->getData()) {
                $slugger = new AsciiSlugger();
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                
                // Obtenir l'extension directement depuis le nom du fichier
                $originalExtension = pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION);
                
                // Vérifier si l'extension est valide
                if (!$this->isValidImageExtension($originalExtension)) {
                    $this->addFlash('error', 'Le format de l\'image n\'est pas valide. Utilisez JPG, PNG ou GIF.');
                    return $this->redirectToRoute('app_article_edit', ['id' => $article->getId()]);
                }
                
                $newFilename = $safeFilename.'-'.uniqid().'.'.$originalExtension;

                // Vérifier si le répertoire existe, sinon le créer
                $uploadDir = $this->getParameter('articles_directory');
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                try {
                    // Déplacer le fichier
                    $imageFile->move(
                        $uploadDir,
                        $newFilename
                    );
                    
                    // Supprimer l'ancienne image si elle existe
                    if ($oldImage && file_exists($uploadDir.'/'.$oldImage)) {
                        unlink($uploadDir.'/'.$oldImage);
                    }
                    
                    $article->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image: ' . $e->getMessage());
                    return $this->redirectToRoute('app_article_edit', ['id' => $article->getId()]);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Votre article a été modifié avec succès !');
            return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
        }

        return $this->render('article/edit.html.twig', [
            'form' => $form->createView(),
            'article' => $article
        ]);
    }
    
    #[Route('/articles/{id}/delete', name: 'app_article_delete', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Article $article, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est le propriétaire de l'article
        if ($this->getUser() !== $article->getSeller()) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer cet article.');
            return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
        }
        
        // Supprimer l'image associée si elle existe
        if ($article->getImage()) {
            $imagePath = $this->getParameter('articles_directory') . '/' . $article->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        // Supprimer l'article de la base de données
        $entityManager->remove($article);
        $entityManager->flush();
        
        $this->addFlash('success', 'L\'article a été supprimé avec succès.');
        return $this->redirectToRoute('app_articles');
    }
    
    /**
     * Vérifie si l'extension de fichier est une image valide
     */
    private function isValidImageExtension(string $extension): bool
    {
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        return in_array(strtolower($extension), $validExtensions);
    }
    
    /**
     * Détermine le type MIME en fonction de l'extension du fichier
     * Utilisé comme solution alternative à fileinfo
     */
    private function getMimeTypeFromExtension(string $extension): ?string
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];
        
        return $mimeTypes[strtolower($extension)] ?? null;
    }
} 