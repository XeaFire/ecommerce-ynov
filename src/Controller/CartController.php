<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Masterclass;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    #[Route('', name: 'app_cart')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $cart = $user->getCart();

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $entityManager->persist($cart);
            $entityManager->flush();
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add')]
    public function add(Article $article, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $cart = $user->getCart();

        // Si l'utilisateur n'a pas encore de panier, on en crée un
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $entityManager->persist($cart);
            $entityManager->flush(); // Flush pour s'assurer que le panier a un ID
        }

        // Vérifier si l'article est disponible en stock
        if ($article->getQuantity() <= 0) {
            $this->addFlash('error', 'Cet article n\'est plus disponible en stock.');
            return $this->redirectToRoute('app_articles');
        }

        // Vérifier si l'article existe déjà dans le panier
        $existingItem = null;
        foreach ($cart->getItems() as $item) {
            if ($item->getArticle()->getId() === $article->getId()) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            // Si l'article existe déjà, on vérifie que la nouvelle quantité ne dépasse pas le stock
            $newQuantity = $existingItem->getQuantity() + 1;
            
            // Vérifier si la quantité demandée est disponible en stock
            if ($newQuantity > $article->getQuantity()) {
                $this->addFlash('error', 'Vous ne pouvez pas ajouter plus d\'exemplaires de cet article (stock insuffisant).');
                return $this->redirectToRoute('app_cart');
            }
            
            $existingItem->setQuantity($newQuantity);
        } else {
            // Sinon, on crée un nouvel élément
            $cartItem = new CartItem();
            $cartItem->setArticle($article);
            $cartItem->setQuantity(1); // Par défaut, on ajoute 1 article
            $cartItem->setCart($cart); // Définir explicitement la relation
            $entityManager->persist($cartItem);
        }
        
        // Mettre à jour la date de mise à jour du panier
        $cart->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('success', 'Article ajouté au panier !');

        // Rediriger vers la page précédente ou la liste des articles
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_articles');
    }

    #[Route('/remove/{id}', name: 'app_cart_remove')]
    public function remove(CartItem $cartItem, EntityManagerInterface $entityManager): Response
    {
        $cart = $cartItem->getCart();
        
        // Vérifier que l'utilisateur est bien le propriétaire du panier
        if ($cart->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier ce panier.');
        }
        
        $cart->removeItem($cartItem);
        $cart->setUpdatedAt(new \DateTimeImmutable());
        
        $entityManager->remove($cartItem);
        $entityManager->flush();
        
        $this->addFlash('success', 'Article retiré du panier !');
        
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/update/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(CartItem $cartItem, Request $request, EntityManagerInterface $entityManager): Response
    {
        $cart = $cartItem->getCart();
        
        // Vérifier que l'utilisateur est bien le propriétaire du panier
        if ($cart->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier ce panier.');
        }
        
        $quantity = (int) $request->request->get('quantity', 1);
        
        // Vérifier que la quantité est valide
        if ($quantity <= 0) {
            // Si la quantité est 0 ou négative, on supprime l'article du panier
            return $this->redirectToRoute('app_cart_remove', ['id' => $cartItem->getId()]);
        }
        
        // Vérifier si la quantité demandée est disponible en stock
        if ($quantity > $cartItem->getArticle()->getQuantity()) {
            $this->addFlash('error', 'La quantité demandée n\'est pas disponible en stock.');
            return $this->redirectToRoute('app_cart');
        }
        
        $cartItem->setQuantity($quantity);
        $cart->setUpdatedAt(new \DateTimeImmutable());
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Quantité mise à jour !');
        
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/clear', name: 'app_cart_clear')]
    public function clear(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $cart = $user->getCart();
        
        if ($cart) {
            // Supprimer tous les éléments du panier
            foreach ($cart->getItems() as $item) {
                $entityManager->remove($item);
            }
            
            $cart->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre panier a été vidé !');
        }
        
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/add-masterclass/{id}', name: 'app_cart_add_masterclass')]
    public function addMasterclass(Masterclass $masterclass, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $cart = $user->getCart();

        // Si l'utilisateur n'a pas encore de panier, on en crée un
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $entityManager->persist($cart);
            $entityManager->flush(); // Flush pour s'assurer que le panier a un ID
        }

        // Vérifier si l'utilisateur a déjà acheté cette masterclass
        if ($user->hasPurchasedMasterclass($masterclass)) {
            $this->addFlash('info', 'Vous avez déjà acheté cette masterclass.');
            return $this->redirectToRoute('app_masterclass_learn', ['id' => $masterclass->getId()]);
        }

        // Vérifier si la masterclass est déjà dans le panier
        $existingItem = null;
        foreach ($cart->getItems() as $item) {
            if ($item->getItemType() === 'masterclass' && $item->getMasterclass() && $item->getMasterclass()->getId() === $masterclass->getId()) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            // Si la masterclass est déjà dans le panier, on ne fait rien
            $this->addFlash('info', 'Cette masterclass est déjà dans votre panier.');
        } else {
            // Sinon, on crée un nouvel élément
            $cartItem = new CartItem();
            $cartItem->setMasterclass($masterclass);
            $cartItem->setQuantity(1); // Les masterclasses sont toujours achetées en quantité 1
            $cartItem->setCart($cart);
            $entityManager->persist($cartItem);
            
            // Mettre à jour la date de mise à jour du panier
            $cart->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Masterclass ajoutée au panier !');
        }

        // Rediriger vers le panier
        return $this->redirectToRoute('app_cart');
    }
}
