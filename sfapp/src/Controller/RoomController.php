<?php
// RoomController.php
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

/**
 * Class RoomController
 *
 * Controller to handle operations related to Room entities.
 */
class RoomController extends AbstractController
{
    /**
     * Creates a delete form for a specific room.
     *
     * This form is used to confirm the deletion of a room.
     *
     * @param string $name The name of the room to delete.
     * @return FormInterface The form used to delete a room.
     */
    private function createDeleteForm(string $name) : FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('app_rooms_delete', ['name' => $name]))
            ->setMethod('POST')
            ->getForm();
    }

    /**
     * Displays a list of rooms.
     *
     * The method includes filtering options based on criteria.
     * Users can filter by name, floor, or state. If no room matches the criteria, a message is displayed.
     *
     * @Route("/rooms", name="app_rooms")
     *
     * @param RoomRepository $roomRepository The repository to retrieve room data.
     * @param Request $request The HTTP request object.
     *
     * @return Response The response rendering the room list page.
     */
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
     * @Route("/rooms/{name}", name="app_rooms_details")
     *
     * @param RoomRepository $roomRepository The repository to fetch room data.
     * @param string $name The name of the room to display.
     *
     * @return Response The response rendering the room details page.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If the room is not found.
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

    /**
     * Adds a new room to the database.
     *
     * This method allows users to add a new room. It includes form validation
     * and persists the new room entity if the form is successfully submitted.
     *
     * @Route("/add", name="app_rooms_add")
     *
     * @param Request $request The HTTP request object.
     * @param EntityManagerInterface $entityManager The entity manager to persist room data.
     *
     * @return Response The response rendering the add room form or redirecting to the room list page.
     */
    #[Route('/add', name: 'app_rooms_add')]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $room = new Room();
        $room->setState(RoomStateEnum::NOT_LINKED);
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

    /**
     * Deletes a specific room from the database.
     *
     * The method checks if the CSRF token is valid before deleting the room.
     * If the room does not exist, it throws a 404 error.
     *
     * @Route("/rooms/{name}/delete", name="app_rooms_delete", methods={"POST"})
     *
     * @param string $name The name of the room to delete.
     * @param RoomRepository $roomRepository The repository to fetch room data.
     * @param EntityManagerInterface $entityManager The entity manager to remove the room.
     * @param Request $request The HTTP request object.
     *
     * @return Response Redirects to the list of rooms after successful deletion.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException If the CSRF token is invalid.
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If the room is not found.
     */
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
