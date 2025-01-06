<?php
// RoomController.php
namespace App\Controller;

use App\Entity\Room;
use App\Entity\Action;
use App\Repository\ActionRepository;
use App\Repository\AcquisitionSystemRepository;
use App\Form\FilterRoomType;
use App\Form\AddRoomType;
use App\Repository\RoomRepository;
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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class RoomController extends AbstractController
{
    private WeatherApiService $weatherApiService;

    public function __construct(WeatherApiService $weatherApiService)
    {
        $this->weatherApiService = $weatherApiService;
    }

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

    #[Route('/rooms', name: 'app_rooms')]
    public function index(
        RoomRepository $roomRepository,
        Request $request
    ): Response
    {
        $stateParam = $request->query->get('state');
        $stateEnum = $stateParam ? RoomStateEnum::tryFrom($stateParam) : null;

        // Formulaire de filtre
        $filterForm = $this->createForm(FilterRoomType::class, null, [
            'state' => $stateEnum,
        ]);
        $filterForm->handleRequest($request);

        $criteria = [];

        // Filtrage pour les utilisateurs non connectés
        if (!$this->getUser()) {
            $criteria['sensorStatus'] = ['linked'];
        }

        // Applique le filtre initial basé sur l’URL
        if ($stateParam) {
            $criteria['state'] = $stateParam;
        }

        // Reset du formulaire
        if ($filterForm->get('reset')->isClicked()) {
            return $this->redirectToRoute('app_rooms');
        }

        // Applique les critères du formulaire
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

        // Récupère la liste des salles selon les critères
        $rooms = $roomRepository->findByCriteria($criteria);

        // --------------------------------------
        // Vérification de l'ancienneté du JSON
        // --------------------------------------
        $now = new \DateTime();
        // On retire 15 minutes de l'heure actuelle
        $cutoffDate = (clone $now)->sub(new \DateInterval('PT15M'));

        foreach ($rooms as $room) {
            // On ne traite que les salles LINKED ayant un système d'acquisition
            if ($room->getSensorState() === SensorStateEnum::LINKED && $room->getAcquisitionSystem()) {
                try {
                    $data = $roomRepository->loadSensorData($room->getAcquisitionSystem());
                } catch (\Exception $e) {
                    // En cas d'erreur (HTTP, JSON invalide, etc.), on peut logguer ou ignorer
                    continue;
                }

                if (!empty($data[0]['dateCapture'])) {
                    $dateString = $data[0]['dateCapture'];

                    // On parse la date selon le format "année-jour-mois heure:minute:seconde"
                    $dateCapture = \DateTime::createFromFormat('Y-d-m H:i:s', $dateString);
                    $errors = \DateTime::getLastErrors();

                    if ($dateCapture !== false && empty($errors['warning_count']) && empty($errors['error_count'])) {
                        // Si la date de capture est antérieure à la date courante - 15 min, on met à jour la salle
                        if ($dateCapture < $cutoffDate) {
                            $roomRepository->updateRoomState($room);
                        }
                    }
                }
            }
        }


        return $this->render('rooms/index.html.twig', [
            'rooms' => $rooms,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/rooms/add', name: 'app_rooms_add')]
    public function add(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
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

    #[Route('/rooms/{name}', name: 'app_rooms_details')]
    public function details(
        RoomRepository $roomRepository,
        ThresholdRepository $thresholdRepository,
        string $name
    ): Response
    {
        $room = $roomRepository->findOneBy(['name' => $name]);

        if (!$room) {
            throw $this->createNotFoundException('The room does not exist');
        }

        if (!$this->getUser() && $room->getSensorState() === SensorStateEnum::NOT_LINKED) {
            throw new AccessDeniedHttpException('This room is not yet equipped.');
        }

        // Puis on met à jour le système depuis le JSON et l'état.
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

    #[Route('/rooms/{name}/update', name: 'app_rooms_update')]
    public function update(
        string $name,
        RoomRepository $roomRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
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

    #[Route('/rooms/{name}/delete', name: 'app_rooms_delete', methods: ['POST'])]
    public function delete(
        string $name,
        RoomRepository $roomRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response
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

    #[Route('/rooms/{name}/request-unassignment', name: 'app_rooms_request_unassignment', methods: ['POST'])]
    public function requestUnassignment(
        string $name,
        RoomRepository $roomRepository,
        EntityManagerInterface $entityManager
    ): Response
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
