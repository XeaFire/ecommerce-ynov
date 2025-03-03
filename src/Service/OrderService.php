<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Article;
use App\Entity\OrderItem;
use App\Entity\Cart;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderService
{
    private $entityManager;
    private $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * Ajoute un message flash
     */
    private function addFlash(string $type, string $message): void
    {
        if ($this->requestStack->getSession()) {
            $this->requestStack->getSession()->getFlashBag()->add($type, $message);
        }
    }

    /**
     * Vérifie si tous les articles du panier sont disponibles en stock
     * 
     * @param Cart $cart Le panier à vérifier
     * @return array [bool $isValid, array $errorMessages]
     */
    public function validateCartStock(Cart $cart): array
    {
        $stockError = false;
        $errorMessages = [];
        
        foreach ($cart->getItems() as $cartItem) {
            if ($cartItem->getItemType() === 'article' && $cartItem->getArticle()) {
                $article = $cartItem->getArticle();
                // Récupérer l'article frais depuis la base de données pour éviter les problèmes de cache
                $article = $this->entityManager->getRepository(Article::class)->find($article->getId());
                
                if ($article->getQuantity() < $cartItem->getQuantity()) {
                    $stockError = true;
                    $errorMessages[] = 'Stock insuffisant pour l\'article "' . $article->getName() . '". Disponible: ' . $article->getQuantity() . ', Demandé: ' . $cartItem->getQuantity();
                }
            }
            // Les masterclasses n'ont pas de contrainte de stock
        }
        
        return [$stockError === false, $errorMessages];
    }

    /**
     * Vérifie si tous les articles de la commande sont disponibles en stock
     * 
     * @param Order $order La commande à vérifier
     * @return array [bool $isValid, array $errorMessages]
     */
    public function validateOrderStock(Order $order): array
    {
        $stockError = false;
        $errorMessages = [];
        
        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->getItemType() === 'article' && $orderItem->getArticle()) {
                $article = $orderItem->getArticle();
                // Récupérer l'article frais depuis la base de données pour éviter les problèmes de cache
                $article = $this->entityManager->getRepository(Article::class)->find($article->getId());
                
                if ($article->getQuantity() < $orderItem->getQuantity()) {
                    $stockError = true;
                    $errorMessages[] = 'Stock insuffisant pour l\'article "' . $article->getName() . '". Disponible: ' . $article->getQuantity() . ', Demandé: ' . $orderItem->getQuantity();
                }
            }
            // Les masterclasses n'ont pas de contrainte de stock
        }
        
        return [$stockError === false, $errorMessages];
    }

    /**
     * Met à jour le stock des articles après une commande
     * 
     * @param Order $order La commande dont les articles doivent être mis à jour
     * @return bool True si la mise à jour a réussi, False sinon
     */
    public function updateStock(Order $order): bool
    {
        // Vérifier d'abord si tous les articles sont disponibles en stock
        [$isValid, $errorMessages] = $this->validateOrderStock($order);
        
        // Si un problème de stock est détecté, annuler la commande
        if (!$isValid) {
            $order->setStatus('cancelled');
            foreach ($errorMessages as $message) {
                $this->addFlash('error', $message);
            }
            $this->addFlash('error', 'La commande a été annulée en raison d\'un problème de stock.');
            $this->entityManager->flush();
            return false;
        }
        
        // Si tout est disponible, mettre à jour le stock
        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->getItemType() === 'article' && $orderItem->getArticle()) {
                $article = $orderItem->getArticle();
                // Récupérer l'article frais depuis la base de données
                $article = $this->entityManager->getRepository(Article::class)->find($article->getId());
                
                if ($article) {
                    // Réduire la quantité en stock
                    $newQuantity = max(0, $article->getQuantity() - $orderItem->getQuantity());
                    $article->setQuantity($newQuantity);
                }
            }
            
            // Pour les masterclasses, ajouter l'accès à l'utilisateur
            if ($orderItem->getItemType() === 'masterclass' && $orderItem->getMasterclass()) {
                $masterclass = $orderItem->getMasterclass();
                $user = $order->getUser();
                
                if ($masterclass && $user) {
                    // Ajouter la masterclass aux achats de l'utilisateur s'il ne l'a pas déjà
                    if (!$user->getPurchasedMasterclasses()->contains($masterclass)) {
                        $user->addPurchasedMasterclass($masterclass);
                    }
                }
            }
        }
        
        $this->entityManager->flush();
        return true;
    }

    /**
     * Accepte automatiquement une commande et met à jour le stock
     * 
     * @param Order $order La commande à accepter
     * @return bool True si l'acceptation a réussi, False sinon
     */
    public function acceptOrder(Order $order): bool
    {
        // Vérifier si la commande est déjà complétée ou annulée
        if ($order->getStatus() === 'completed' || $order->getStatus() === 'cancelled') {
            return false;
        }
        
        // Mettre à jour le stock
        if (!$this->updateStock($order)) {
            return false;
        }
        
        // Mettre à jour le statut de la commande
        $order->setStatus('completed');
        $order->setPaidAt(new \DateTimeImmutable());
        $this->entityManager->flush();
        
        return true;
    }

    /**
     * Annule une commande
     * 
     * @param Order $order La commande à annuler
     * @return bool True si l'annulation a réussi, False sinon
     */
    public function cancelOrder(Order $order): bool
    {
        // Vérifier si la commande est déjà complétée
        if ($order->getStatus() === 'completed') {
            // Si la commande est déjà complétée, on ne peut pas l'annuler
            $this->addFlash('error', 'Impossible d\'annuler une commande déjà complétée.');
            return false;
        }
        
        // Mettre à jour le statut de la commande
        $order->setStatus('cancelled');
        $this->entityManager->flush();
        
        return true;
    }
} 