<?php
//HomeController.php
namespace App\Controller;

use App\Entity\Action;
use App\Entity\AcquisitionSystem;
use App\Entity\Room;
use App\Form\AddASType;
use App\Form\FilterASType;
use App\Repository\ActionRepository;
use App\Repository\RoomRepository;
use App\Repository\AcquisitionSystemRepository;
use App\Service\WeatherApiService;
use App\Utils\ActionStateEnum;
use App\Utils\ActionInfoEnum;
use App\Utils\SensorStateEnum;
use App\Form\AssignFormType;
use App\Form\UnassignFormType;
use App\Form\ChangementFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Contracts\HttpClient\HttpClientInterface;


/**
 * Class HomeController
 *
 * Controller responsible for handling home, as list and to-do list related actions.
 *
 * @package App\Controller
 */
class HomeController extends AbstractController
{
    /**
     * Displays the home dashboard with statistical data and weather forecasts.
     *
     * @Route("/home", name="app_home")
     *
     * This method retrieves and prepares the data required for the home dashboard, including:
     * - Counts of rooms, acquisition systems, critical and at-risk rooms.
     * - Weather forecasts for the next 4 days fetched from a weather API via the WeatherApiService.
     * - Pending actions retrieved from the database.
     *
     * @param RoomRepository             $roomRepository             Repository for managing room entities.
     * @param AcquisitionSystemRepository $acquisitionSystemRepository Repository for managing acquisition system entities.
     * @param ActionRepository           $actionRepository           Repository for managing action entities.
     * @param WeatherApiService          $weatherApiService          Service for fetching and processing weather data.
     *
     * @return Response Rendered home page with all the necessary data.
     */
    #[Route('/home', name: 'app_home')]
    public function home(
        RoomRepository $roomRepository,
        AcquisitionSystemRepository $acquisitionSystemRepository,
        ActionRepository $actionRepository,
        WeatherApiService $weatherApiService
    ): Response {
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
        ]);
    }

    /**
     * Displays the to-do list.
     *
     * @Route("/todolist", name="app_todolist")
     *
     * @param ActionRepository $actionRepository The repository for actions.
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

        return $this->render('home/todolist.html.twig', [
            'actions' => $actions,
            'awaitingTasksCount' => $awaitingTasksCount,
        ]);
    }

    /**
     * Edits an existing action.
     *
     * @Route("/todolist/edit/{id}", name="app_todolist_edit")
     *
     * @param int                        $id                         The ID of the action to edit.
     * @param Request                    $request                    The current HTTP request.
     * @param ActionRepository           $actionRepository          The repository for actions.
     * @param RoomRepository             $roomRepository            The repository for rooms.
     * @param AcquisitionSystemRepository $acquisitionSystemRepository The repository for acquisition systems.
     * @param EntityManagerInterface     $entityManager              The entity manager.
     *
     * @return Response The rendered edit action page or a redirect response.
     *
     * @throws NotFoundException If the action or room is not found.
     */
    #[Route('/todolist/edit/{id}', name: 'app_todolist_edit')]
    public function editAction(int $id, Request $request, ActionRepository $actionRepository, RoomRepository $roomRepository, AcquisitionSystemRepository $acquisitionSystemRepository, EntityManagerInterface $entityManager): Response {
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

            return $this->redirectToRoute('todolist');
        }
        $acquisitionSystems = $acquisitionSystemRepository->findAll();

        return $this->render('home/edit.html.twig', [
            'action' => $action,
            'rooms' => $rooms,
            'acquisitionSystems' => $acquisitionSystems,
        ]);
    }

    /**
     * Deletes an action.
     *
     * @Route("/todolist/delete/{id}", name="app_todolist_delete", methods={"POST"})
     *
     * @param Action                $action        The action to delete.
     * @param Request               $request       The current HTTP request.
     * @param EntityManagerInterface $entityManager The entity manager.
     *
     * @return Response A redirect response to the to-do list.
     */
    #[Route('/todolist/delete/{id}', name: 'app_todolist_delete', methods: ['POST'])]
    public function delete(Action $action, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_action_' . $action->getId(), $request->request->get('_token'))) {
            $entityManager->remove($action);
            $entityManager->flush();

            $this->addFlash('success', 'Action cancelled successfully.');
        }

        return $this->redirectToRoute('app_todolist');
    }

    /**
     * Begins an action by changing its state to DOING.
     *
     * @Route("/todolist/{id}/begin", name="app_begin_action", methods={"POST"})
     *
     * @param int                        $id                The ID of the action to begin.
     * @param ActionRepository           $actionRepository  The repository for actions.
     * @param EntityManagerInterface     $entityManager     The entity manager.
     *
     * @return Response A redirect response to the to-do list.
     *
     * @throws NotFoundException If the action is not found.
     */
    #[Route('/todolist/{id}/begin', name:'app_begin_action', methods:['POST'])]
    public function beginAction(int $id, ActionRepository $actionRepository, EntityManagerInterface $entityManager): Response
    {
        $action = $actionRepository->find($id);

        if (!$action) {
            throw $this->createNotFoundException('Action not found.');
        }

        if ($action->getState()->value !== 'to do') {
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
     * @Route("/todolist/{id}/validate", name="app_validate_action", methods={"POST"})
     *
     * @param int                        $id                          The ID of the action to validate.
     * @param Request                    $request                     The current HTTP request.
     * @param ActionRepository           $actionRepository           The repository for actions.
     * @param AcquisitionSystemRepository $acquisitionSystemRepository The repository for acquisition systems.
     * @param EntityManagerInterface     $entityManager               The entity manager.
     *
     * @return Response A redirect response to the to-do list.
     *
     * @throws NotFoundException If the action or acquisition system is not found.
     */
    #[Route('/todolist/{id}/validate', name:'app_validate_action', methods:['POST'])]
    public function validateAction(
        int $id,
        Request $request,
        ActionRepository $actionRepository,
        AcquisitionSystemRepository $acquisitionSystemRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $action = $actionRepository->find($id);

        if (!$action) {
            throw $this->createNotFoundException('Action not found.');
        }

        if ($action->getState()->value !== 'doing') {
            $this->addFlash('error', 'This action is not in a state that allows it to be validated.');
            return $this->redirectToRoute('app_todolist');
        }

        if (in_array($action->getInfo()->value, ['assignment', 'replacement', 'switch'])) {
            $acquisitionSystemId = $request->request->get('acquisitionSystem');
            $acquisitionSystem = $acquisitionSystemRepository->find($acquisitionSystemId);

            if (!$acquisitionSystem) {
                $this->addFlash('error', 'Invalid acquisition system.');
                return $this->redirectToRoute('app_todolist');
            }

            $action->setAcquisitionSystem($acquisitionSystem);
        }


        $action->setState(ActionStateEnum::DONE); // Change state to DONE
        $entityManager->flush();

        $this->addFlash('success', 'Action has been validated.');
        return $this->redirectToRoute('app_todolist');
    }

    /**
     * Displays all completed actions.
     *
     * @Route("/todolist/done", name="app_todolist_done")
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
     * @Route("/as", name="app_acquisition_system")
     *
     * @param Request                    $request                    The current HTTP request.
     * @param AcquisitionSystemRepository $acquisitionSystemRepository The repository for acquisition systems.
     *
     * @return Response The rendered acquisition systems list page.
     */
    #[Route('/as', name: 'app_acquisition_system')]
    public function asList(Request $request, AcquisitionSystemRepository $acquisitionSystemRepository): Response
    {
        $filterForm = $this->createForm(FilterASType::class);
        $filterForm->handleRequest($request);

        $criteria = [];
        $formSubmitted = $filterForm->isSubmitted() && $filterForm->isValid();

        if ($filterForm->get('reset')->isClicked()) {
            // Redirige vers la même page sans les filtres
            return $this->redirectToRoute('app_acquisition_system');
        }

        if ($formSubmitted) {
            /** @var Room $data */
            $data = $filterForm->getData();

            if (!empty($data->getName())) {
                $criteria['name'] = $data->getName();
            }


            if ($data->getState()) {
                $criteria['state'] = $data->getState();
            }

        }
        $as = $acquisitionSystemRepository->findByCriteria($criteria);

        $deleteForms = [];

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
     * @Route("/as/add", name="app_add_acquisition_system")
     *
     * @param Request                    $request                    The current HTTP request.
     * @param AcquisitionSystemRepository $acquisitionSystemRepository The repository for acquisition systems.
     * @param EntityManagerInterface     $entityManager              The entity manager.
     *
     * @return Response The rendered add acquisition system page or a redirect response.
     */
    #[Route('/as/add', name: 'app_acquisition_system_add')]
    public function addAS(Request $request, AcquisitionSystemRepository $acquisitionSystemRepository, EntityManagerInterface $entityManager): Response
    {
        $as = new AcquisitionSystem();
        $as->setState(SensorStateEnum::NOT_LINKED);
        $form = $this->createForm(AddASType::class, $as, ['validation_groups' => ['Default', 'add']]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Récupérer la valeur du champ 'number'
            $number = $form->get('number')->getData();

            // Formater le numéro avec des zéros en tête
            $formattedNumber = str_pad($number, 3, '0', STR_PAD_LEFT);

            // Définir le nom complet avec le préfixe
            $as->setName('ESP-' . $formattedNumber);

            // Vérifier l'unicité du nom
            $existingAS = $acquisitionSystemRepository->findOneBy(['name' => $as->getName()]);
            if ($existingAS) {
                $form->get('number')->addError(new FormError('The acquisition system name must be unique. This name is already in use.'));
            }

            // Si le formulaire est valide après les vérifications
            if ($form->isValid()) {
                $entityManager->persist($as);
                $entityManager->flush();

                $this->addFlash('success', 'Acquisition system added successfully.');

                return $this->redirectToRoute('app_acquisition_system');
            }
        }

        return $this->render('home/addAS.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
