<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripeService
{
    private $params;
    private $urlGenerator;

    public function __construct(ParameterBagInterface $params, UrlGeneratorInterface $urlGenerator)
    {
        $this->params = $params;
        $this->urlGenerator = $urlGenerator;
        
        // Initialiser Stripe avec la clé secrète
        Stripe::setApiKey($this->params->get('app.stripe_secret_key'));
    }

    /**
     * Crée une session de paiement Stripe pour une commande
     */
    public function createCheckoutSession(Order $order): Session
    {
        $lineItems = [];
        
        // Ajouter chaque article de la commande comme un élément de ligne Stripe
        foreach ($order->getItems() as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item->getArticleName(),
                        'description' => 'Vendeur: ' . $item->getArticle()->getSeller()->getUsername(),
                    ],
                    'unit_amount' => (int)($item->getPrice() * 100), // Stripe utilise les centimes
                ],
                'quantity' => $item->getQuantity(),
            ];
        }

        // Créer la session de paiement
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $this->urlGenerator->generate('app_payment_success', [
                'orderId' => $order->getId(),
                'sessionId' => '{CHECKOUT_SESSION_ID}'
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->urlGenerator->generate('app_payment_cancel', [
                'orderId' => $order->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'customer_email' => $order->getUser()->getEmail(),
            'metadata' => [
                'order_id' => $order->getId()
            ]
        ]);

        // Mettre à jour la commande avec l'ID de session Stripe
        $order->setStripeSessionId($session->id);
        
        return $session;
    }

    /**
     * Vérifie le statut d'une session de paiement Stripe
     */
    public function checkSessionStatus(string $sessionId): array
    {
        $session = Session::retrieve($sessionId);
        
        return [
            'status' => $session->payment_status,
            'payment_intent' => $session->payment_intent
        ];
    }
} 