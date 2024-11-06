<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RoomsController extends AbstractController
{
    #[Route('/rooms', name: 'app_rooms')]
    public function index(): Response
    {
        // Utilisation de render() pour retourner une vue Twig
        return $this->render('rooms/index.html.twig', [
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/RoomsController.php',
        ]);
    }
}
