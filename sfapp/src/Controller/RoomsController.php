<?php

namespace App\Controller;

use App\Form\RoomForm;
use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RoomsController extends AbstractController
{
    #[Route('/rooms', name: 'app_rooms')]
    public function index(RoomRepository $roomRepository, Request $request): Response
    {
        // Utilisation de RoomForm
        $form = $this->createForm(RoomForm::class);
        $form->handleRequest($request);

        $criteria = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($data['floor']) {
                $criteria['floor'] = $data['floor'];
            }
            if ($data['state']) {
                $criteria['state'] = $data['state'];
            }
        }

        $rooms = $roomRepository->findBy($criteria, ['name' => 'ASC']);

        return $this->render('rooms/index.html.twig', [
            'rooms' => $rooms,
            'form' => $form->createView(),
            'formSubmitted' => $form->isSubmitted(),
        ]);
    }

    #[Route('/rooms/{name}', name: 'app_rooms_details')]
    public function details(RoomRepository $roomRepository, string $name): Response
    {
        $room = $roomRepository->findOneBy(['name' => $name]);

        if (!$room) {
            throw $this->createNotFoundException('The room does not exist');
        }

        return $this->render('rooms/detail.html.twig', [
            'room' => $room,
        ]);
    }
}
