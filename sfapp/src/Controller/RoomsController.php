<?php

namespace App\Controller;

use App\Entity\Room;
use App\Form\RoomType;
use App\Form\AddRoomType;
use App\Repository\RoomRepository;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RoomsController extends AbstractController
{
    private function createDeleteForm(string $name) : FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('app_rooms_delete', ['name' => $name]))
            ->setMethod('POST')
            ->getForm();
    }

    #[Route('/rooms', name: 'app_rooms')]
    public function index(RoomRepository $roomRepository, Request $request): Response
    {
        // Formulaire de Filtrage
        $filterForm = $this->createForm(RoomType::class);
        $filterForm->handleRequest($request);

        $criteria = [];

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            /** @var Room $data */
            $data = $filterForm->getData();  // $data est un objet Room

            // Utilisation des getters pour accéder aux données de $data
            if (!empty($data->getName())) {
                $criteria['name'] = $data->getName();
            }

            if ($data->getFloor()) {
                $criteria['floor'] = $data->getFloor();
            }

            if ($data->getState()) {
                $criteria['state'] = $data->getState();
            }
        }

        // Recherche des salles correspondant aux critères
        $rooms = $roomRepository->findByCriteria($criteria);

        // Générer un formulaire de suppression pour chaque salle
        $deleteForms = [];
        foreach ($rooms as $room) {
            $deleteForms[$room->getName()] = $this->createDeleteForm($room->getName())->createView();
        }

        return $this->render('rooms/index.html.twig', [
            'rooms' => $rooms,
            'filterForm' => $filterForm->createView(),
            'deleteForms' => $deleteForms, // Passer tous les formulaires à la vue
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
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $room = new Room();
        $form = $this->createForm(AddRoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($room);
            $entityManager->flush();

            return $this->redirectToRoute('app_rooms');
        }

        return $this->render('rooms/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/rooms/{name}/delete', name: 'app_rooms_delete', methods: ['POST'])]
    public function delete(string $name, RoomRepository $roomRepository, EntityManagerInterface $entityManager): Response
    {
        $room = $roomRepository->findOneBy(['name' => $name]);

        if (!$room) {
            throw $this->createNotFoundException('Room not found');
        }

        // Supprime la salle
        $entityManager->remove($room);
        $entityManager->flush();

        // Redirige vers la liste des salles après la suppression
        return $this->redirectToRoute('app_rooms');
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
            ->add('save', SubmitType::class, [
                'label' => 'Save Changes',
                'attr' => ['class' => 'btn btn-primary'],
            ])
            ->getForm();

        $form->handleRequest($request);

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
