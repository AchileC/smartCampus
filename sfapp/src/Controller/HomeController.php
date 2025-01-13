<?php
// HomeController.php
namespace App\Controller;

use App\Entity\AcquisitionSystem;
use App\Entity\Notification;
use App\Entity\Room;
use App\Entity\Threshold;
use App\Form\AddASType;
use App\Form\FilterASType;
use App\Form\ThresholdType;
use App\Repository\ActionRepository;
use App\Repository\RoomRepository;
use App\Repository\AcquisitionSystemRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Repository\ThresholdRepository;
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

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @brief Serves as the main controller for the application's home functionalities.
 *
 * Manages the dashboard, threshold settings, and notification handling.
 */
class HomeController extends AbstractController
{
    /**
     * @brief Repository for managing notifications.
     *
     * @var NotificationRepository
     */
    private NotificationRepository $notificationRepository;

    /**
     * @brief Twig environment for rendering templates.
     *
     * @var Environment
     */
    private Environment $twig;

    /**
     * @brief Constructs the HomeController with required services.
     *
     * @param NotificationRepository $notificationRepository Repository to manage Notification entities.
     * @param Environment            $twig                  Twig environment for rendering templates.
     */
    public function __construct(
        NotificationRepository  $notificationRepository,
        Environment             $twig
    )
    {
        $this->notificationRepository = $notificationRepository;
        $this->twig = $twig;
    }

     /**
     * @brief Displays the home dashboard.
     *
     * @param RoomRepository $roomRepository Repository to manage Room entities.
     * @param AcquisitionSystemRepository $acquisitionSystemRepository Repository to manage AcquisitionSystem entities.
     * @param ActionRepository $actionRepository Repository to manage Action entities.
     * @param WeatherApiService $weatherApiService Service to fetch weather data.
     * @param NotificationRepository $notificationRepository Repository to manage Notification entities.
     *
     * @return Response The rendered home dashboard page.
     */
    #[Route('/home', name: 'app_home')]
    public function index(
        RoomRepository              $roomRepository,
        AcquisitionSystemRepository $acquisitionSystemRepository,
        ActionRepository            $actionRepository,
        WeatherApiService           $weatherApiService,
        NotificationRepository      $notificationRepository,
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
     * @brief Manages threshold settings.
     *
     * Allows users to view, update, and reset threshold values.
     *
     * @param Request                 $request            The current HTTP request.
     * @param ThresholdRepository     $thresholdRepository Repository to manage Threshold entities.
     * @param EntityManagerInterface  $entityManager       The entity manager for database operations.
     *
     * @return Response The rendered threshold settings page or a redirect to the home dashboard.
     */
    #[Route('/home/threshold', name: 'app_home_threshold')]
    public function thresholds(
        Request                 $request,
        ThresholdRepository     $thresholdRepository,
        EntityManagerInterface  $entityManager
    ): Response
    {
        $threshold = $thresholdRepository->getDefaultThresholds();
        $form = $this->createForm(ThresholdType::class, $threshold);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Thresholds updated successfully.');
                return $this->redirectToRoute('app_home');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while saving the thresholds.');
            }
        }

        return $this->render('home/threshold.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @brief Resets threshold settings to default values.
     *
     * Restores all threshold values to their predefined defaults.
     *
     * @param ThresholdRepository $thresholdRepository Repository to manage Threshold entities.
     *
     * @return Response A redirect response to the threshold settings page.
     */
    #[Route('/home/threshold/reset', name: 'app_home_threshold_reset')]
    public function resetThresholds(ThresholdRepository $thresholdRepository): Response
    {
        try {
            $thresholdRepository->resetToDefault();
            $this->addFlash('success', 'Thresholds have been reset to default values.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while resetting the thresholds.');
        }

        return $this->redirectToRoute('app_home_threshold');
    }

    /**
     * @brief Marks all notifications as read.
     *
     * Updates the read status of all notifications for the authenticated user.
     *
     * @param NotificationRepository  $notificationRepository Repository to manage Notification entities.
     * @param EntityManagerInterface  $entityManager          The entity manager for database operations.
     * @param Request                 $request                The current HTTP request.
     *
     * @return JsonResponse A JSON response indicating success or error.
     */
    #[Route('/notifications/mark-as-read', name: 'mark_notifications_as_read', methods: ['POST'])]
    public function markAllAsRead(
        NotificationRepository  $notificationRepository,
        EntityManagerInterface  $entityManager,
        Request                 $request
    ): JsonResponse {
        // Verify the CSRF token for security
        if (!$this->isCsrfTokenValid('mark_notifications', $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 400);
        }

        // Verify user authentication
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        // Retrieve unread notifications for the user
        $notifications = $notificationRepository->findBy([
            'recipient' => $user
        ]);

        // Update each notification's read status
        foreach ($notifications as $notification) {
            $notification->setRead(true);
        }

        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'All notifications marked as read.']);
    }

    /**
     * @brief Marks a specific notification as read.
     *
     * Updates the read status of a single notification and redirects to the associated room's details.
     *
     * @param int                     $id                     The ID of the notification to mark as read.
     * @param NotificationRepository  $notificationRepository Repository to manage Notification entities.
     * @param EntityManagerInterface  $entityManager           The entity manager for database operations.
     *
     * @return Response A redirect response to the room's details page.
     *
     * @throws AccessDeniedHttpException If the user is not authenticated.
     * @throws NotFoundException If the notification is not found or does not belong to the user.
     */
    #[Route('/notifications/mark-as-read/{id}', name: 'mark_notification_as_read')]
    public function markAsRead(
        int                     $id,
        NotificationRepository  $notificationRepository,
        EntityManagerInterface  $entityManager
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('User not authenticated');
        }

        $notification = $notificationRepository->find($id);
        if (!$notification || $notification->getRecipient() !== $user) {
            throw $this->createNotFoundException('Notification not found');
        }

        // Mark the notification as read
        $notification->setRead(true);
        $entityManager->flush();

        // Redirect to the associated room's details
        return $this->redirectToRoute('app_rooms_details', ['name' => $notification->getRoom()->getName()]);
    }
}
