<?php

namespace App\Tests\Controller;

use App\Entity\Room;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\CardinalEnum;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @class RoomControllerTest
 * @brief Test suite for the RoomController.
 *
 * This class contains unit tests to verify:
 * - The presence and correctness of form fields and labels in the room list.
 * - Role-based visibility of the "Add Room" button for managers.
 */
class RoomControllerTest extends WebTestCase
{

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->client->setServerParameter('HTTP_ACCEPT_LANGUAGE', 'en');
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

    }

    protected function login(string $username): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByUsername($username);

        if (!$testUser) {
            throw new \InvalidArgumentException(sprintf('User with username "%s" not found.', $username));
        }

        $this->client->loginUser($testUser);
    }

    protected function createRoom(
        string          $name,
        RoomStateEnum   $state,
        SensorStateEnum $sensorState
    ): Room
    {
        $room = new Room();
        $room->setName($name)
            ->setFloor(FloorEnum::FIRST)
            ->setState($state)
            ->setSensorState($sensorState)
            ->setCardinalDirection(CardinalEnum::NORTH)
            ->setNbHeaters(1)
            ->setNbWindows(1)
            ->setSurface(1);

        $this->entityManager->persist($room);
        $this->entityManager->flush();

        return $room;
    }

    protected function assertRoomExists(string $roomName): void
    {
        $crawler = $this->client->request('GET', '/rooms');
        $this->assertGreaterThan(
            0,
            $crawler->filter('h5.card-title:contains("' . $roomName . '")')->count());
    }

    protected function assertRoomDoesNotExist(string $roomName): void
    {
        $crawler = $this->client->request('GET', '/rooms');
        $this->assertEquals(
            0,
            $crawler->filter('h5.card-title:contains("' . $roomName . '")')->count());
    }


    /**
     * @brief Verifies the presence and correctness of form fields and labels on the room list page.
     *
     * @test This test checks:
     * - If the filter form contains the expected fields: name, floor, and state.
     * - If the filter form fields have correct labels.
     * - If the reset and search buttons are present and labeled correctly.
     *
     * @return void
     */
    public function testRoomListFormFields(): void
    {

        // Make a GET request to the '/rooms' page
        $crawler = $this->client->request('GET', '/rooms');

        // Verify the presence of form fields
        $this->assertSelectorExists('input#filter_room_name', 'The name input field is missing.');
        $this->assertSelectorExists('select#filter_room_floor', 'The floor select field is missing.');
        $this->assertSelectorExists('select#filter_room_state', 'The state select field is missing.');

        // Verify the labels for the form fields
        $this->assertSelectorTextContains('label[for="filter_room_name"]', 'Name');
        $this->assertSelectorTextContains('label[for="filter_room_floor"]', 'Floor');
        $this->assertSelectorTextContains('label[for="filter_room_state"]', 'State');

        // Verify the presence and correctness of form buttons
        $this->assertSelectorExists('button#filter_room_reset', 'The reset button is missing.');
        $this->assertSelectorTextContains('button#filter_room_reset', 'Reset');

        $this->assertSelectorExists('button#filter_room_filter', 'The search button is missing.');
        $this->assertSelectorTextContains('button#filter_room_filter', 'Search');
    }

    /**
     * @brief Ensures the "Add Room", "Edit", and "Delete" buttons are visible only for users with the "manager" role.
     *
     * @test This test checks:
     * - That unauthenticated users do not see the "Add Room", "Edit", and "Delete" buttons on the '/rooms' page.
     * - That managers see these buttons after logging in.
     * @return void
     */

    public function testRoomElementsVisibleForUsersOnly(): void
    {
        $this->createRoom(
            'Not linked room test',
            RoomStateEnum::NO_DATA,
            SensorStateEnum::NOT_LINKED
        );

        $this->createRoom(
            'Linked room test',
            RoomStateEnum::STABLE,
            SensorStateEnum::LINKED
        );

        $crawler = $this->client->request('GET', '/rooms');

        $this->assertSelectorExists('a#details-button');
        $this->assertSelectorNotExists('a#update_button');
        $this->assertSelectorNotExists('button#delete_button');
        $this->assertSelectorNotExists('a#add_room_button');
        $this->assertSelectorNotExists('span#sensor_card_state');

        $this->assertRoomExists('Linked room test');
        $this->assertRoomDoesNotExist('Not linked room test');

    }

    public function testRoomElementsVisibleForManagerOnly(): void
    {
        $this->createRoom(
            'Not linked room test',
            RoomStateEnum::NO_DATA,
            SensorStateEnum::NOT_LINKED
        );

        $this->createRoom(
            'Linked room test',
            RoomStateEnum::STABLE,
            SensorStateEnum::LINKED
        );

       $this->login('manager');

        $crawler = $this->client->request('GET', '/rooms');

        $this->assertSelectorExists('a#details-button');
        $this->assertSelectorExists('a#update_button');
        $this->assertSelectorExists('button#delete_button');
        $this->assertSelectorExists('a#add_room_button');

        $this->assertSelectorNotExists('span#sensor_card_state');

        $this->assertRoomExists('Linked room test');
        $this->assertRoomExists('Not linked room test');
    }

    public function testRoomElementsVisibleForTechnicianOnly(): void
    {
        {
            $this->createRoom(
                'Not linked room test',
                RoomStateEnum::NO_DATA,
                SensorStateEnum::NOT_LINKED
            );

            $this->createRoom(
                'Linked room test',
                RoomStateEnum::STABLE,
                SensorStateEnum::LINKED
            );

            $this->login('technician');

            $crawler = $this->client->request('GET', '/rooms');

            $this->assertSelectorExists('a#details-button');
            $this->assertSelectorExists('span#sensor_card_state');

            $this->assertSelectorNotExists('a#update_button');
            $this->assertSelectorNotExists('button#delete_button');
            $this->assertSelectorNotExists('a#add_room_button');

            $this->assertRoomExists('Linked room test');
            $this->assertRoomExists('Not linked room test');
        }
    }

    public function testRoomNameAndStateDisplayed(): void
    {

        // login

        $this->login('technician');

        // Create rooms
        $this->createRoom(
            'At risk room',
            RoomStateEnum::AT_RISK,
            SensorStateEnum::LINKED
        );

        $this->createRoom(
            'Stable room',
            RoomStateEnum::STABLE,
            SensorStateEnum::LINKED
        );

        $this->createRoom(
            'Critical room',
            RoomStateEnum::CRITICAL,
            SensorStateEnum::LINKED
        );

        $this->createRoom(
            'None room',
            RoomStateEnum::NO_DATA,
            SensorStateEnum::NOT_LINKED
        );

        $crawler = $this->client->request('GET', '/rooms');

        // Find the cards
        $riskRoom = $crawler->filterXPath("//div[contains(@class, 'card') and .//h5[contains(text(), 'At risk room')]]");
        $stableRoom = $crawler->filterXPath("//div[contains(@class, 'card') and .//h5[contains(text(), 'Stable room')]]");
        $criticalRoom = $crawler->filterXPath("//div[contains(@class, 'card') and .//h5[contains(text(), 'Critical room')]]");
        $noneRoom = $crawler->filterXPath("//div[contains(@class, 'card') and .//h5[contains(text(), 'None room')]]");

        // Verify the presence of the rooms
        $this->assertGreaterThan(
            0,
            $riskRoom->count(),
            'The "At risk room" was not displayed.'
        );

        $this->assertGreaterThan(
            0,
            $stableRoom->count(),
            'The "Stable room" was not displayed.'
        );

        $this->assertGreaterThan(
            0,
            $criticalRoom->count(),
            'The "Critical room" was not displayed.'
        );

        $this->assertGreaterThan(
            0,
            $noneRoom->count(),
            'The "None room" was not displayed.'
        );

        // Verify the names
        $this->assertEquals(
            'At risk room',
            $riskRoom->filter('h5.card-title')->text(),
            'The displayed name for the "At risk room" is not correct.'
        );

        $this->assertEquals(
            'Stable room',
            $stableRoom->filter('h5.card-title')->text(),
            'The displayed name for the "Stable room" is not correct.'
        );

        $this->assertEquals(
            'Critical room',
            $criticalRoom->filter('h5.card-title')->text(),
            'The displayed name for the "Critical room" is not correct.'
        );

        $this->assertEquals(
            'None room',
            $noneRoom->filter('h5.card-title')->text(),
            'The displayed name for the "None room" is not correct.'
        );

        // Verify the states
        $this->assertEquals(
            'AT RISK',
            trim($riskRoom->filter('span.badge-custom-size')->text()),
            'The displayed state for the "At risk room" is not correct.'
        );

        $this->assertEquals(
            'STABLE',
            trim($stableRoom->filter('span.badge-custom-size')->text()),
            'The displayed state for the "Stable room" is not correct.'
        );

        $this->assertEquals(
            'CRITICAL',
            trim($criticalRoom->filter('span.badge-custom-size')->text()),
            'The displayed state for the "Critical room" is not correct.'
        );

        $this->assertEquals(
            'NONE',
            trim($noneRoom->filter('span.badge-custom-size')->text()),
            'The displayed state for the "None room" is not correct.'
        );
    }
}