<?php

namespace App\Controller;

use App\Entity\Masterclass;
use App\Entity\MasterclassPage;
use App\Entity\MasterclassProgress;
use App\Repository\MasterclassRepository;
use App\Repository\MasterclassPageRepository;
use App\Repository\MasterclassProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/masterclass')]
class MasterclassController extends AbstractController
{
    #[Route('', name: 'app_masterclass_index')]
    public function index(MasterclassRepository $masterclassRepository): Response
    {
        $masterclasses = $masterclassRepository->findBy([], ['createdAt' => 'DESC']);
        
        return $this->render('masterclass/index.html.twig', [
            'masterclasses' => $masterclasses,
        ]);
    }
    
    #[Route('/new', name: 'app_masterclass_new')]
    #[IsGranted('ROLE_CERTIFIED')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Vérifier si l'utilisateur est certifié
        $user = $this->getUser();
        if (!$user || !$user->getIsCertified()) {
            $this->addFlash('error', 'Vous devez être certifié pour créer une masterclass.');
            return $this->redirectToRoute('app_masterclass_index');
        }
        
        $masterclass = new Masterclass();
        
        // Création du formulaire
        $form = $this->createFormBuilder($masterclass)
            ->add('title', null, [
                'label' => 'Titre',
                'attr' => ['placeholder' => 'Titre de votre masterclass']
            ])
            ->add('description', null, [
                'label' => 'Description',
                'attr' => ['rows' => 5, 'placeholder' => 'Description détaillée de votre masterclass...']
            ])
            ->add('price', null, [
                'label' => 'Prix (€)',
                'attr' => ['placeholder' => '0.00']
            ])
            ->add('imageFile', \Symfony\Component\Form\Extension\Core\Type\FileType::class, [
                'label' => 'Image de couverture',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, WEBP)',
                    ])
                ],
            ])
            ->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $masterclass->setAuthor($user);
            
            // Gestion de l'image
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                try {
                    $imageFile->move(
                        $this->getParameter('masterclasses_directory'),
                        $newFilename
                    );
                    $masterclass->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
                }
            }
            
            $entityManager->persist($masterclass);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre masterclass a été créée avec succès !');
            return $this->redirectToRoute('app_masterclass_manage_pages', ['id' => $masterclass->getId()]);
        }
        
        return $this->render('masterclass/new.html.twig', [
            'masterclass' => $masterclass,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}', name: 'app_masterclass_show', methods: ['GET'])]
    public function show(Masterclass $masterclass): Response
    {
        return $this->render('masterclass/show.html.twig', [
            'masterclass' => $masterclass,
        ]);
    }
    
    #[Route('/{id}/buy', name: 'app_masterclass_buy', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function buy(Masterclass $masterclass, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a déjà acheté cette masterclass
        if ($user->hasPurchasedMasterclass($masterclass)) {
            $this->addFlash('info', 'Vous avez déjà acheté cette masterclass.');
            return $this->redirectToRoute('app_masterclass_learn', ['id' => $masterclass->getId()]);
        }
        
        // Vérifier si l'utilisateur a assez d'argent
        if ($user->getBalance() < $masterclass->getPrice()) {
            $this->addFlash('error', 'Vous n\'avez pas assez d\'argent pour acheter cette masterclass.');
            return $this->redirectToRoute('app_masterclass_show', ['id' => $masterclass->getId()]);
        }
        
        // Effectuer l'achat
        $user->setBalance($user->getBalance() - $masterclass->getPrice());
        $user->addPurchasedMasterclass($masterclass);
        
        // Créer un enregistrement de progression
        $progress = new MasterclassProgress();
        $progress->setUser($user);
        $progress->setMasterclass($masterclass);
        $entityManager->persist($progress);
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Vous avez acheté la masterclass avec succès !');
        return $this->redirectToRoute('app_masterclass_learn', ['id' => $masterclass->getId()]);
    }
    
    #[Route('/{id}/learn', name: 'app_masterclass_learn')]
    #[IsGranted('ROLE_USER')]
    public function learn(Masterclass $masterclass, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a acheté cette masterclass
        if (!$user->hasPurchasedMasterclass($masterclass)) {
            $this->addFlash('error', 'Vous devez acheter cette masterclass pour y accéder.');
            return $this->redirectToRoute('app_masterclass_show', ['id' => $masterclass->getId()]);
        }
        
        // Récupérer la progression de l'utilisateur
        $progress = $user->getProgressForMasterclass($masterclass);
        
        // Si aucune progression n'existe, en créer une
        if (!$progress) {
            $progress = new MasterclassProgress();
            $progress->setUser($user);
            $progress->setMasterclass($masterclass);
            $entityManager->persist($progress);
            $entityManager->flush();
        }
        
        return $this->render('masterclass/learn.html.twig', [
            'masterclass' => $masterclass,
            'progress' => $progress,
        ]);
    }
    
    #[Route('/{id}/page/{pageId}', name: 'app_masterclass_page')]
    #[IsGranted('ROLE_USER')]
    public function page(Masterclass $masterclass, int $pageId, MasterclassPageRepository $pageRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a acheté cette masterclass
        if (!$user->hasPurchasedMasterclass($masterclass)) {
            $this->addFlash('error', 'Vous devez acheter cette masterclass pour y accéder.');
            return $this->redirectToRoute('app_masterclass_show', ['id' => $masterclass->getId()]);
        }
        
        // Récupérer la page
        $page = $pageRepository->find($pageId);
        
        if (!$page || $page->getMasterclass() !== $masterclass) {
            throw $this->createNotFoundException('Page non trouvée');
        }
        
        // Récupérer la progression de l'utilisateur
        $progress = $user->getProgressForMasterclass($masterclass);
        
        return $this->render('masterclass/page.html.twig', [
            'masterclass' => $masterclass,
            'page' => $page,
            'progress' => $progress,
        ]);
    }
    
    #[Route('/{id}/page/{pageId}/complete', name: 'app_masterclass_page_complete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function completePage(Masterclass $masterclass, int $pageId, MasterclassPageRepository $pageRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur a acheté cette masterclass
        if (!$user->hasPurchasedMasterclass($masterclass)) {
            $this->addFlash('error', 'Vous devez acheter cette masterclass pour y accéder.');
            return $this->redirectToRoute('app_masterclass_show', ['id' => $masterclass->getId()]);
        }
        
        // Récupérer la page
        $page = $pageRepository->find($pageId);
        
        if (!$page || $page->getMasterclass() !== $masterclass) {
            throw $this->createNotFoundException('Page non trouvée');
        }
        
        // Récupérer la progression de l'utilisateur
        $progress = $user->getProgressForMasterclass($masterclass);
        
        // Marquer la page comme complétée
        $progress->addCompletedPage($pageId);
        $entityManager->flush();
        
        $this->addFlash('success', 'Page marquée comme terminée !');
        
        // Rediriger vers la page suivante si elle existe
        $nextPage = $pageRepository->findNextPage($masterclass, $page->getPosition());
        
        if ($nextPage) {
            return $this->redirectToRoute('app_masterclass_page', [
                'id' => $masterclass->getId(),
                'pageId' => $nextPage->getId(),
            ]);
        }
        
        // Sinon, rediriger vers la page d'apprentissage
        return $this->redirectToRoute('app_masterclass_learn', ['id' => $masterclass->getId()]);
    }
    
    #[Route('/{id}/edit', name: 'app_masterclass_edit')]
    #[IsGranted('ROLE_CERTIFIED')]
    public function edit(Request $request, Masterclass $masterclass, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Vérifier si l'utilisateur est l'auteur de la masterclass
        if ($masterclass->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à modifier cette masterclass.');
            return $this->redirectToRoute('app_masterclass_show', ['id' => $masterclass->getId()]);
        }
        
        // Création du formulaire
        $form = $this->createFormBuilder($masterclass)
            ->add('title', null, [
                'label' => 'Titre',
                'attr' => ['placeholder' => 'Titre de votre masterclass']
            ])
            ->add('description', null, [
                'label' => 'Description',
                'attr' => ['rows' => 5, 'placeholder' => 'Description détaillée de votre masterclass...']
            ])
            ->add('price', null, [
                'label' => 'Prix (€)',
                'attr' => ['placeholder' => '0.00']
            ])
            ->add('imageFile', \Symfony\Component\Form\Extension\Core\Type\FileType::class, [
                'label' => 'Image de couverture',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, WEBP)',
                    ])
                ],
            ])
            ->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Mise à jour de la date de modification
            $masterclass->setUpdatedAt(new \DateTimeImmutable());
            
            // Gestion de l'image
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                try {
                    $imageFile->move(
                        $this->getParameter('masterclasses_directory'),
                        $newFilename
                    );
                    
                    // Supprimer l'ancienne image si elle existe
                    $oldImage = $masterclass->getImage();
                    if ($oldImage) {
                        $oldImagePath = $this->getParameter('masterclasses_directory').'/'.$oldImage;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    $masterclass->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
                }
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'La masterclass a été modifiée avec succès !');
            return $this->redirectToRoute('app_masterclass_show', ['id' => $masterclass->getId()]);
        }
        
        return $this->render('masterclass/edit.html.twig', [
            'masterclass' => $masterclass,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}/pages/manage', name: 'app_masterclass_manage_pages')]
    #[IsGranted('ROLE_CERTIFIED')]
    public function managePages(Masterclass $masterclass, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est l'auteur de la masterclass
        if ($masterclass->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à gérer les pages de cette masterclass.');
            return $this->redirectToRoute('app_masterclass_show', ['id' => $masterclass->getId()]);
        }
        
        // Créer une nouvelle page
        $page = new MasterclassPage();
        $page->setMasterclass($masterclass);
        $page->setPosition(count($masterclass->getPages()) + 1);
        
        // Créer le formulaire
        $form = $this->createFormBuilder($page)
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un titre']),
                    new Length(['min' => 3, 'max' => 255])
                ]
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer une position']),
                    new GreaterThan(['value' => 0])
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer du contenu']),
                ]
            ])
            ->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($page);
            $entityManager->flush();
            
            $this->addFlash('success', 'La page a été ajoutée avec succès.');
            return $this->redirectToRoute('app_masterclass_manage_pages', ['id' => $masterclass->getId()]);
        }
        
        return $this->render('masterclass/manage_pages.html.twig', [
            'masterclass' => $masterclass,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}/pages/new', name: 'app_masterclass_new_page')]
    #[IsGranted('ROLE_CERTIFIED')]
    public function newPage(Masterclass $masterclass, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est l'auteur de la masterclass
        if ($masterclass->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à ajouter des pages à cette masterclass.');
            return $this->redirectToRoute('app_masterclass_show', ['id' => $masterclass->getId()]);
        }
        
        $page = new MasterclassPage();
        $page->setMasterclass($masterclass);
        $page->setPosition(count($masterclass->getPages()) + 1);
        
        // Créer le formulaire
        $form = $this->createFormBuilder($page)
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un titre']),
                    new Length(['min' => 3, 'max' => 255])
                ]
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer une position']),
                    new GreaterThan(['value' => 0])
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer du contenu']),
                ]
            ])
            ->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($page);
            $entityManager->flush();
            
            $this->addFlash('success', 'La page a été ajoutée avec succès.');
            return $this->redirectToRoute('app_masterclass_manage_pages', ['id' => $masterclass->getId()]);
        }
        
        return $this->render('masterclass/new_page.html.twig', [
            'masterclass' => $masterclass,
            'page' => $page,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}/pages/{pageId}/edit', name: 'app_masterclass_edit_page')]
    #[IsGranted('ROLE_CERTIFIED')]
    public function editPage(Masterclass $masterclass, int $pageId, MasterclassPageRepository $pageRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est l'auteur de la masterclass
        if ($masterclass->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à modifier les pages de cette masterclass.');
            return $this->redirectToRoute('app_masterclass_show', ['id' => $masterclass->getId()]);
        }
        
        $page = $pageRepository->find($pageId);
        
        if (!$page || $page->getMasterclass() !== $masterclass) {
            throw $this->createNotFoundException('Page non trouvée');
        }
        
        // Créer le formulaire
        $form = $this->createFormBuilder($page)
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un titre']),
                    new Length(['min' => 3, 'max' => 255])
                ]
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer une position']),
                    new GreaterThan(['value' => 0])
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer du contenu']),
                ]
            ])
            ->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'La page a été modifiée avec succès.');
            return $this->redirectToRoute('app_masterclass_manage_pages', ['id' => $masterclass->getId()]);
        }
        
        return $this->render('masterclass/edit_page.html.twig', [
            'masterclass' => $masterclass,
            'page' => $page,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}/pages/{pageId}/delete', name: 'app_masterclass_delete_page')]
    #[IsGranted('ROLE_CERTIFIED')]
    public function deletePage(Masterclass $masterclass, int $pageId, MasterclassPageRepository $pageRepository, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est l'auteur de la masterclass
        if ($masterclass->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer les pages de cette masterclass.');
            return $this->redirectToRoute('app_masterclass_show', ['id' => $masterclass->getId()]);
        }
        
        $page = $pageRepository->find($pageId);
        
        if (!$page || $page->getMasterclass() !== $masterclass) {
            throw $this->createNotFoundException('Page non trouvée');
        }
        
        // Supprimer la page
        $entityManager->remove($page);
        $entityManager->flush();
        
        // Réorganiser les positions des autres pages
        $this->reorderPages($masterclass, $entityManager);
        
        $this->addFlash('success', 'La page a été supprimée avec succès.');
        return $this->redirectToRoute('app_masterclass_manage_pages', ['id' => $masterclass->getId()]);
    }
    
    #[Route('/{id}/pages/update-order', name: 'app_masterclass_update_pages_order', methods: ['POST'])]
    #[IsGranted('ROLE_CERTIFIED')]
    public function updatePagesOrder(Masterclass $masterclass, Request $request, MasterclassPageRepository $pageRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérifier si l'utilisateur est l'auteur de la masterclass
        if ($masterclass->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier l\'ordre des pages.'], 403);
        }
        
        // Vérifier le token CSRF
        $submittedToken = $request->headers->get('X-CSRF-TOKEN');
        if (!$this->isCsrfTokenValid('update-order', $submittedToken)) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide.'], 400);
        }
        
        // Récupérer les données JSON
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !is_array($data)) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides.'], 400);
        }
        
        // Mettre à jour les positions
        foreach ($data as $item) {
            if (!isset($item['id']) || !isset($item['position'])) {
                continue;
            }
            
            $page = $pageRepository->find($item['id']);
            
            if ($page && $page->getMasterclass() === $masterclass) {
                $page->setPosition($item['position']);
                $entityManager->persist($page);
            }
        }
        
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }
    
    /**
     * Réorganise les positions des pages d'une masterclass
     */
    private function reorderPages(Masterclass $masterclass, EntityManagerInterface $entityManager): void
    {
        $pages = $masterclass->getPages()->toArray();
        
        // Trier les pages par position
        usort($pages, function($a, $b) {
            return $a->getPosition() <=> $b->getPosition();
        });
        
        // Réaffecter les positions
        foreach ($pages as $index => $page) {
            $page->setPosition($index + 1);
            $entityManager->persist($page);
        }
        
        $entityManager->flush();
    }
    
    #[Route('/{id}/delete', name: 'app_masterclass_delete')]
    #[IsGranted('ROLE_CERTIFIED')]
    public function delete(Masterclass $masterclass, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est l'auteur de la masterclass
        if ($masterclass->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer cette masterclass.');
            return $this->redirectToRoute('app_masterclass_show', ['id' => $masterclass->getId()]);
        }
        
        // Supprimer l'image si elle existe
        $image = $masterclass->getImage();
        if ($image) {
            $imagePath = $this->getParameter('masterclasses_directory').'/'.$image;
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        // Supprimer la masterclass
        $entityManager->remove($masterclass);
        $entityManager->flush();
        
        $this->addFlash('success', 'La masterclass a été supprimée avec succès !');
        return $this->redirectToRoute('app_masterclass_index');
    }
}
