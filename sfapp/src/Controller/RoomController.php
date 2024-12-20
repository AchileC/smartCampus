<?php
// RoomController.php
namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Room;
use App\Entity\Action;
use App\Repository\ActionRepository;
use App\Repository\AcquisitionSystemRepository;
use App\Form\FilterRoomType;
use App\Form\AddRoomType;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use App\Utils\ActionInfoEnum;
use App\Utils\ActionStateEnum;
use App\Service\WeatherApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ThresholdRepository;

/**
 * Class RoomController
 *
 * Controller to handle operations related to Room entities.
 */
class RoomController extends AbstractController
{

    private WeatherApiService $weatherApiService;

    public function __construct(WeatherApiService $weatherApiService)
    {
        $this->weatherApiService = $weatherApiService;
    }

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
     * @param RoomRepository $roomRepository The repository to retrieve room data.
     * @param Request $request The HTTP request object.
     *
     * @return Response The response rendering the room list page.
     */
    #[Route('/rooms', name: 'app_rooms')]
    public function index(RoomRepository $roomRepository, Request $request): Response
    {
        $stateParam = $request->query->get('state');
        $stateEnum = $stateParam ? RoomStateEnum::tryFrom($stateParam) : null;

        $isManager = $this->isGranted('ROLE_MANAGER');

        $filterForm = $this->createForm(FilterRoomType::class, null, [
            'state' => $stateEnum,
        ]);
        $filterForm->handleRequest($request);

        $criteria = [];

        // Filtrage pour les utilisateurs non connectés
        if (!$this->getUser()) {
            $criteria['sensorStatus'] = ['linked'];
        }

        // Applique le filtre initial basé sur l'URL
        if ($stateParam) {
            $criteria['state'] = $stateParam;
        }

        if ($filterForm->get('reset')->isClicked()) {
            // Redirige vers la même page sans les filtres
            return $this->redirectToRoute('app_rooms');
        }

        // Applique les critères du formulaire s'il est soumis
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
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

        $optionsEnabled = $request->query->get('optionsEnabled', false);

        return $this->render('rooms/index.html.twig', [
            'optionsEnabled' => $optionsEnabled, // Passez la variable ici
            'rooms' => $rooms,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    /**
     * Adds a new room to the database.
     *
     * This method allows users to add a new room. It includes form validation
     * and persists the new room entity if the form is successfully submitted.
     *
     * @param Request $request The HTTP request object.
     * @param EntityManagerInterface $entityManager The entity manager to persist room data.
     *
     * @return Response The response rendering the add room form or redirecting to the room list page.
     */
    #[Route('/rooms/add', name: 'app_rooms_add')]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {

        $this->denyAccessUnlessGranted('ROLE_MANAGER');

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
     * @param RoomRepository $roomRepository The repository to fetch room data.
     * @param ThresholdRepository $thresholdRepository The repository to fetch threshold data.
     * @param string $name The name of the room to display.
     *
     * @return Response The response rendering the room details page.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If the room is not found.
     */
    #[Route('/rooms/{name}', name: 'app_rooms_details')]
    public function details(RoomRepository $roomRepository, ThresholdRepository $thresholdRepository, string $name): Response
    {
        $room = $roomRepository->findOneBy(['name' => $name]);

        if (!$room) {
            throw $this->createNotFoundException('The room does not exist');
        }

        if (!$this->getUser() && $room->getSensorState() === SensorStateEnum::NOT_LINKED) {
            throw new AccessDeniedHttpException('This room is not yet equipped.');
        }

        $roomRepository->updateJsonFromApi($room);

        $roomRepository->updateAcquisitionSystemFromJson($room);
        $roomRepository->updateRoomState($room);

        try {
            // Appeler le service pour obtenir les prévisions météo
            $this->weatherApiService->fetchWeatherData('46.16', '-1.15', 'Xu9ot3p6Bx4iIcfE');
            $forecast = $this->weatherApiService->getForecast();
            $todayForecast = $forecast[0] ?? null;
        } catch (\RuntimeException $e) {
            // Gérer les erreurs de l'API météo en affichant un message d'avertissement
            $todayForecast = $forecast[0] ?? null;
            $this->addFlash('warning', 'Unable to retrieve weather forecasts.');
        }

        return $this->render('rooms/detail.html.twig', [
            'room' => $room,
            'todayForecast' => $todayForecast,
            'thresholds' => $thresholdRepository->getDefaultThresholds(),
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
            $acquisitionSystem->setState(SensorStateEnum::NOT_LINKED);
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
     * @param string $name The name of the room to assign an acquisition system.
     * @param RoomRepository $roomRepository The repository used to fetch room data.
     * @param EntityManagerInterface $entityManager The entity manager used to persist data.
     *
     * @return Response A response that redirects to the room list page.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If the room is not found.
     */
    #[Route('/rooms/{name}/request-assignment', name: 'app_rooms_request_assignment', methods: ['POST'])]
    public function requesAssignment(
        string $name,
        RoomRepository $roomRepository,
        AcquisitionSystemRepository $acquisitionSystemRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $room = $roomRepository->findOneBy(['name' => $name]);

        if (!$room) {
            throw $this->createNotFoundException('Room not found');
        }

        // Vérifier les systèmes d'acquisition disponibles
        $availableSystems = $acquisitionSystemRepository->findSystemsNotLinked();

        if (empty($availableSystems)) {
            $this->addFlash('warning', 'Assignment may take some time, as there are no more acquisition systems available.');
        }

        // Créer une nouvelle action pour l'assignation
        $action = new Action();
        $action->setInfo(ActionInfoEnum::ASSIGNMENT); // Type d'action : ASSIGNMENT
        $action->setState(ActionStateEnum::TO_DO);    // Etat : À FAIRE
        $action->setCreatedAt(new \DateTime());       // Date de création
        $action->setRoom($room);                      // Associer la salle à la tâche

        $room->setState(RoomStateEnum::WAITING);
        $room->setSensorState(SensorStateEnum::ASSIGNMENT);

        // Persister la tâche dans la base de données
        $entityManager->persist($action);

        // Enregistrer les modifications dans la base de données
        $entityManager->flush();

        // Ajouter un message flash pour indiquer le succès
        $this->addFlash('success', 'A new assignment task has been created.');

        return $this->redirectToRoute('app_rooms');
    }


    /**
     * Initiates the request to unassign an acquisition system from a room.
     *
     * This method sets the state of the specified room to "PENDING_UNASSIGNMENT",
     * and saves the current state as "previousState" to allow restoration if needed.
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

        // Créer une nouvelle tâche pour assigner un système à cette salle
        $action = new Action();
        $action->setInfo(ActionInfoEnum::UNASSIGNMENT); // Type d'action : ASSIGNMENT
        $action->setState(ActionStateEnum::TO_DO);    // Etat : À FAIRE
        $action->setCreatedAt(new \DateTime());       // Date de création
        $action->setRoom($room);                      // Associer la salle à la tâche


        $room->setPreviousState($room->getState());
        $room->setPreviousSensorState($room->getSensorState());
        $room->setState(RoomStateEnum::WAITING);
        $room->setSensorState(SensorStateEnum::UNASSIGNMENT);
        $entityManager->persist($action);
        $entityManager->flush();

        $this->addFlash('success', 'A new unassignment task has been created.');

        return $this->redirectToRoute('app_rooms');
    }

    /**
     * Cancels the assignment or unassignment of an acquisition system to/from a room.
     *
     * This method restores the room state to the previous state if it was in "PENDING_UNASSIGNMENT".
     * If the room was in "PENDING_ASSIGNMENT", it changes the state to "NOT_LINKED".
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
    public function cancelInstallation(
        string $name,
        RoomRepository $roomRepository,
        EntityManagerInterface $entityManager,
        ActionRepository $actionRepository
    ): Response {
        $room = $roomRepository->findOneBy(['name' => $name]);

        if (!$room) {
            throw $this->createNotFoundException('Room not found');
        }

        // Annuler une action d'ASSIGNMENT
        if ($room->getSensorState() == SensorStateEnum::ASSIGNMENT) {
            $room->setState(RoomStateEnum::NONE);
            $room->setSensorState(SensorStateEnum::NOT_LINKED);
        }
        // Annuler une action d'UNASSIGNMENT
        elseif ($room->getSensorState() == SensorStateEnum::UNASSIGNMENT) {
            $room->setState($room->getPreviousState());
            $room->setSensorState($room->getPreviousSensorState());

            // Restaurer les actions précédentes
            foreach ($room->getPreviousActions() as $previousAction) {
                $entityManager->persist($previousAction);
            }

            // Vider les previousActions après restauration
            $room->getPreviousActions()->clear();
        }

        // Supprimer les tâches associées à cette salle
        $tasks = $actionRepository->findTasksForRoomToDelete($room->getId());

        // Message flash basé sur les tâches supprimées
        if (!empty($tasks)) {
            $this->addFlash('success', 'The installation task(s) have been successfully canceled.');
        } else {
            $this->addFlash('info', 'No pending or ongoing installation task was found for this room.');
        }

        foreach ($tasks as $task) {
            $entityManager->remove($task);
        }

        $entityManager->flush();

        // Redirection
        return $this->redirectToRoute('app_rooms');
    }
}
