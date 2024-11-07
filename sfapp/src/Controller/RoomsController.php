<?php

namespace App\Controller;

use App\Form\RoomForm;
use App\Repository\RoomRepository;
use App\Utils\FloorEnum;
use App\Entity\Room;
use App\Form\RoomType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Utils\RoomStateEnum;


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

            // Filtrage par nom si fourni
            if (!empty($data['name'])) {
                $criteria['name'] = $data['name'];
            }
            if ($data['floor']) {
                $criteria['floor'] = $data['floor'];
            }
            if ($data['state']) {
                $criteria['state'] = $data['state'];
            }
        }

        // Recherche des salles correspondant aux critères
        $rooms = $roomRepository->findByCriteria($criteria);

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

    #[Route('/add', name: 'app_rooms_add')]
    public function add(Request $request, RoomRepository $roomRepository, EntityManagerInterface $entityManager): Response
    {
        $room = new Room();

        $form = $this->createForm(RoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Utiliser l'Entity Manager pour persister et flusher
            $entityManager->persist($room);
            $entityManager->flush();

            return $this->redirectToRoute('app_rooms');
        }

        return $this->render('rooms/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/rooms/{name}/update', name: 'app_rooms_update')]
    public function update(string $name, RoomRepository $roomRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $room = $roomRepository->findOneBy(['name' => $name]);

        if (!$room) {
            throw $this->createNotFoundException('Room not found');
        }

        // Création du formulaire pour la mise à jour des informations de la salle
        $form = $this->createFormBuilder($room)
            ->add('name', TextType::class, ['label' => 'Room Name'])
            ->add('floor', ChoiceType::class, [
                'choices' => [
                    'Ground Floor' => FloorEnum::GROUND,
                    'First Floor' => FloorEnum::FIRST,
                    'Second Floor' => FloorEnum::SECOND,
                    'Third Floor' => FloorEnum::THIRD,
                ],
                'label' => 'Floor',
            ])
            ->add('state', ChoiceType::class, [
                'choices' => [
                    'OK' => RoomStateEnum::OK,
                    'Problem' => RoomStateEnum::PROBLEM,
                    'Critical' => RoomStateEnum::CRITICAL,
                ],
                'label' => 'State',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->getForm();

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush(); // Enregistre les modifications en base de données
            return $this->redirectToRoute('app_rooms'); // Redirige vers la liste des salles après mise à jour
        }

        return $this->render('rooms/update.html.twig', [
            'form' => $form->createView(),
            'room' => $room,
        ]);
    }

}
