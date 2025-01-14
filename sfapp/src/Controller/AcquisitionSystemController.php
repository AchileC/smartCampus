<?php
//AcquisitionSystemController.php
namespace App\Controller;

use App\Entity\AcquisitionSystem;
use App\Form\AddASType;
use App\Form\FilterASType;
use App\Repository\AcquisitionSystemRepository;
use App\Utils\SensorStateEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @brief Manages acquisition systems within the application.
 *
 * The AcquisitionSystemController handles listing, adding, and filtering acquisition systems.
 */
class AcquisitionSystemController extends AbstractController
{
    /**
     * @brief Displays the list of acquisition systems with filtering options.
     *
     * Retrieves acquisition systems based on filter criteria.
     *
     * @param Request                     $request                     The current HTTP request.
     * @param AcquisitionSystemRepository $acquisitionSystemRepository Repository to manage AcquisitionSystem entities.
     *
     * @return Response The rendered acquisition systems listing page.
     */
    #[Route('/as', name: 'app_acquisition_system')]
    public function index(
        Request                     $request,
        AcquisitionSystemRepository $acquisitionSystemRepository
    ): Response
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

        return $this->render('acquisition_system/index.html.twig', [
            'as' => $as,
            'filterForm' => $filterForm->createView(),
            'formSubmitted' => $formSubmitted,
            'optionsEnabled' => false,
        ]);
    }

    /**
     * @brief Adds a new acquisition system.
     *
     * Allows users to create a new acquisition system, ensuring unique naming.
     *
     * @param Request                     $request                     The current HTTP request.
     * @param AcquisitionSystemRepository $acquisitionSystemRepository Repository to manage AcquisitionSystem entities.
     * @param EntityManagerInterface      $entityManager               The entity manager for database operations.
     *
     * @return Response The rendered add acquisition system form or a redirect to the appropriate route.
     */
    #[Route('/as/add', name: 'app_acquisition_system_add')]
    public function add(
        Request                     $request,
        AcquisitionSystemRepository $acquisitionSystemRepository,
        EntityManagerInterface      $entityManager
    ): Response
    {
        // Initialize a new acquisition system with a default state
        $as = new AcquisitionSystem();
        $as->setState(SensorStateEnum::NOT_LINKED);
        $as->setDbName("temp_dbName");

        // Create and process the form
        $form = $this->createForm(AddASType::class, $as, ['validation_groups' => ['Default', 'add']]);
        $form->handleRequest($request);

        $fromAction = $request->query->get('from_action', false);

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
                return $this->render('home/add.html.twig', [
                    'form' => $form->createView(),
                    'from_action' => $fromAction,
                ]);
            }

            // Save the new acquisition system to the database
            $entityManager->persist($as);
            $entityManager->flush();

            $this->addFlash('success', 'Acquisition system added successfully.');

            if ($fromAction) {
                return $this->redirectToRoute('app_todolist_edit', ['id' => $fromAction]);
            } else {
                return $this->redirectToRoute('app_acquisition_system');
            }
        }

        return $this->render('acquisition_system/add.html.twig', [
            'form' => $form->createView(),
            'from_action' => $fromAction,
        ]);
    }
}
