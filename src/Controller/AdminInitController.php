<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class AdminInitController extends AbstractController
{
    #[Route('/admin/init', name: 'app_admin_init')]
    public function index(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Vérifier si l'utilisateur admin existe déjà
        $existingAdmin = $entityManager->getRepository(User::class)->findOneBy([
            'username' => 'admin'
        ]);

        if ($existingAdmin) {
            $this->addFlash('info', 'Un compte administrateur existe déjà.');
            return $this->redirectToRoute('app_login');
        }

        // Créer un nouvel utilisateur admin
        $user = new User();
        $user->setUsername('admin');
        $user->setEmail('admin@gmail.com');
        $user->setRoles(['ROLE_ADMIN']);

        // Hasher le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, 'admin');
        $user->setPassword($hashedPassword);

        // Persister l'utilisateur
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Compte administrateur créé avec succès. Email: admin@gmail.com, Mot de passe: admin');
        return $this->redirectToRoute('app_login');
    }
} 