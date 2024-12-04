<?php

namespace App\Controller;

use App\Entity\Room;
use App\Form\FilterASType;
use App\Form\FilterRoomType;
use App\Repository\AcquisitionSystemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AcquisitionSystemController extends AbstractController
{
    #[Route('/as', name: 'app_acquisition_system')]
    public function index(Request $request, AcquisitionSystemRepository $acquisitionSystemRepository): Response
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

            if (!empty($data->getName()))  {
                $criteria['name'] = $data->getName();
            }


            if ($data->getState()) {
                $criteria['state'] = $data->getState();
            }

        }

        $as = $acquisitionSystemRepository->findByCriteria($criteria);

        $deleteForms = [];


        return $this->render('acquisition_system/index.html.twig', [
            'as' => $as,
            'filterForm' => $filterForm->createView(),
            'formSubmitted' => $formSubmitted,
            'optionsEnabled' => false,
        ]);
    }
}
