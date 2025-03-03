<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard')]
    public function index(ArticleRepository $articleRepository, UserRepository $userRepository): Response
    {
        $totalArticles = count($articleRepository->findAll());
        $totalUsers = count($userRepository->findAll());
        $recentArticles = $articleRepository->findBy([], ['createdAt' => 'DESC'], 5);
        
        return $this->render('admin/dashboard.html.twig', [
            'totalArticles' => $totalArticles,
            'totalUsers' => $totalUsers,
            'recentArticles' => $recentArticles
        ]);
    }
    
    #[Route('/users', name: 'app_admin_users')]
    public function manageUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        
        return $this->render('admin/users.html.twig', [
            'users' => $users
        ]);
    }
    
    #[Route('/users/{id}/toggle-role', name: 'app_admin_toggle_role', methods: ['POST'])]
    public function toggleUserRole(User $user, EntityManagerInterface $entityManager): Response
    {
        // Ne pas permettre de modifier le rôle de l'utilisateur admin actuel
        if ($user === $this->getUser()) {
            $this->addFlash('danger', 'Vous ne pouvez pas modifier votre propre rôle.');
            return $this->redirectToRoute('app_admin_users');
        }
        
        $roles = $user->getRoles();
        
        // Si l'utilisateur a déjà le rôle ROLE_ADMIN, on le retire
        if (in_array('ROLE_ADMIN', $roles)) {
            $roles = array_diff($roles, ['ROLE_ADMIN']);
            $this->addFlash('success', 'Rôle administrateur retiré pour ' . $user->getUsername());
        } else {
            // Sinon, on l'ajoute
            $roles[] = 'ROLE_ADMIN';
            $this->addFlash('success', 'Rôle administrateur ajouté pour ' . $user->getUsername());
        }
        
        $user->setRoles($roles);
        $entityManager->flush();
        
        return $this->redirectToRoute('app_admin_users');
    }
    
    #[Route('/users/{id}/delete', name: 'app_admin_delete_user', methods: ['POST'])]
    public function deleteUser(User $user, EntityManagerInterface $entityManager): Response
    {
        // Ne pas permettre de supprimer l'utilisateur admin actuel
        if ($user === $this->getUser()) {
            $this->addFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_admin_users');
        }
        
        $username = $user->getUsername();
        
        $entityManager->remove($user);
        $entityManager->flush();
        
        $this->addFlash('success', 'Utilisateur ' . $username . ' supprimé avec succès.');
        
        return $this->redirectToRoute('app_admin_users');
    }
    
    #[Route('/articles', name: 'app_admin_articles')]
    public function manageArticles(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findAll();
        
        return $this->render('admin/articles.html.twig', [
            'articles' => $articles
        ]);
    }
    
    #[Route('/articles/{id}/delete', name: 'app_admin_delete_article', methods: ['POST'])]
    public function deleteArticle(Article $article, EntityManagerInterface $entityManager): Response
    {
        $articleName = $article->getName();
        
        // Supprimer l'image associée si elle existe
        if ($article->getImage()) {
            $imagePath = $this->getParameter('articles_directory') . '/' . $article->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        $entityManager->remove($article);
        $entityManager->flush();
        
        $this->addFlash('success', 'Article "' . $articleName . '" supprimé avec succès.');
        
        return $this->redirectToRoute('app_admin_articles');
    }
} 