<?php

namespace App\Controller;

use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;



use App\Entity\Room;


class RoomsController extends AbstractController
{
    #[Route('/rooms', name: 'app_rooms')]
    public function index(RoomRepository $roomRepository, Request $request): Response
    {
        // filter formulaire
        $form = $this->createFormBuilder()
            ->add('floor', ChoiceType::class, [
                'choices' => [
                    'Ground' => 'ground',
                    'First' => 'first',
                    'Second' => 'second',
                ],
                'required' => false,
                'placeholder' => 'Choose Floor',
            ])
            ->add('state', ChoiceType::class, [
                'choices' => [
                    'OK' => 'ok',
                    'Problem' => 'problem',
                    'Critical' => 'critical',
                ],
                'required' => false,
                'placeholder' => 'Select a State',
            ])
            ->add('filter', SubmitType::class, ['label' => 'Filter'])
            ->getForm();

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
