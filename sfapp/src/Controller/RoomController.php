<?php
// RoomController.php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\Action;
use App\Repository\ActionRepository;
use App\Repository\AcquisitionSystemRepository;
use App\Repository\RoomRepository;
use App\Repository\ThresholdRepository;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use App\Utils\ActionInfoEnum;
use App\Utils\ActionStateEnum;
use App\Service\WeatherApiService;
use App\Service\RoomSensorService;
use App\Form\FilterRoomType;
use App\Form\AddRoomType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @brief Manages room-related operations.
 *
 * Handles listing, adding, updating, deleting, and managing room assignments.
 */
class RoomController extends AbstractController
{
    private WeatherApiService $weatherApiService;
    private RoomSensorService $roomSensorService;

    /**
     * @brief Constructs the RoomController with required services.
     *
     * @param WeatherApiService $weatherApiService Service to fetch weather data.
     * @param RoomSensorService $roomSensorService Service to manage room sensor data.
     */
    public function __construct(
        WeatherApiService $weatherApiService,
        RoomSensorService $roomSensorService
    ) {
        $this->weatherApiService = $weatherApiService;
        $this->roomSensorService = $roomSensorService;
    }

    /**
     * @brief Creates a form for deleting a room.
     *
     * @param string $name The name of the room to delete.
     *
     * @return FormInterface The delete form.
     */
    private function createDeleteForm(string $name): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('app_rooms_delete', ['name' => $name]))
            ->setMethod('POST')
            ->add('_token', HiddenType::class, [
                'data' => $this->get('csrf_token_manager')->getToken('delete_room')->getValue(),
            ])
            ->getForm();
    }

    /**
     * @brief Displays the list of rooms with filtering options.
     *
     * @param RoomRepository   $roomRepository   Repository to manage Room entities.
     * @param ActionRepository $actionRepository Repository to manage Action entities.
     * @param Request          $request          The current HTTP request.
     *
     * @return Response The rendered rooms listing page.
     */
    #[Route('/rooms', name: 'app_rooms')]
    public function index(
        RoomRepository   $roomRepository,
        ActionRepository $actionRepository,
        Request          $request
    ): Response {
        date_default_timezone_set('Europe/Paris');

        // Retrieve all rooms that have an AcquisitionSystem
        $rooms = $roomRepository->findRoomsWithAS();

        // --------------------------------------
        // Check freshness of lastUpdatedAt
        // and update if necessary (only if older than 3 minutes)
        // --------------------------------------
        foreach ($rooms as $room) {
            // We only handle rooms that are actually equipped (LINKED)
            if ($room->getSensorState() === SensorStateEnum::LINKED) {
                $lastUpdatedAt = $room->getLastUpdatedAt();
                $now           = new \DateTimeImmutable('now');

                // If lastUpdatedAt is null or older than 3 minutes
                if (
                    null === $lastUpdatedAt
                    || ($now->getTimestamp() - $lastUpdatedAt->getTimestamp()) >= 180
                ) {
                    // => Update the room (updateRoomState)
                    try {
                        $this->roomSensorService->updateRoomState($room);
                    } catch (\Exception $e) {
                        // Handle or log the error
                        continue;
                    }
                } else {
                    // Otherwise, do nothing to avoid too many API calls
                }
            }
        }

        $stateParam = $request->query->get('state');
        $stateEnum  = $stateParam ? RoomStateEnum::tryFrom($stateParam) : null;

        // Filter form
        $filterForm = $this->createForm(FilterRoomType::class, null, [
            'state' => $stateEnum,
        ]);
        $filterForm->handleRequest($request);

        $criteria = [];

        // Filtering for non-authenticated users
        if (!$this->getUser()) {
            $criteria['sensorStatus'] = 'linked';
            $criteria['state_not']    = 'no data'; // Exclude "no data" state
        }

        // Initial filter (via URL)
        if ($stateParam) {
            $criteria['state'] = $stateParam;
        }

        // Reset form
        if ($filterForm->get('reset')->isClicked()) {
            return $this->redirectToRoute('app_rooms');
        }

        // Apply criteria from form
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

        // Get rooms using criteria
        $rooms = $roomRepository->findByCriteria($criteria);

        // Fetch ongoing tasks for each room
        $ongoingTasksByRoomId = [];
        foreach ($rooms as $room) {
            $ongoingTask = $actionRepository->findOngoingTaskForRoom($room->getId());
            $ongoingTasksByRoomId[$room->getId()] = $ongoingTask;
        }

        return $this->render('rooms/index.html.twig', [
            'rooms'        => $rooms,
            'filterForm'   => $filterForm->createView(),
            'ongoingTasks' => $ongoingTasksByRoomId,
        ]);
    }

    /**
     * @brief Adds a new room.
     *
     * @param Request                 $request         The current HTTP request.
     * @param EntityManagerInterface  $entityManager   The entity manager for database operations.
     *
     * @return Response The rendered form page or a redirect to the rooms listing.
     */
    #[Route('/rooms/add', name: 'app_rooms_add')]
    public function add(
        Request                 $request,
        EntityManagerInterface  $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        $room = new Room();
        $room->setSensorState(SensorStateEnum::NOT_LINKED);
        $room->setState(RoomStateEnum::NO_DATA);

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
     * @brief Displays the details of a specific room.
     *
     * Shows room information, weather forecast, and thresholds.
     *
     * @param RoomRepository      $roomRepository      Repository to manage Room entities.
     * @param ThresholdRepository $thresholdRepository Repository to manage Threshold entities.
     * @param string             $name                The name of the room.
     *
     * @return Response The rendered room details page.
     *
     * @throws NotFoundException If the room is not found.
     * @throws AccessDeniedHttpException If access is denied based on sensor state.
     */
    #[Route('/rooms/{name}', name: 'app_rooms_details')]
    public function details(
        RoomRepository      $roomRepository,
        ThresholdRepository $thresholdRepository,
        string             $name
    ): Response {
        $room = $roomRepository->findByName($name);
        if (!$room) {
            throw $this->createNotFoundException(sprintf('Room "%s" not found', $name));
        }

        if (!$this->getUser() && $room->getSensorState() === SensorStateEnum::NOT_LINKED) {
            throw new AccessDeniedHttpException('This room is not yet equipped.');
        }

        // Update the room's state directly from API/sensor data
        $this->roomSensorService->updateRoomState($room);

        try {
            // Get weather forecast
            $this->weatherApiService->fetchWeatherData('46.16', '-1.15', 'Xu9ot3p6Bx4iIcfE');
            $forecast = $this->weatherApiService->getForecast();
            $todayForecast = $forecast[0] ?? null;
        } catch (\RuntimeException $e) {
            // Handle API errors by displaying a warning message
            $todayForecast = $forecast[0] ?? null;
            $this->addFlash('warning', 'Unable to retrieve weather forecasts.');
        }

        return $this->render('rooms/detail.html.twig', [
            'room'       => $room,
            'todayForecast' => $todayForecast,
            'thresholds' => $thresholdRepository->getDefaultThresholds(),
        ]);
    }

    /**
     * @brief Updates an existing room.
     *
     * @param string                  $name             The name of the room to update.
     * @param RoomRepository          $roomRepository   Repository to manage Room entities.
     * @param ActionRepository        $actionRepository Repository to manage Action entities.
     * @param Request                 $request          The current HTTP request.
     * @param EntityManagerInterface  $entityManager    The entity manager for database operations.
     *
     * @return Response The rendered update form or a redirect to the rooms listing.
     *
     * @throws NotFoundException If the room is not found.
     */
    #[Route('/rooms/{name}/update', name: 'app_rooms_update')]
    public function update(
        string                  $name,
        RoomRepository          $roomRepository,
        ActionRepository        $actionRepository,
        Request                 $request,
        EntityManagerInterface  $entityManager
    ): Response {
        $room = $roomRepository->findByName($name);
        if (!$room) {
            throw $this->createNotFoundException(sprintf('Room "%s" not found', $name));
        }

        // Retrieve ongoing tasks for the room
        $ongoingTask = $actionRepository->findOngoingTaskForRoom($room->getId());

        $form = $this->createForm(AddRoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Room updated successfully.');
            return $this->redirectToRoute('app_rooms');
        }

        return $this->render('rooms/update.html.twig', [
            'room'        => $room,
            'form'        => $form->createView(),
            'ongoingTask' => $ongoingTask,
        ]);
    }

    /**
     * @brief Deletes a specific room.
     *
     * @param string                  $name             The name of the room to delete.
     * @param RoomRepository          $roomRepository   Repository to manage Room entities.
     * @param EntityManagerInterface  $entityManager    The entity manager for database operations.
     * @param Request                 $request          The current HTTP request.
     *
     * @return Response A redirect response to the rooms listing.
     *
     * @throws AccessDeniedHttpException If the CSRF token is invalid.
     * @throws NotFoundException If the room is not found.
     */
    #[Route('/rooms/{name}/delete', name: 'app_rooms_delete', methods: ['POST'])]
    public function delete(
        string                  $name,
        RoomRepository          $roomRepository,
        EntityManagerInterface  $entityManager,
        Request                 $request
    ): Response {
        $submittedToken = $request->request->get('_token');

        // Verify CSRF token
        if (!$this->isCsrfTokenValid('delete_room', $submittedToken)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $room = $roomRepository->findByName($name);
        if (!$room) {
            throw $this->createNotFoundException(sprintf('Room "%s" not found', $name));
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
     * @brief Requests the assignment of an acquisition system to a room.
     *
     * Creates a new assignment action for the specified room.
     *
     * @param string                      $name                      The name of the room.
     * @param RoomRepository              $roomRepository            Repository to manage Room entities.
     * @param AcquisitionSystemRepository $acquisitionSystemRepository Repository to manage AcquisitionSystem entities.
     * @param ActionRepository            $actionRepository          Repository to manage Action entities.
     * @param EntityManagerInterface      $entityManager             The entity manager for database operations.
     *
     * @return Response A redirect response to the rooms listing.
     *
     * @throws NotFoundException If the room is not found.
     */
    #[Route('/rooms/{name}/request-assignment', name: 'app_rooms_request_assignment', methods: ['POST'])]
    public function requestAssignment(
        string                      $name,
        RoomRepository              $roomRepository,
        AcquisitionSystemRepository $acquisitionSystemRepository,
        ActionRepository            $actionRepository,
        EntityManagerInterface      $entityManager
    ): Response {
        $room = $roomRepository->findByName($name);
        if (!$room) {
            throw $this->createNotFoundException(sprintf('Room "%s" not found', $name));
        }

        // Check if an assignment/unassignment task is already in progress
        $ongoingTask = $actionRepository->findOngoingTaskForRoom($room->getId());
        if ($ongoingTask) {
            $this->addFlash('warning', 'An (un)installation task is already in progress for this room.');
            return $this->redirectToRoute('app_rooms');
        }

        // No ongoing task => create the action
        $action = new Action();
        $action->setInfo(ActionInfoEnum::ASSIGNMENT);
        $action->setState(ActionStateEnum::TO_DO);
        $action->setCreatedAt(new \DateTime());
        $action->setRoom($room);

        $entityManager->persist($action);
        $entityManager->flush();

        $this->addFlash('success', 'Installation task created.');

        return $this->redirectToRoute('app_rooms');
    }

    /**
     * @brief Requests the unassignment of an acquisition system from a room.
     *
     * Creates a new unassignment action for the specified room.
     *
     * @param string                  $name           The name of the room.
     * @param RoomRepository          $roomRepository Repository to manage Room entities.
     * @param ActionRepository        $actionRepository Repository to manage Action entities.
     * @param EntityManagerInterface  $entityManager  The entity manager for database operations.
     *
     * @return Response A redirect response to the rooms listing.
     *
     * @throws NotFoundException If the room is not found.
     */
    #[Route('/rooms/{name}/request-unassignment', name: 'app_rooms_request_unassignment', methods: ['POST'])]
    public function requestUnassignment(
        string                  $name,
        RoomRepository          $roomRepository,
        ActionRepository        $actionRepository,
        EntityManagerInterface  $entityManager
    ): Response {
        $room = $roomRepository->findByName($name);
        if (!$room) {
            throw $this->createNotFoundException(sprintf('Room "%s" not found', $name));
        }

        // Check if an assignment/unassignment task is already in progress
        $ongoingTask = $actionRepository->findOngoingTaskForRoom($room->getId());
        if ($ongoingTask) {
            $this->addFlash('warning', 'An (un)installation task is already in progress for this room.');
            return $this->redirectToRoute('app_rooms');
        }

        // Create the action
        $action = new Action();
        $action->setInfo(ActionInfoEnum::UNASSIGNMENT);
        $action->setState(ActionStateEnum::TO_DO);
        $action->setCreatedAt(new \DateTime());
        $action->setRoom($room);

        $entityManager->persist($action);
        $entityManager->flush();

        $this->addFlash('success', 'Uninstallation task created.');

        return $this->redirectToRoute('app_rooms');
    }

    /**
     * @brief Cancels an ongoing installation/uninstallation action.
     *
     * Removes all associated tasks for the specified room.
     *
     * @param string                  $name             The name of the room.
     * @param RoomRepository          $roomRepository   Repository to manage Room entities.
     * @param ActionRepository        $actionRepository Repository to manage Action entities.
     * @param EntityManagerInterface  $entityManager    The entity manager for database operations.
     *
     * @return Response A redirect response to the rooms listing.
     *
     * @throws NotFoundException If the room is not found.
     */
    #[Route('/rooms/{name}/cancel-installation', name: 'app_rooms_cancel_installation', methods: ['POST'])]
    public function cancelInstallation(
        string                  $name,
        RoomRepository          $roomRepository,
        ActionRepository        $actionRepository,
        EntityManagerInterface  $entityManager
    ): Response {
        $room = $roomRepository->findByName($name);
        if (!$room) {
            throw $this->createNotFoundException(sprintf('Room "%s" not found', $name));
        }

        $ongoingTasks = $actionRepository->findOngoingTaskForRoom($room->getId());

        if (empty($ongoingTasks)) {
            $this->addFlash('info', 'No ongoing installation/uninstallation task for this room.');
            return $this->redirectToRoute('app_rooms');
        }

        foreach ($ongoingTasks as $task) {
            $entityManager->remove($task);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Installation/uninstallation task canceled successfully.');
        return $this->redirectToRoute('app_rooms');
    }

    /**
     * @brief Displays analytics for a specific room.
     *
     * @param string          $name          The name of the room
     * @param string          $dbname        The database name associated with the room's acquisition system
     * @param RoomRepository  $roomRepository The repository to fetch room data
     * @param Request         $request       The HTTP request
     *
     * @return Response The response rendering the analytics page
     */
    #[Route('/rooms/{name}/analytics/{dbname}', name: 'app_rooms_analytics')]
    public function analytics(
        string         $name,
        string         $dbname,
        RoomRepository $roomRepository,
        Request        $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        $room = $roomRepository->findByName($name);
        if (!$room) {
            throw $this->createNotFoundException(sprintf('Room "%s" not found', $name));
        }

        $acquisitionSystem = $room->getAcquisitionSystem();
        if (!$acquisitionSystem) {
            throw $this->createNotFoundException('This room has no acquisition system.');
        }

        if ($acquisitionSystem->getDbName() !== $dbname) {
            throw $this->createNotFoundException('Invalid dbname for this room.');
        }

        $range = $request->query->get('range', 'month');

        // Fetch historical data from the external API
        try {
            $historicalData = $this->roomSensorService->fetchHistoricalDataFromApi($acquisitionSystem, $range);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to fetch data: ' . $e->getMessage());
            $historicalData = [];
        }

        return $this->render('rooms/analytics.html.twig', [
            'room'           => $room,
            'historicalData' => $historicalData,
            'range'          => $range,
        ]);
    }
}
