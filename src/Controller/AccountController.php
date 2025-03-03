<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserEditType;
use App\Repository\ArticleRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    #[Route('/account/{id}', name: 'app_account_user', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function index(
        Request $request, 
        ArticleRepository $articleRepository, 
        OrderRepository $orderRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Déterminer quel utilisateur afficher
        $requestedUserId = $request->attributes->get('id');
        $currentUser = $this->getUser();
        
        // Si un ID est spécifié, on affiche cet utilisateur
        if ($requestedUserId) {
            $user = $userRepository->find($requestedUserId);
            
            if (!$user) {
                throw $this->createNotFoundException('Utilisateur non trouvé');
            }
            
            $isOwnAccount = $currentUser->getId() === $user->getId();
        } else {
            // Sinon, on affiche le profil de l'utilisateur connecté
            $user = $currentUser;
            $isOwnAccount = true;
        }
        
        // Récupérer les articles publiés par l'utilisateur
        $publishedArticles = $articleRepository->findBy(['seller' => $user]);
        
        // Variables pour le compte personnel uniquement
        $purchasedOrders = [];
        $form = null;
        $formSubmitted = false;
        
        if ($isOwnAccount) {
            // Récupérer les commandes de l'utilisateur avec leurs éléments associés
            $purchasedOrders = $orderRepository->findOrdersWithItemsByUser($user);
            
            // Ajouter un message flash pour le débogage
            $this->addFlash('info', 'Utilisateur connecté: ' . $user->getUsername() . ' (ID: ' . $user->getId() . ')');
            $this->addFlash('info', 'Nombre de commandes trouvées: ' . count($purchasedOrders));
            
            // Formulaire de modification des informations personnelles
            $form = $this->createForm(UserEditType::class, $user);
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid()) {
                // Vérifier si le mot de passe a été modifié
                $plainPassword = $form->get('plainPassword')->getData();
                if ($plainPassword) {
                    $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                }
                
                $entityManager->flush();
                $this->addFlash('success', 'Vos informations ont été mises à jour avec succès.');
                $formSubmitted = true;
            }
        }
        
        // Déterminer l'onglet actif
        $activeTab = $request->query->get('tab', 'articles');
        
        return $this->render('account/index.html.twig', [
            'user' => $user,
            'isOwnAccount' => $isOwnAccount,
            'publishedArticles' => $publishedArticles,
            'purchasedOrders' => $purchasedOrders,
            'form' => $form ? $form->createView() : null,
            'formSubmitted' => $formSubmitted,
            'activeTab' => $activeTab
        ]);
    }
} 