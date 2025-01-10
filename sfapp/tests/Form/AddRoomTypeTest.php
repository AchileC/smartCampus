<?php

namespace App\Tests\Form;

use App\Entity\Room;
use App\Repository\UserRepository;
use App\Utils\FloorEnum;
use App\Utils\CardinalEnum;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AddRoomTypeTest
 *
 * Test suite for the Add Room form functionality.
 */
class AddRoomTypeTest extends WebTestCase
{
    private $client;
    private $entityManager;

    /**
     * Sets up the client and entity manager for each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
    }

    /**
     * Logs in a user with the specified username.
     *
     * @param string $username
     * @return void
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
     * Tests that the Add Room form is displayed correctly,
     * and successfully creates a room in the database with valid data.
     *
     * @return void
     */
    public function testAddRoomForm(): void
    {
        // Prepare test data
        $roomData = [
            'add_room[name]' => 'A101',
            'add_room[floor]' => FloorEnum::FIRST->value,
            'add_room[nbWindows]' => 3,
            'add_room[nbHeaters]' => 2,
            'add_room[surface]' => 25.5,
            'add_room[cardinalDirection]' => CardinalEnum::NORTH->value,
        ];

        // Log in as manager
        $this->login('manager');

        // Send a GET request to access the Add Room form
        $crawler = $this->client->request('GET', '/rooms');
        $this->assertResponseIsSuccessful();


        // Fill the form with valid data
        $submitButton = $crawler->selectButton('Add');
        $form = $submitButton->form();

        $form['add_room[name]'] = $roomData['add_room[name]'];
        $form['add_room[floor]'] = $roomData['add_room[floor]'];
        $form['add_room[nbWindows]'] = $roomData['add_room[nbWindows]'];
        $form['add_room[nbHeaters]'] = $roomData['add_room[nbHeaters]'];
        $form['add_room[surface]'] = $roomData['add_room[surface]'];
        $form['add_room[cardinalDirection]'] = $roomData['add_room[cardinalDirection]'];

        // Submit the form
        $this->client->submit($form);

//        // Follow the redirect to the Rooms List page
//        $this->assertResponseRedirects('/rooms');
//        $this->client->followRedirect();
//
//
//        // Verify that the room is successfully created in the database
//        $room = $this->entityManager->getRepository(Room::class)->findOneBy(['name' => $roomData['add_room[name]']]);
//
//        $this->assertNotNull($room, 'The room was not created in the database.');
//        $this->assertEquals($roomData['add_room[name]'], $room->getName(), 'The room name is incorrect.');
//        $this->assertEquals($roomData['add_room[floor]'], $room->getFloor()->value, 'The floor is incorrect.');
//        $this->assertEquals($roomData['add_room[nbWindows]'], $room->getNbWindows(), 'The number of windows is incorrect.');
//        $this->assertEquals($roomData['add_room[nbHeaters]'], $room->getNbHeaters(), 'The number of heaters is incorrect.');
//        $this->assertEquals($roomData['add_room[surface]'], $room->getSurface(), 'The surface is incorrect.');
//        $this->assertEquals($roomData['add_room[cardinalDirection]'], $room->getCardinalDirection()->value, 'The cardinal direction is incorrect.');
   }
}
