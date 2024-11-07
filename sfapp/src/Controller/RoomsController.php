<?php

namespace App\Controller;

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


class RoomsController extends AbstractController
{
    #[Route('/rooms', name: 'app_rooms')]
    public function index(RoomRepository $roomRepository, Request $request): Response
    {
        // Formulaire de filtrage
        $form = $this->createFormBuilder()
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Room Name',
                'attr' => ['placeholder' => 'Search by room name'],
            ])
            ->add('floor', ChoiceType::class, [
                'choices' => [
                    'Ground' => FloorEnum::GROUND->value,
                    'First' => FloorEnum::FIRST->value,
                    'Second' => FloorEnum::SECOND->value,
                    'Third' => FloorEnum::THIRD->value,
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

}
