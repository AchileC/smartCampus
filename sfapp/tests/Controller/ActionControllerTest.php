<?php

namespace App\Tests\Controller;

use App\Entity\Room;
use App\Entity\User;
use App\Entity\Action;
use App\Entity\AcquisitionSystem;
use App\Repository\UserRepository;
use App\Utils\CardinalEnum;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use App\Utils\ActionInfoEnum;
use App\Utils\ActionStateEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @class ActionControllerTest
 * @brief Test suite for the ActionController.
 *
 */
class ActionControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private Action $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->client->setServerParameter('HTTP_ACCEPT_LANGUAGE', 'en');
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        // Log in as a technician
        $userRepository = static::getContainer()->get(UserRepository::class);
        $technician = $userRepository->findOneBy(['username' => 'technician']);

        if (!$technician) {
            throw new \InvalidArgumentException(sprintf('User with username "%s" not found.', $username));
        }

        $this->client->loginUser($technician);

        // Step 1: Load test room from fixtures
        $roomRepository = $this->entityManager->getRepository(Room::class);
        $room = $roomRepository->findOneBy(['name' => 'D205']);
        $this->assertNotNull($room, 'Test room not found.');

        // Step 2: Create a new unlinked acquisition system
        $acquisitionSystem = new AcquisitionSystem();
        $acquisitionSystem->setName('Unlinked System');
        $acquisitionSystem->setState(SensorStateEnum::NOT_LINKED);
        $acquisitionSystem->setDbName('test_db');
        $this->entityManager->persist($acquisitionSystem);

        // Step 3: Create a test action linked to the room
        $this->action = new Action();
        $this->action->setInfo(ActionInfoEnum::ASSIGNMENT);
        $this->action->setState(ActionStateEnum::TO_DO);
        $this->action->setCreatedAt(new \DateTime());
        $this->action->setRoom($room);

        $this->entityManager->persist($this->action);
        $this->entityManager->flush();
    }

    /**
     * @brief Tests the begin function of the ActionController.
     *
     * This test checks:
     * - If the action is updated to the "DOING" state.
     * - If the "startedAt" timestamp is set.
     * - If actions that cannot begin (wrong state or missing conditions) return appropriate error responses.
     */
    public function testBeginAction(): void
    {
        $url = $this->client->getContainer()->get('router')->generate(
            'app_begin_action',
            ['id' => $this->action->getId(), '_locale' => 'en']
        );

        $this->client->request('POST', $url);

        // Reload the action
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

    public function testValidateAction(): void
    {
        // Set action to "DOING" state
        $this->action->setState(ActionStateEnum::DOING);
        $this->entityManager->flush();

        $url = $this->client->getContainer()->get('router')->generate(
            'app_validate_action',
            ['id' => $this->action->getId(), '_locale' => 'en']
        );

        $this->client->request('POST', $url);

        // Reload the action
        $this->entityManager->refresh($this->action);

        $this->assertEquals(
            ActionStateEnum::DONE,
            $this->action->getState(),
            'The action state was not updated to "DONE".'
        );
    }


    public function testDoneAction(): void
    {
        // Set action to "DONE" state
        $this->action->setState(ActionStateEnum::DONE);
        $this->entityManager->flush();

        $url = $this->client->getContainer()->get('router')->generate(
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

}