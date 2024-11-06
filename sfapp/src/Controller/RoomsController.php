<?php

namespace App\Controller;

use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Room;


class RoomsController extends AbstractController
{
    #[Route('/rooms', name: 'app_rooms')]
    public function index(RoomRepository $roomRepository): Response
    {
        // Fetch all rooms, without filtering by name
        $rooms = $roomRepository->findBy([], ['name' => 'ASC']);

        return $this->render('rooms/index.html.twig', [
            'rooms' => $rooms,
        ]);
    }
}
