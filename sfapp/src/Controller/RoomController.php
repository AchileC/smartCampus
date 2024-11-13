<?php
//RoomController.php
namespace App\Controller;

use App\Entity\Room;
use App\Form\FilterRoomType;
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

class RoomController extends AbstractController
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
        $filterForm = $this->createForm(FilterRoomType::class);
        $filterForm->handleRequest($request);

        $criteria = [];
        $formSubmitted = $filterForm->isSubmitted() && $filterForm->isValid();

        if ($formSubmitted) {
            /** @var Room $data */
            $data = $filterForm->getData();

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

        $rooms = $roomRepository->findByCriteria($criteria);

        $deleteForms = [];
        foreach ($rooms as $room) {
            $deleteForms[$room->getName()] = $this->createDeleteForm($room->getName())->createView();
        }

        return $this->render('rooms/index.html.twig', [
            'rooms' => $rooms,
            'filterForm' => $filterForm->createView(),
            'deleteForms' => $deleteForms,
            'formSubmitted' => $formSubmitted,
        ]);
    }


    /**
     * Displays the details of a specific room.
     *
     * This method retrieves the room information based on its name
     * and renders the detail view with its properties.
     *
     * @param RoomRepository $roomRepository The repository to fetch room data.
     * @param string $name The name of the room to display.
     *
     * @return Response The response rendering the room details page.
     *
     */
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
        $form = $this->createForm(AddRoomType::class, $room, ['validation_groups' => ['Default', 'add']]);
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
    public function delete(string $name, RoomRepository $roomRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $submittedToken = $request->request->get('_token');

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('delete_room', $submittedToken)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $room = $roomRepository->findOneBy(['name' => $name]);

        if (!$room) {
            throw $this->createNotFoundException('Room not found');
        }

        $entityManager->remove($room);
        $entityManager->flush();

        $this->addFlash('success', 'La salle a été supprimée avec succès.');

        return $this->redirectToRoute('app_rooms');
    }


    #[Route('/rooms/{name}/update', name: 'app_rooms_update')]
    public function update(string $name, RoomRepository $roomRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $room = $roomRepository->findOneBy(['name' => $name]);

        if (!$room) {
            throw $this->createNotFoundException('Room not found');
        }

        $form = $this->createForm(AddRoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_rooms');
        }

        return $this->render('rooms/update.html.twig', [
            'form' => $form->createView(),
            'room' => $room,
        ]);
    }
}
