<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LoginController
{
    #[Route('/login')]
    public function number(): Response
    {
        $number = random_int(0, 100);

        return new Response(
            '<html><body><p>Salut tritan</p></body></html>'
        );
    }
}