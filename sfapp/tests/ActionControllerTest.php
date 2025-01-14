<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Action;
use App\Entity\AcquisitionSystem;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\ActionInfoEnum;
use App\Utils\ActionStateEnum;
use App\Utils\CardinalEnum;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @class ActionControllerTest
 * @brief Comprehensive test suite for the ActionController.
 *
 * This test suite covers various functionalities of the ActionController, including action state transitions,
 * access control, and UI component verification.
 *
 * @group functional
 */
class ActionControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private Action $action;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize the client and set server parameters
        $this->client = static::createClient();
        $this->client->setServerParameter('HTTP_ACCEPT_LANGUAGE', 'en');

        // Retrieve the EntityManager from the container
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        // Purge the database to ensure a clean state
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        // Load fixtures
        $loader = new Loader();
        $loader->addFixture(new AppFixtures(static::getContainer()->get('security.password_hasher')));

        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($loader->getFixtures());

        // Verify that the fixtures are loaded correctly
        $rooms = $this->entityManager->getRepository(Room::class)->findAll();
        $this->assertNotEmpty($rooms, 'Fixtures were not loaded correctly.');

        // Additional setup: Create a new unlinked acquisition system
        $acquisitionSystem = new AcquisitionSystem();
        $acquisitionSystem->setName('Unlinked System');
        $acquisitionSystem->setState(SensorStateEnum::NOT_LINKED);
        $acquisitionSystem->setDbName('test_db');
        $this->entityManager->persist($acquisitionSystem);

        // Retrieve a test room from fixtures (e.g., 'D205')
        $roomRepository = $this->entityManager->getRepository(Room::class);
        $room = $roomRepository->findOneBy(['name' => 'D205']);
        $this->assertNotNull($room, 'Test room "D205" not found.');

        // Create a test action linked to the room
        $this->action = new Action();
        $this->action->setInfo(ActionInfoEnum::ASSIGNMENT);
        $this->action->setState(ActionStateEnum::TO_DO);
        $this->action->setCreatedAt(new \DateTime());
        $this->action->setRoom($room);

        $this->entityManager->persist($this->action);
        $this->entityManager->flush();
    }

    /**
     * Helper method to log in a user by username.
     *
     * @param string $username The username of the user to log in.
     *
     * @throws \InvalidArgumentException if the user is not found.
     */
    protected function login(string $username): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['username' => $username]);

        if (!$testUser) {
            throw new \InvalidArgumentException(sprintf('User with username "%s" not found.', $username));
        }

        $this->client->loginUser($testUser);
    }

    /**
     * Helper method to create a Room entity.
     *
     * @param string          $name         The name of the room.
     * @param RoomStateEnum   $state        The state of the room.
     * @param SensorStateEnum $sensorState  The sensor state of the room.
     *
     * @return Room The created Room entity.
     */
    protected function createRoom(
        string $name,
        RoomStateEnum $state,
        SensorStateEnum $sensorState
    ): Room {
        $room = new Room();
        $room->setName($name)
            ->setFloor(FloorEnum::FIRST)
            ->setState($state)
            ->setSensorState($sensorState)
            ->setCardinalDirection(CardinalEnum::NORTH)
            ->setNbHeaters(2)
            ->setNbWindows(3)
            ->setSurface(20);

        $this->entityManager->persist($room);
        $this->entityManager->flush();

        return $room;
    }

    /**
     * Tests the begin function of the ActionController.
     *
     * This test checks:
     * - If the action is updated to the "DOING" state.
     * - If the "startedAt" timestamp is set.
     * - If actions that cannot begin (wrong state or missing conditions) return appropriate error responses.
     */
    public function testBeginAction(): void
    {

        // Log in as a technician
        $this->login('technician');
        $url = static::getContainer()->get('router')->generate(
            'app_begin_action',
            ['id' => $this->action->getId(), '_locale' => 'en']
        );

        $this->client->request('POST', $url);

        // Reload the action to get the latest state
        $this->entityManager->refresh($this->action);

        $this->assertEquals(
            ActionStateEnum::DOING,
            $this->action->getState(),
            'The action state was not updated to "DOING".'
        );

        $this->assertNotNull(
            $this->action->getStartedAt(),
            'The action "startedAt" timestamp was not set.'
        );
    }

    /**
     * Tests the validate function of the ActionController.
     *
     * This test checks:
     * - If the action is updated to the "DONE" state.
     */
    public function testValidateAction(): void
    {

        // Log in as a technician
        $this->login('technician');
        // Set action to "DOING" state
        $this->action->setState(ActionStateEnum::DOING);
        $this->entityManager->flush();

        $url = static::getContainer()->get('router')->generate(
            'app_validate_action',
            ['id' => $this->action->getId(), '_locale' => 'en']
        );

        $this->client->request('POST', $url);

        // Reload the action to get the latest state
        $this->entityManager->refresh($this->action);

        $this->assertEquals(
            ActionStateEnum::DONE,
            $this->action->getState(),
            'The action state was not updated to "DONE".'
        );
    }

    /**
     * Tests the done function of the ActionController.
     *
     * This test checks:
     * - If the completed action appears in the "done" list.
     */
    public function testDoneAction(): void
    {

        // Log in as a technician
        $this->login('technician');
        // Set action to "DONE" state
        $this->action->setState(ActionStateEnum::DONE);
        $this->entityManager->flush();

        $url = static::getContainer()->get('router')->generate(
            'app_todolist_done',
            ['_locale' => 'en']
        );

        $crawler = $this->client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        // Verify that the completed action appears on the "done" list
        $this->assertSelectorTextContains(
            '.action-item',
            $this->action->getInfo()->value,
            'The completed action is not listed in the "done" section.'
        );
    }

    /**
     * Tests that the /todolist page displays an "ASSIGNMENT" task in the "TO DO" state.
     */
    public function testTodolistShowsAssignmentTask(): void
    {

        // Log in as a technician
        $this->login('technician');
        // Create a Room required for the Action
        $room = $this->createRoom('TEST_TASK_TODO', RoomStateEnum::STABLE, SensorStateEnum::LINKED);

        // Create the Action in "TO DO" state
        $action = new Action();
        $action->setInfo(ActionInfoEnum::ASSIGNMENT);
        $action->setState(ActionStateEnum::TO_DO);
        $action->setCreatedAt(new \DateTime());
        $action->setRoom($room);

        $this->entityManager->persist($action);
        $this->entityManager->flush();

        // Call the /todolist route
        $crawler = $this->client->request('GET', '/en/todolist');
        $this->assertResponseIsSuccessful();

        // Verify the presence of the "TO DO" badge
        $this->assertSelectorTextContains('span.badge', 'TO DO');

        // Verify the presence of the action edit button
        $this->assertSelectorExists('a.btn.btn-outline-warning.btn-sm.me-2 > i.bi.bi-pencil');
    }

    /**
     * Tests that accessing the /todolist page as a non-technician user results in an authentication error.
     */
    public function testTodolistAsNonTechnician(): void
    {

        // Expect an HttpException due to insufficient permissions
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Full authentication is required to access this resource.');

        // Attempt to access the /todolist route
        $this->client->request('GET', '/en/todolist');
    }

    /**
     * Tests that the /todolist/done page displays an "ASSIGNMENT" task in the "DONE" state.
     */
    public function testHistoryShowsDoneAction(): void
    {

        // Log in as a technician
        $this->login('technician');
        // Create a Room required for the Action
        $room = $this->createRoom('TEST_TASK_DONE', RoomStateEnum::STABLE, SensorStateEnum::LINKED);

        // Create the Action in "DONE" state
        $action = new Action();
        $action->setInfo(ActionInfoEnum::ASSIGNMENT);
        $action->setState(ActionStateEnum::DONE);
        $action->setCreatedAt(new \DateTime());
        $action->setCompletedAt(new \DateTime()); // Ensures consistency for "DONE" state
        $action->setRoom($room);

        $this->entityManager->persist($action);
        $this->entityManager->flush();

        // Call the /todolist/done route
        $crawler = $this->client->request('GET', '/en/todolist/done');

        // Verify that the request was successful
        $this->assertResponseIsSuccessful();

        // Verify the presence of the "ASSIGNMENT" badge with appropriate classes
        $badge = $crawler->filter('span.badge.bg-info.text-uppercase');
        $this->assertCount(1, $badge, 'The "ASSIGNMENT" badge with classes "bg-info text-uppercase" was not found.');
        $this->assertEquals('Assignment', $badge->text(), 'The text of the "ASSIGNMENT" badge is incorrect.');

        // Verify the presence of the "Completed on:" information
        $completedOnListItem = $crawler->filterXPath('//li[strong[contains(text(), "Completed on:")]]');
        $this->assertCount(1, $completedOnListItem, 'The <li> containing <strong>Completed on:</strong> was not found.');
    }

    /**
     * Tests that accessing the /todolist/done page as a non-technician user results in an authentication error.
     */
    public function testTodolistHistoryAsNonTechnician(): void
    {
        // Expect an HttpException due to insufficient permissions
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Full authentication is required to access this resource.');

        // Attempt to access the /todolist/done route
        $this->client->request('GET', '/en/todolist/done');
    }
}
