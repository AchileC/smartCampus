<?php

namespace App\Tests\Functional\Controller;

use App\Entity\AcquisitionSystem;
use App\Entity\Action;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\ActionRepository;
use App\Repository\UserRepository;
use App\Utils\ActionInfoEnum;
use App\Utils\ActionStateEnum;
use App\Utils\CardinalEnum;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Functional tests for the HomeController.
 */
class HomeControllerTest extends WebTestCase
{
    private KernelBrowser $client; ///< HTTP client for testing.
    private EntityManagerInterface $entityManager; ///< Entity manager for database operations.

    /**
     * Set up the test environment.
     * Initializes the client and clears the database.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Initialize the client
        $this->client = static::createClient();
        $this->client->setServerParameter('HTTP_ACCEPT_LANGUAGE', 'en');

        // Get the entity manager
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        // Clear existing entities
        $this->removeAllEntities();
    }

    /**
     * Removes all entities of Room and AcquisitionSystem from the database.
     */
    private function removeAllEntities(): void
    {
        // Delete all Actions
        $query = $this->entityManager->createQuery('DELETE FROM App\Entity\Action a');
        $query->execute();

        // Delete all AcquisitionSystems
        $query = $this->entityManager->createQuery('DELETE FROM App\Entity\AcquisitionSystem a');
        $query->execute();

        // Delete all Rooms
        $query = $this->entityManager->createQuery('DELETE FROM App\Entity\Room r');
        $query->execute();
    }

    /**
     * Logs in a user by username.
     *
     * @param string $username The username of the user to log in.
     */
    protected function login(string $username): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByUsername($username);

        if (!$testUser) {
            throw new \InvalidArgumentException(sprintf('User with username "%s" not found.', $username));
        }

        $this->client->loginUser($testUser);
    }

    /**
     * Creates a new Room entity.
     *
     * @param string $name The name of the room.
     * @param RoomStateEnum $state The state of the room.
     * @param SensorStateEnum $sensorState The sensor state of the room.
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
     * Creates a new AcquisitionSystem entity.
     *
     * @param string $name The name of the acquisition system.
     * @param SensorStateEnum|null $state The state of the acquisition system.
     * @return AcquisitionSystem The created AcquisitionSystem entity.
     */
    protected function createAcquisitionSystem(
        string $name,
        ?SensorStateEnum $state = null
    ): AcquisitionSystem {
        $acquisitionSystem = new AcquisitionSystem();
        $randomDbName = 'db_' . uniqid(); // Generate a random database name
        $acquisitionSystem->setName($name)
            ->setDbName($randomDbName);

        $this->entityManager->persist($acquisitionSystem);
        $this->entityManager->flush();

        return $acquisitionSystem;
    }

    /**
     * Tests the dashboard counters for Rooms and AcquisitionSystems.
     */
    public function testDashboardCounters(): void
    {
        // Log in as manager
        $this->login('manager');

        // Create rooms and acquisition systems
        $this->createRoom('Critical room 1', RoomStateEnum::CRITICAL, SensorStateEnum::LINKED);
        $this->createRoom('Critical room 2', RoomStateEnum::CRITICAL, SensorStateEnum::LINKED);
        $this->createRoom('At risk room 1', RoomStateEnum::AT_RISK, SensorStateEnum::LINKED);
        $this->createRoom('Stable room 1', RoomStateEnum::STABLE, SensorStateEnum::LINKED);
        $this->createAcquisitionSystem('AS 1');
        $this->createAcquisitionSystem('AS 2');
        $this->createAcquisitionSystem('AS 3');

        // Verify counters as manager
        $crawler = $this->client->request('GET', '/en/home');
        $this->assertResponseIsSuccessful(); // Ensure the response is 200 OK
        $this->assertSelectorTextContains('h1', 'Hello manager!');

        $roomsCount = $crawler->filter('[data-test="rooms-count"]')->text();
        $this->assertEquals(4, (int)$roomsCount, 'The rooms counter should be 4.');

        $criticalCount = $crawler->filter('[data-test="critical-count"]')->text();
        $this->assertEquals(2, (int)$criticalCount, 'The critical rooms counter should be 2.');

        $atRiskCount = $crawler->filter('[data-test="at-risk-count"]')->text();
        $this->assertEquals(1, (int)$atRiskCount, 'The at-risk rooms counter should be 1.');

        // Log in as technician
        $this->login('technician');

        // Verify counters as technician
        $crawler = $this->client->request('GET', '/en/home');
        $this->assertResponseIsSuccessful(); // Ensure the response is 200 OK
        $this->assertSelectorTextContains('h1', 'Hello technician!');

        $asCount = $crawler->filter('[data-test="as-count"]')->text();
        $this->assertEquals(3, (int)$asCount, 'The acquisition systems counter should be 3.');
    }

    public function testActionsDisplayed(): void
    {
        $this->login('manager');
        $roomname = 'Test room';
        $room = $this->createRoom($roomname, RoomStateEnum::NO_DATA, SensorStateEnum::NOT_LINKED);

        $action = new Action();
        $action->setInfo(ActionInfoEnum::ASSIGNMENT);
        $action->setState(ActionStateEnum::TO_DO);
        $action->setCreatedAt(new \DateTime());
        $action->setRoom($room);

        $this->entityManager->persist($action);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', '/en/home');

        // Check if the action is displayed
        $actionSelector = sprintf('[data-test="action-%d"]', $action->getId());
        $this->assertCount(1, $crawler->filter($actionSelector), 'The action should be displayed.');

        // Verify action details
        $actionText = $crawler->filter($actionSelector)->text();
        $this->assertStringContainsString('Assignment on ' . $roomname, $actionText, 'The action info should be displayed.');
        $this->assertStringContainsString($room->getName(), $actionText, 'The room name should match.');



    }
}
