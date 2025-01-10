<?php
//ActionController.php
namespace App\Controller;

use App\Entity\Notification;
use App\Repository\AcquisitionSystemRepository;
use App\Repository\ActionRepository;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Utils\ActionInfoEnum;
use App\Utils\ActionStateEnum;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @brief Manages actions related to tasks within the application.
 *
 * The ActionController handles the creation, editing, initiation, validation, and history of actions.
 */
class ActionController extends AbstractController
{
    /**
     * @brief Displays the to-do list of actions.
     *
     * Retrieves all actions except those marked as done and calculates the number of awaiting tasks.
     *
     * @param ActionRepository $actionRepository Repository to manage Action entities.
     *
     * @return Response The rendered to-do list page.
     */
    #[Route('/todolist', name: 'app_todolist')]
    public function index(ActionRepository $actionRepository): Response
    {
        $actions = $actionRepository->findAllExceptDone();

        $awaitingTasksCount = count(array_filter($actions, function ($action) {
            return $action->getState() === ActionStateEnum::TO_DO || $action->getState() === ActionStateEnum::DOING;
        }));

        return $this->render('action/index.html.twig', [
            'actions' => $actions,
            'awaitingTasksCount' => $awaitingTasksCount,
        ]);
    }

    /**
     * @brief Edits an existing action.
     *
     * Allows updating the room and state of a specific action.
     *
     * @param int                         $id                        The ID of the action to edit.
     * @param Request                     $request                   The current HTTP request.
     * @param ActionRepository            $actionRepository          Repository to manage Action entities.
     * @param RoomRepository              $roomRepository            Repository to manage Room entities.
     * @param AcquisitionSystemRepository $acquisitionSystemRepository Repository to manage AcquisitionSystem entities.
     * @param EntityManagerInterface      $entityManager             The entity manager for database operations.
     *
     * @return Response The rendered edit page or a redirect to the to-do list.
     *
     * @throws NotFoundException If the action or room is not found.
     */
    #[Route('/todolist/edit/{id}', name: 'app_todolist_edit')]
    public function edit(
        int                         $id,
        Request                     $request,
        ActionRepository            $actionRepository,
        RoomRepository              $roomRepository,
        AcquisitionSystemRepository $acquisitionSystemRepository,
        EntityManagerInterface      $entityManager
    ): Response
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

        // Determine available Acquisition Systems
        $acquisitionSystems = $acquisitionSystemRepository->findSystemsNotLinked();
        $noAsAvailable = count($acquisitionSystems) === 0;

        return $this->render('action/edit.html.twig', [
            'action' => $action,
            'rooms' => $rooms,
            'acquisitionSystems' => $acquisitionSystems,
            'noAsAvailable' => $noAsAvailable,
        ]);
    }

    /**
     * @brief Initiates the beginning of an action.
     *
     * Changes the state of an action from TO_DO to DOING and sets the start time.
     *
     * @param int                         $id                        The ID of the action to begin.
     * @param ActionRepository            $actionRepository          Repository to manage Action entities.
     * @param AcquisitionSystemRepository $acquisitionSystemRepository Repository to manage AcquisitionSystem entities.
     * @param EntityManagerInterface      $entityManager             The entity manager for database operations.
     *
     * @return Response A redirect response to the to-do list or acquisition system addition page.
     *
     * @throws NotFoundException If the action is not found.
     */
    #[Route('/todolist/{id}/begin', name: 'app_begin_action', methods: ['POST'])]
    public function begin(
        int                         $id,
        ActionRepository            $actionRepository,
        AcquisitionSystemRepository $acquisitionSystemRepository,
        EntityManagerInterface      $entityManager
    ): Response {
        $action = $actionRepository->find($id);

        if (!$action) {
            throw $this->createNotFoundException('Action not found.');
        }

        if ($action->getState() !== ActionStateEnum::TO_DO) {
            $this->addFlash('error', 'This action is not in a state that allows it to be started.');
            return $this->redirectToRoute('app_todolist');
        }

        // ⇩⇩⇩ Vérifier la dispo des systèmes UNIQUEMENT si c'est un ASSIGNMENT ⇩⇩⇩
        if ($action->getInfo() === ActionInfoEnum::ASSIGNMENT) {
            $availableSystems = $acquisitionSystemRepository->findSystemsNotLinked();
            if (empty($availableSystems)) {
                // Rediriger vers la création d’un nouveau système d’acquisition
                return $this->redirectToRoute('app_acquisition_system_add', [
                    'from_action' => true,
                ]);
            }
        }

        // Si la tâche est un UNASSIGNMENT (ou maintenance, etc.),
        // on ne redirige PAS vers la création d'AS

        $action->setState(ActionStateEnum::DOING); // passe la tâche en "DOING"
        $action->setStartedAt(new \DateTime());    // date de début de la tâche
        $entityManager->flush();

        $this->addFlash('success', 'Action has been started.');
        return $this->redirectToRoute('app_todolist');
    }


    /**
     * @brief Validates and completes an action.
     *
     * Updates the state of an action to DONE and performs additional operations based on the action type.
     *
     * @param int                         $id                        The ID of the action to validate.
     * @param Request                     $request                   The current HTTP request.
     * @param ActionRepository            $actionRepository          Repository to manage Action entities.
     * @param AcquisitionSystemRepository $acquisitionSystemRepository Repository to manage AcquisitionSystem entities.
     * @param EntityManagerInterface      $entityManager             The entity manager for database operations.
     * @param UserRepository              $userRepository            Repository to manage User entities.
     *
     * @return Response A redirect response to the to-do list.
     *
     * @throws NotFoundException If the action is not found.
     */
    #[Route('/todolist/{id}/validate', name: 'app_validate_action', methods: ['POST'])]
    public function validate(
        int                         $id,
        Request                     $request,
        ActionRepository            $actionRepository,
        AcquisitionSystemRepository $acquisitionSystemRepository,
        EntityManagerInterface      $entityManager,
        UserRepository              $userRepository
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
            $acquisitionSystem->setState(SensorStateEnum::LINKED);
            $action->setAcquisitionSystem($acquisitionSystem);
        }

        if ($action->getInfo() == ActionInfoEnum::MAINTENANCE) {
            $maintenanceStatus = $request->request->get('maintenanceStatus');

            if ($maintenanceStatus === 'yes') {
                // If the system is repaired, update its state
                $acquisitionSystem = $action->getAcquisitionSystem();
                if ($acquisitionSystem) {
                    $acquisitionSystem->setState(SensorStateEnum::LINKED); // State changes to "functional"
                    $entityManager->persist($acquisitionSystem);
                }
                $this->addFlash('success', 'The acquisition system has been marked as repaired.');
            } else {
                $this->addFlash('warning', 'Please select a valid maintenance status.');
                return $this->redirectToRoute('app_todolist_edit', ['id' => $action->getId()]);
            }
        }


        if ($action->getInfo() == ActionInfoEnum::UNASSIGNMENT) {
            $room = $action->getRoom();

            if ($room) {
                $room->setSensorState(SensorStateEnum::NOT_LINKED);
                $room->setState(RoomStateEnum::NO_DATA);
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
     * @brief Displays the history of completed actions.
     *
     * Retrieves all actions marked as DONE and categorizes them based on their type.
     *
     * @param ActionRepository $actionRepository Repository to manage Action entities.
     *
     * @return Response The rendered history page.
     */
    #[Route('/todolist/done', name: 'app_todolist_done')]
    public function history(ActionRepository $actionRepository): Response
    {
        $doneActions = $actionRepository->findBy(['state' => ActionStateEnum::DONE]);

        $actionTypes = [
            ActionInfoEnum::ASSIGNMENT->value => 'info',
            ActionInfoEnum::UNASSIGNMENT->value => 'warning',
        ];

        return $this->render('action/history.html.twig', [
            'doneActions' => $doneActions,
            'actionTypes' => $actionTypes,
        ]);
    }
}
