<?php

namespace App\Tests\Form;

use App\Entity\Room;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Utils\FloorEnum;
use App\Utils\CardinalEnum;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class AddRoomTypeTest
 *
 * Test suite for the Add Room form functionality.
 */
class AddRoomTypeTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $router;

    /**
     * Sets up the client, entity manager, and router for each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->router = static::getContainer()->get(RouterInterface::class);
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
        $testUser = $userRepository->findOneBy(['username' => $username]);

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
        $this->login('manager');

        // Génère l'URL pour la page d'ajout de salle en anglais
        $addRoomUrl = $this->router->generate('app_rooms_add', ['_locale' => 'en']);

        // Accède à la page d'ajout de salle
        $crawler = $this->client->request('GET', $addRoomUrl);

        // Vérifie que la page s'affiche correctement
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Sélectionne le formulaire
        $form = $crawler->selectButton('Add')->form(); // Assurez-vous que "Add" correspond au texte exact du bouton

        // Remplit les champs du formulaire avec des données valides
        $form['add_room[name]'] = 'A123';
        $form['add_room[floor]'] = FloorEnum::FIRST->value;
        $form['add_room[nbWindows]'] = 2;
        $form['add_room[nbHeaters]'] = 1;
        $form['add_room[surface]'] = 15;
        $form['add_room[cardinalDirection]'] = CardinalEnum::NORTH->value;

        // Soumet le formulaire
        $this->client->submit($form);

        $this->router->generate('app_rooms', ['_locale' => 'en']);

        // Vérifie que la liste des salles contient la nouvelle salle
        $this->assertGreaterThan(
            0,
            $crawler->filter('h5.card-title:contains("A123")')->count(),
            'The room "A123" was not found in the list.'
        );

        // Optionnel : Vérifie dans la base de données que la salle a bien été ajoutée
        /** @var RoomRepository $roomRepository */
        $roomRepository = static::getContainer()->get(RoomRepository::class);
        $room = $roomRepository->findOneBy(['name' => 'A123']);

        $this->assertNotNull($room, 'The room "A123" has been successfully created in the database.');
        $this->assertEquals(FloorEnum::FIRST, $room->getFloor());
        $this->assertEquals(2, $room->getNbWindows());
        $this->assertEquals(1, $room->getNbHeaters());
        $this->assertEquals(15, $room->getSurface());
        $this->assertEquals(CardinalEnum::NORTH, $room->getCardinalDirection());
    }
}
