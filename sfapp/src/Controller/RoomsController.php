<?php

namespace App\Controller;

use App\Repository\RoomRepository;
use App\Utils\FloorEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use App\Entity\Room;


class RoomsController extends AbstractController
{
    #[Route('/rooms', name: 'app_rooms')]
    public function index(RoomRepository $roomRepository, Request $request): Response
    {
        // filter form
        $form = $this->createFormBuilder()
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
}
