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