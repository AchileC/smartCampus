<?php
//HomeController.php
namespace App\Controller;

use App\Entity\Action;
use App\Entity\AcquisitionSystem;
use App\Entity\Room;
use App\Form\FilterASType;
use App\Repository\ActionRepository;
use App\Repository\RoomRepository;
use App\Repository\AcquisitionSystemRepository;
use App\Utils\ActionStateEnum;
use App\Utils\ActionInfoEnum;
use App\Utils\SensorStateEnum;
use App\Form\AssignFormType;
use App\Form\UnassignFormType;
use App\Form\ChangementFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

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

    #[Route('/todolist/delete/{id}', name: 'app_todolist_delete', methods: ['POST'])]
    public function delete(Action $action, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_action_' . $action->getId(), $request->request->get('_token'))) {
            $entityManager->remove($action);
            $entityManager->flush();

            $this->addFlash('success', 'Action deleted successfully.');
        }

        return $this->redirectToRoute('app_todolist');
    }

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

    #[Route('/todolist/done', name: 'app_todolist_done')]
    public function showDoneActions(ActionRepository $actionRepository): Response
    {
        $doneActions = $actionRepository->findBy(['state' => ActionStateEnum::DONE]);

        $actionTypes = [
            ActionInfoEnum::ASSIGNMENT->value => 'info',
            ActionInfoEnum::UNASSIGNMENT->value => 'danger',
            ActionInfoEnum::SWITCH->value => 'warning',
            ActionInfoEnum::REPLACEMENT->value => 'primary',
        ];

        return $this->render('home/done.html.twig', [
            'doneActions' => $doneActions,
            'actionTypes' => $actionTypes,
        ]);
    }

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

}
