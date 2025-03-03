<?php

namespace App\Command;

use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-orders',
    description: 'Test orders retrieval',
)]
class TestOrdersCommand extends Command
{
    private $userRepository;
    private $orderRepository;

    public function __construct(UserRepository $userRepository, OrderRepository $orderRepository)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->orderRepository = $orderRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Récupérer tous les utilisateurs
        $users = $this->userRepository->findAll();
        
        $io->title('Test de récupération des commandes');
        
        foreach ($users as $user) {
            $io->section('Utilisateur: ' . $user->getUsername() . ' (ID: ' . $user->getId() . ')');
            
            // Méthode standard
            $orders = $this->orderRepository->findBy(['user' => $user]);
            $io->text('Nombre de commandes (findBy): ' . count($orders));
            
            // Méthode personnalisée
            $ordersWithItems = $this->orderRepository->findOrdersWithItemsByUser($user);
            $io->text('Nombre de commandes (findOrdersWithItemsByUser): ' . count($ordersWithItems));
            
            if (count($ordersWithItems) > 0) {
                $io->table(
                    ['ID', 'Référence', 'Total', 'Status', 'Date', 'Nb Items'],
                    array_map(function($order) {
                        return [
                            $order->getId(),
                            $order->getReference(),
                            $order->getTotal(),
                            $order->getStatus(),
                            $order->getCreatedAt()->format('Y-m-d H:i:s'),
                            count($order->getItems())
                        ];
                    }, $ordersWithItems)
                );
            }
        }
        
        $io->success('Test terminé');

        return Command::SUCCESS;
    }
} 