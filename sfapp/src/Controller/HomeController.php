<?php
//HomeController.php
namespace App\Controller;

use App\Entity\AcquisitionSystem;
use App\Entity\Notification;
use App\Form\AddASType;
use App\Form\FilterASType;
use App\Repository\ActionRepository;
use App\Repository\RoomRepository;
use App\Repository\AcquisitionSystemRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Service\WeatherApiService;
use App\Utils\ActionStateEnum;
use App\Utils\ActionInfoEnum;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Class HomeController
 *
 * Controller responsible for handling home, as list and to-do list related actions.
 *
 * @package App\Controller
 */
class HomeController extends AbstractController
{

    // Constructor pour injecter les dépendances
    public function __construct(NotificationRepository $notificationRepository, Environment $twig)
    {
        $this->notificationRepository = $notificationRepository;
        $this->twig = $twig;
    }

    /**
     * @return Response
     */
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        // Redirige vers la route 'app_rooms'
        return $this->redirectToRoute('app_rooms');
    }

    /**
     * Displays the home dashboard with statistical data and weather forecasts.
     *
     * This method retrieves and prepares the data required for the home dashboard, including:
     * - Counts of rooms, acquisition systems, critical and at-risk rooms.
     * - Weather forecasts for the next 4 days fetched from a weather API via the WeatherApiService.
     * - Pending actions retrieved from the database.
     *
     * @param RoomRepository $roomRepository Repository for managing room entities.
     * @param AcquisitionSystemRepository $acquisitionSystemRepository Repository for managing acquisition system entities.
     * @param ActionRepository $actionRepository Repository for managing action entities.
     * @param WeatherApiService $weatherApiService Service for fetching and processing weather data.
     *
     * @return Response Rendered home page with all the necessary data.
     */
    #[Route('/home', name: 'app_home')]
    public function home(
        RoomRepository              $roomRepository,
        AcquisitionSystemRepository $acquisitionSystemRepository,
        ActionRepository            $actionRepository,
        WeatherApiService           $weatherApiService,
        NotificationRepository     $notificationRepository,
    ): Response
    {
        $user = $this->getUser();
        $notifications = $notificationRepository->findBy([
            'recipient' => $user
        ]);


        // Retrieve the number of rooms, acquisition systems, and critical or at-risk rooms from the repositories
        $roomsCount = $roomRepository->count([]);
        $asCount = $acquisitionSystemRepository->count([]);
        $criticalCount = $roomRepository->countByState('critical');
        $atRiskCount = $roomRepository->countByState('at risk');

        try {
            // Fetch weather data from the WeatherApiService for the specified location
            $weatherApiService->fetchWeatherData('46.16', '-1.15', 'Xu9ot3p6Bx4iIcfE');

            // Get the 4-day weather forecast from the service
            $forecast = $weatherApiService->getForecast();
        } catch (\RuntimeException $e) {
            // Handle exceptions during weather data retrieval by displaying an error message
            $forecast = null;
            $this->addFlash('error', 'Failed to fetch weather data: ' . $e->getMessage());
        }

        // Retrieve all actions that are not marked as 'done' from the repository
        $actions = $actionRepository->findLatestFive();

        // Render the dashboard view with the retrieved data
        return $this->render('home/index.html.twig', [
            'rooms_count' => $roomsCount,        // Total number of rooms
            'as_count' => $asCount,              // Total number of acquisition systems
            'critical_count' => $criticalCount,  // Number of critical rooms
            'at_risk_count' => $atRiskCount,     // Number of at-risk rooms
            'forecast' => $forecast,             // Weather forecast data for 4 days
            'actions' => $actions,               // List of pending actions
            'notifications' => $notifications,
        ]);
    }

    /**
     * Displays the to-do list.
     *
     * @param ActionRepository $actionRepository The repository for actions.
     *
     * @return Response The rendered to-do list page.
     */
    #[Route('/todolist', name: 'app_todolist')]
    public function todolist(ActionRepository $actionRepository): Response
    {
        $actions = $actionRepository->findAllExceptDone();

        $awaitingTasksCount = count(array_filter($actions, function ($action) {
            return $action->getState() === ActionStateEnum::TO_DO || $action->getState() === ActionStateEnum::DOING;
        }));

        return $this->render('home/todolist.html.twig', [
            'actions' => $actions,
            'awaitingTasksCount' => $awaitingTasksCount,
        ]);
    }

    /**
     * Edits an existing action.
     *
     * @param int $id The ID of the action to edit.
     * @param Request $request The current HTTP request.
     * @param ActionRepository $actionRepository The repository for actions.
     * @param RoomRepository $roomRepository The repository for rooms.
     * @param AcquisitionSystemRepository $acquisitionSystemRepository The repository for acquisition systems.
     * @param EntityManagerInterface $entityManager The entity manager.
     *
     * @return Response The rendered edit action page or a redirect response.
     *
     * @throws NotFoundException If the action or room is not found.
     */
    #[Route('/todolist/edit/{id}', name: 'app_todolist_edit')]
    public function editAction(int $id, Request $request, ActionRepository $actionRepository, RoomRepository $roomRepository, AcquisitionSystemRepository $acquisitionSystemRepository, EntityManagerInterface $entityManager): Response
    {
        $action = $actionRepository->find($id);

        if (!$action) {
            throw $this->createNotFoundException('Action not found.');
        }

        $rooms = $roomRepository->findAll();

        if ($request->isMethod('POST')) {
            $roomId = $request->request->get('room');
            $state = $request->request->get('state');
            $room = $roomRepository->find($roomId);

            if (!$room) {
                throw $this->createNotFoundException('Room not found.');
            }

            $action->setRoom($room);
            $action->setState(ActionStateEnum::from($state));
            $entityManager->flush();

            return $this->redirectToRoute('app_todolist');
        }
        $acquisitionSystems = $acquisitionSystemRepository->findSystemsNotLinked();

        return $this->render('home/edit.html.twig', [
            'action' => $action,
            'rooms' => $rooms,
            'acquisitionSystems' => $acquisitionSystems,
        ]);
    }


    /**
     * Begins an action by changing its state to DOING.
     *
     * @param int $id The ID of the action to begin.
     * @param ActionRepository $actionRepository The repository for actions.
     * @param EntityManagerInterface $entityManager The entity manager.
     *
     * @return Response A redirect response to the to-do list.
     *
     * @throws NotFoundException If the action is not found.
     */
    #[Route('/todolist/{id}/begin', name: 'app_begin_action', methods: ['POST'])]
    public function beginAction(int $id, ActionRepository $actionRepository, EntityManagerInterface $entityManager): Response
    {
        $action = $actionRepository->find($id);

        if (!$action) {
            throw $this->createNotFoundException('Action not found.');
        }

        if ($action->getState() !== ActionStateEnum::TO_DO) {
            $this->addFlash('error', 'This action is not in a state that allows it to be started.');
            return $this->redirectToRoute('app_todolist');
        }

        $action->setState(ActionStateEnum::DOING); // Change state to DOING
        $action->setStartedAt(new \DateTime()); // Set start time
        $entityManager->flush();

        $this->addFlash('success', 'Action has been started.');
        return $this->redirectToRoute('app_todolist');
    }

    /**
     * Validates an action by changing its state to DONE.
     *
     * @param int $id The ID of the action to validate.
     * @param Request $request The current HTTP request.
     * @param ActionRepository $actionRepository The repository for actions.
     * @param AcquisitionSystemRepository $acquisitionSystemRepository The repository for acquisition systems.
     * @param EntityManagerInterface $entityManager The entity manager.
     *
     * @return Response A redirect response to the to-do list.
     *
     * @throws NotFoundException If the action or acquisition system is not found.
     */
    #[Route('/todolist/{id}/validate', name: 'app_validate_action', methods: ['POST'])]
    public function validateAction(
        int                         $id,
        Request                     $request,
        ActionRepository            $actionRepository,
        AcquisitionSystemRepository $acquisitionSystemRepository,
        EntityManagerInterface      $entityManager,
        UserRepository             $userRepository
    ): Response
    {
        $action = $actionRepository->find($id);


        if (!$action) {
            throw $this->createNotFoundException('Action not found.');
        }

        if ($action->getState() !== ActionStateEnum::DOING) {
            $this->addFlash('error', 'This action is not in a state that allows it to be validated.');
            return $this->redirectToRoute('app_todolist');
        }

        if ($action->getInfo() == ActionInfoEnum::ASSIGNMENT) {
            $acquisitionSystemId = $request->request->get('acquisitionSystem');
            $acquisitionSystem = $acquisitionSystemRepository->find($acquisitionSystemId);

            if (!$acquisitionSystem) {
                $this->addFlash('error', 'Invalid acquisition system.');
                return $this->redirectToRoute('app_todolist');
            }

            $room = $action->getRoom();

            $room->setSensorState(SensorStateEnum::LINKED);
            $room->setAcquisitionSystem($acquisitionSystem);
            $room->setState(RoomStateEnum::WAITING);
            $acquisitionSystem->setState(SensorStateEnum::LINKED);
            $action->setAcquisitionSystem($acquisitionSystem);
        }

        if ($action->getInfo() == ActionInfoEnum::UNASSIGNMENT) {
            $room = $action->getRoom();

            if ($room) {
                $room->setSensorState(SensorStateEnum::NOT_LINKED);
                $room->setState(RoomStateEnum::NONE);
                $acquisitionSystem = $room->getAcquisitionSystem();
                if ($acquisitionSystem) {
                    $acquisitionSystem->setState(SensorStateEnum::NOT_LINKED);
                    $room->setAcquisitionSystem(null);
                }
            }
        }

        $action->setState(ActionStateEnum::DONE); // Change state to DONE

        $manager = $userRepository->findOneByExactRole('ROLE_MANAGER');

        if ($manager) {
            $notification = new Notification();
            $notification->setRead(false);
            $notification->setMessage(sprintf(
                "Task '%s' completed in '%s'.",
                $action->getInfo()->value,
                $action->getRoom()->getName()
            ));
            $notification->setCreateAt(new \DateTimeImmutable());
            $notification->setRecipient($manager);
            $notification->setRoom($action->getRoom());

            $entityManager->persist($notification);
            $entityManager->flush();
        }

        $this->addFlash('success', 'Action has been validated.');
        return $this->redirectToRoute('app_todolist');
    }

    /**
     * Displays all completed actions.
     *
     * @param ActionRepository $actionRepository The repository for actions.
     *
     * @return Response The rendered done actions page.
     */
    #[Route('/todolist/done', name: 'app_todolist_done')]
    public function showDoneActions(ActionRepository $actionRepository): Response
    {
        $doneActions = $actionRepository->findBy(['state' => ActionStateEnum::DONE]);

        $actionTypes = [
            ActionInfoEnum::ASSIGNMENT->value => 'info',
            ActionInfoEnum::UNASSIGNMENT->value => 'warning',
        ];

        return $this->render('home/done.html.twig', [
            'doneActions' => $doneActions,
            'actionTypes' => $actionTypes,
        ]);
    }

    /**
     * Displays the list of acquisition systems with filtering options.
     *
     * This method handles the filtering and listing of acquisition systems.
     * Users can filter by name and state using the filter form.
     *
     * @param Request $request The current HTTP request.
     * @param AcquisitionSystemRepository $acquisitionSystemRepository The repository for acquisition systems.
     *
     * @return Response The rendered page displaying acquisition systems with optional filters applied.
     */
    #[Route('/as', name: 'app_acquisition_system')]
    public function asList(Request $request, AcquisitionSystemRepository $acquisitionSystemRepository): Response
    {
        // Create and process the filter form
        $filterForm = $this->createForm(FilterASType::class);
        $filterForm->handleRequest($request);

        $criteria = [];
        $formSubmitted = $filterForm->isSubmitted() && $filterForm->isValid();

        if ($filterForm->get('reset')->isClicked()) {
            // Redirect to the same page without filters
            return $this->redirectToRoute('app_acquisition_system');
        }

        if ($formSubmitted) {
            /** @var AcquisitionSystem $data */
            $data = $filterForm->getData();

            // Filter by name if provided
            if (!empty($data->getName())) {
                $criteria['name'] = $data->getName();
            }

            // Filter by state if provided
            if ($data->getState()) {
                $criteria['state'] = $data->getState();
            }
        }

        // Fetch acquisition systems based on criteria
        $as = $acquisitionSystemRepository->findByCriteria($criteria);

        return $this->render('home/aslist.html.twig', [
            'as' => $as,
            'filterForm' => $filterForm->createView(),
            'formSubmitted' => $formSubmitted,
            'optionsEnabled' => false,
        ]);
    }

    /**
     * Adds a new acquisition system.
     *
     * This method provides a form to add a new acquisition system. It validates
     * the input, ensures uniqueness of the system name, and saves the system to the database.
     *
     * @param Request $request The current HTTP request.
     * @param AcquisitionSystemRepository $acquisitionSystemRepository The repository for acquisition systems.
     * @param EntityManagerInterface $entityManager The entity manager to persist the data.
     *
     * @return Response The rendered page for adding an acquisition system or a redirect to the list page.
     */
    #[Route('/as/add', name: 'app_acquisition_system_add')]
    public function addAS(Request $request, AcquisitionSystemRepository $acquisitionSystemRepository, EntityManagerInterface $entityManager): Response
    {
        // Initialize a new acquisition system with a default state
        $as = new AcquisitionSystem();
        $as->setState(SensorStateEnum::NOT_LINKED);

        // Create and process the form
        $form = $this->createForm(AddASType::class, $as, ['validation_groups' => ['Default', 'add']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Retrieve and format the number field
            $number = $form->get('number')->getData();
            $formattedNumber = str_pad($number, 3, '0', STR_PAD_LEFT);

            // Set the complete system name
            $as->setName('ESP-' . $formattedNumber);

            // Ensure the name is unique
            $existingAS = $acquisitionSystemRepository->findOneBy(['name' => $as->getName()]);
            if ($existingAS) {
                $form->get('number')->addError(new FormError('The acquisition system name must be unique. This name is already in use.'));
                return $this->render('home/addAS.html.twig', ['form' => $form->createView()]);
            }

            // Save the new acquisition system to the database
            $entityManager->persist($as);
            $entityManager->flush();

            $this->addFlash('success', 'Acquisition system added successfully.');

            // Redirect to the acquisition system list page
            return $this->redirectToRoute('app_acquisition_system');
        }

        return $this->render('home/addAS.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/notifications/mark-as-read', name: 'mark_notifications_as_read', methods: ['POST'])]
    public function markAllAsRead(
        NotificationRepository $notificationRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): JsonResponse {
        // Vérification du token CSRF pour la sécurité
        if (!$this->isCsrfTokenValid('mark_notifications', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 400);
        }

        // Vérification de l'authentification de l'utilisateur
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        // Récupération des notifications non lues de l'utilisateur
        $notifications = $notificationRepository->findBy([
            'recipient' => $user
        ]);

        // Met à jour chaque notification
        foreach ($notifications as $notification) {
            $notification->setRead(true);
        }

        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'All notifications marked as read.']);
    }
}