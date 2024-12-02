<?php
// RoomController.php
namespace App\Controller;

use App\Entity\Room;
use App\Form\FilterRoomType;
use App\Form\AddRoomType;
use App\Repository\RoomRepository;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

        if ($filterForm->get('reset')->isClicked()) {
            // Redirige vers la même page sans les filtres
            return $this->redirectToRoute('app_rooms');
        }

        if ($formSubmitted) {
            /** @var Room $data */
            $data = $filterForm->getData();

            if (!empty($data->getName()))  {
                $criteria['name'] = $data->getName();
            }

            if ($data->getFloor()) {
                $criteria['floor'] = $data->getFloor();
            }

            if ($data->getState()) {
                $criteria['state'] = $data->getState();
            }

            if ($filterForm->get('sensorStatus')->getData()) {
                $criteria['sensorStatus'] = ['linked', 'probably broken'];
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
            'optionsEnabled' => false,
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
    #[Route('/rooms/add', name: 'app_rooms_add')]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $room = new Room();
        $room->setSensorState(SensorStateEnum::NOT_LINKED);
        $room->setState(RoomStateEnum::NONE);
        $form = $this->createForm(AddRoomType::class, $room, ['validation_groups' => ['Default', 'add']]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->persist($room);
                $entityManager->flush();

                $this->addFlash('success', 'Room added successfully.');

                return $this->redirectToRoute('app_rooms');
            }
        }

        return $this->render('rooms/add.html.twig', [
            'form' => $form->createView(),
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
     * Updates an existing room based on the provided name.
     *
     * This function retrieves a room entity by its name, creates a form to edit it, and handles
     * the form submission. If the form is valid, it persists the changes in the database.
     * If the room is not found, a 404 error is thrown.
     *
     * @param string $name The name of the room to update.
     * @param RoomRepository $roomRepository The repository to fetch the room data from the database.
     * @param Request $request The HTTP request object that contains the form data.
     * @param EntityManagerInterface $entityManager The entity manager to handle database transactions.
     *
     * @return Response A redirect to the 'app_rooms' route if the form is successfully submitted,
     *                  or renders the update form view if the form is not submitted or invalid.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If the room is not found in the database.
     *
     * @Route("/rooms/{name}/update", name="app_rooms_update")
     */
    #[Route('/rooms/{name}/update', name: 'app_rooms_update')]
    public function update(string $name, RoomRepository $roomRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $room = $roomRepository->findOneBy(['name' => $name]);

        if (!$room) {
            throw $this->createNotFoundException('Room not found');
        }

        $form = $this->createForm(AddRoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->flush();

                $this->addFlash('success', 'Room updated successfully.');

                return $this->redirectToRoute('app_rooms');
            }
        }

        return $this->render('rooms/update.html.twig', [
            'form' => $form->createView(),
            'room' => $room,
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

        $acquisitionSystem = $room->getAcquisitionSystem();
        if ($acquisitionSystem !== null) {
            $acquisitionSystem->setRoom(null);
            $entityManager->persist($acquisitionSystem);
        }
        $entityManager->remove($room);
        $entityManager->flush();

        $this->addFlash('success', 'Room deleted successfully.');

        return $this->redirectToRoute('app_rooms');
    }

    /**
     * Initiates the request for an assignment of an acquisition system to a room.
     *
     * This method sets the state of the specified room to "PENDING_ASSIGNMENT",
     * indicating that the room is awaiting the assignment of an acquisition system.
     *
     * @Route("/rooms/{name}/request-assignment", name="app_rooms_request_assignment", methods={"POST"})
     *
     * @param string $name The name of the room to assign an acquisition system.
     * @param RoomRepository $roomRepository The repository used to fetch room data.
     * @param EntityManagerInterface $entityManager The entity manager used to persist data.
     *
     * @return Response A response that redirects to the room list page.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If the room is not found.
     */
    #[Route('/rooms/{name}/request-assignment', name: 'app_rooms_request_assignment', methods: ['POST'])]
    public function requestInstallation(string $name, RoomRepository $roomRepository, EntityManagerInterface $entityManager): Response
    {
        $room = $roomRepository->findOneBy(['name' => $name]);

        if (!$room) {
            throw $this->createNotFoundException('Room not found');
        }

        $room->setState(RoomStateEnum::WAITING);
        $room->setSensorState(SensorStateEnum::ASSIGNMENT);
        $entityManager->flush();

        return $this->redirectToRoute('app_rooms');
    }

    /**
     * Initiates the request to unassign an acquisition system from a room.
     *
     * This method sets the state of the specified room to "PENDING_UNASSIGNMENT",
     * and saves the current state as "previousState" to allow restoration if needed.
     *
     * @Route("/rooms/{name}/request-unassignment", name="app_rooms_request_unassignment", methods={"POST"})
     *
     * @param string $name The name of the room from which to unassign the acquisition system.
     * @param RoomRepository $roomRepository The repository used to fetch room data.
     * @param EntityManagerInterface $entityManager The entity manager used to persist data.
     *
     * @return Response A response that redirects to the room list page.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If the room is not found.
     */
    #[Route('/rooms/{name}/request-unassignment', name: 'app_rooms_request_unassignment', methods: ['POST'])]
    public function requestUnassignment(string $name, RoomRepository $roomRepository, EntityManagerInterface $entityManager): Response
    {
        $room = $roomRepository->findOneBy(['name' => $name]);

        if (!$room) {
            throw $this->createNotFoundException('Room not found');
        }

        $room->setPreviousState($room->getState());
        $room->setPreviousSensorState($room->getSensorState());
        $room->setState(RoomStateEnum::WAITING);
        $room->setSensorState(SensorStateEnum::UNASSIGNMENT);
        $entityManager->flush();

        return $this->redirectToRoute('app_rooms');
    }

    /**
     * Cancels the assignment or unassignment of an acquisition system to/from a room.
     *
     * This method restores the room state to the previous state if it was in "PENDING_UNASSIGNMENT".
     * If the room was in "PENDING_ASSIGNMENT", it changes the state to "NOT_LINKED".
     *
     * @Route("/rooms/{name}/cancel-installation", name="app_rooms_cancel_installation", methods={"POST"})
     *
     * @param string $name The name of the room for which the installation request is being canceled.
     * @param RoomRepository $roomRepository The repository used to fetch room data.
     * @param EntityManagerInterface $entityManager The entity manager used to persist data.
     *
     * @return Response A response that redirects to the room list page.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If the room is not found.
     */
    #[Route('/rooms/{name}/cancel-installation', name: 'app_rooms_cancel_installation', methods: ['POST'])]
    public function cancelInstallation(string $name, RoomRepository $roomRepository, EntityManagerInterface $entityManager): Response
    {
        $room = $roomRepository->findOneBy(['name' => $name]);

        if (!$room) {
            throw $this->createNotFoundException('Room not found');
        }

        if ($room->getSensorState() == SensorStateEnum::ASSIGNMENT) {
            $room->setState(RoomStateEnum::NONE);
            $room->setSensorState(SensorStateEnum::NOT_LINKED);
        }
        elseif ($room->getSensorState() == SensorStateEnum::UNASSIGNMENT) {
            $room->setState($room->getPreviousState());
            $room->setSensorState($room->getPreviousSensorState());
        }
        $entityManager->flush();

        return $this->redirectToRoute('app_rooms');
    }
}
