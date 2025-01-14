<?php

namespace App\Tests\Functional\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Room;
use App\Entity\Action;
use App\Repository\UserRepository;
use App\Repository\ActionRepository;
use App\Utils\CardinalEnum;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use App\Utils\ActionStateEnum;
use App\Utils\ActionInfoEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Service\WeatherApiService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoomControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer le client et configurer les paramètres du serveur
        $this->client = static::createClient();
        $this->client->setServerParameter('HTTP_ACCEPT_LANGUAGE', 'en');

        // Récupérer l'EntityManager depuis le container
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        // Purger la base de données
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        // Charger les fixtures
        $loader = new Loader();
        $loader->addFixture(new AppFixtures(static::getContainer()->get('security.password_hasher')));

        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($loader->getFixtures());

        // Vérifier que les fixtures sont chargées correctement
        $rooms = $this->entityManager->getRepository(Room::class)->findAll();
        $this->assertNotEmpty($rooms, 'Les fixtures n\'ont pas été chargées correctement.');
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
            ->setNbHeaters(2)
            ->setNbWindows(3)
            ->setSurface(20);

        $this->entityManager->persist($room);
        $this->entityManager->flush();

        return $room;
    }

    protected function assertRoomExists(string $roomName): void
    {
        $crawler = $this->client->request('GET', '/en/rooms');
        $this->assertGreaterThan(
            0,
            $crawler->filter('h5.card-title:contains("' . $roomName . '")')->count());
    }

    protected function assertRoomDoesNotExist(string $roomName): void
    {
        $crawler = $this->client->request('GET', '/en/rooms');
        $this->assertEquals(
            0,
            $crawler->filter('h5.card-title:contains("' . $roomName . '")')->count());
    }

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

        $crawler = $this->client->request('GET', '/en/rooms');

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

        $crawler = $this->client->request('GET', '/en/rooms');

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

            $crawler = $this->client->request('GET', '/en/rooms');

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
            'No Data room',
            RoomStateEnum::NO_DATA,
            SensorStateEnum::NOT_LINKED
        );

        $crawler = $this->client->request('GET', '/en/rooms');

        // Find the cards
        $riskRoom = $crawler->filterXPath("//div[contains(@class, 'card') and .//h5[contains(text(), 'At risk room')]]");
        $stableRoom = $crawler->filterXPath("//div[contains(@class, 'card') and .//h5[contains(text(), 'Stable room')]]");
        $criticalRoom = $crawler->filterXPath("//div[contains(@class, 'card') and .//h5[contains(text(), 'Critical room')]]");
        $noneRoom = $crawler->filterXPath("//div[contains(@class, 'card') and .//h5[contains(text(), 'No Data room')]]");

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
            'The "No Data room" was not displayed.'
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
            'No Data room',
            $noneRoom->filter('h5.card-title')->text(),
            'The displayed name for the "No Data room" is not correct.'
        );

        // Verify the states
        $this->assertEquals(
            'AT RISK',
            trim($riskRoom->filter('span.badge-custom-size')->text()),
            'The displayed state for the "At risk room" is not correct.'
        );

        // Get the correct rooms URL using the router
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
            'NO DATA',
            trim($noneRoom->filter('span.badge-custom-size')->text()),
            'The displayed state for the "No Data room" is not correct.'
        );
    }

    public function testAddRoomAsManagerSuccess(): void
    {
        $this->login('manager');

        // 2) Accéder à la page /rooms/add
        $crawler = $this->client->request('GET', '/en/rooms/add');
        $this->assertResponseIsSuccessful(); // 200 OK
        $this->assertSelectorTextContains('h1', 'Add a room');

        // 3) Remplir le formulaire et soumettre
        $form = $crawler->selectButton('Add')->form([
            'add_room[name]'               => 'A123',
            'add_room[floor]'              => FloorEnum::GROUND->value,
            'add_room[nbWindows]'          => 2,
            'add_room[nbHeaters]'          => 1,
            'add_room[surface]'            => 20.0,
            'add_room[cardinalDirection]'  => 'north',
        ]);
        $this->client->submit($form);

        // 4) Vérifier qu’on est bien redirigé vers la liste des salles avec un flash
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert.alert-success', 'Room added successfully.');

        // 5) Vérifier que la salle est en base
        // On peut récupérer l’EntityManager et vérifier l’existence en BDD
        $roomRepository = static::getContainer()->get('doctrine')->getRepository(Room::class);
        $room = $roomRepository->findOneBy(['name' => 'A123']);
        $this->assertNotNull($room);
    }



    public function testAddRoomAsNonManagerDenied(): void
    {
        // On indique à PHPUnit qu'on s'attend à une exception HttpException :
        $this->expectException(HttpException::class);

        // ... et si vous voulez vérifier le message précis :
        $this->expectExceptionMessage('Full authentication is required to access this resource.');

        $this->client->request('GET', '/en/rooms/add');

    }

    public function testDeleteRoomNonManager(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->expectExceptionMessage('Full authentication is required to access this resource.');

        $room = $this->createRoom('TEST_DELETE_DENIED', RoomStateEnum::STABLE, SensorStateEnum::LINKED);

        $this->client->request('POST', '/en/rooms/'.$room->getName().'/delete', [
            '_token' => 'any'
        ]);

    }

    public function testDetailsPageShowsWeatherAdvice(): void
    {
        // 1) Mock du service
        $mockWeatherService = $this->createMock(WeatherApiService::class);
        // On fait un "stub" pour fetchWeatherData() => ne rien faire
        $mockWeatherService->method('fetchWeatherData')
            ->will($this->returnCallback(function () {
            }));

        // On renvoie un forecast factice
        $mockWeatherService->method('getForecast')->willReturn([
            [
                'temperature_min' => 30,
                'temperature_max' => 34,  // => “very_hot_advice”
                'precipitation'   => 60,  // => “moderate_rainfall_advice”
                'pictocode'       => 8,   // => de la pluie
            ]
        ]);
        static::getContainer()->set(WeatherApiService::class, $mockWeatherService);

        // 2) Créer une salle
        $uniqueName = 'WEATHER_TEST_' . uniqid();
        $room = $this->createRoom($uniqueName,
            RoomStateEnum::CRITICAL,
            SensorStateEnum::LINKED
        );

        // 3) Aller sur la page detail
        $this->client->request('GET', '/en/rooms/'.$room->getName());
        $this->assertResponseIsSuccessful();

        // 4) Vérifier que la page contient des conseils
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString("Moderate rainfall expected. Keep windows closed and use doors to ventilate the classroom as needed while avoiding excess humidity.", $html);
        $this->assertStringContainsString("Very hot today. Consider lowering the radiator settings and ventilate the classroom by opening windows or doors to maintain a comfortable environment.", $html);
        $this->assertStringContainsString("Wet conditions anticipated. Ensure proper ventilation to avoid humidity buildup by using doors if opening windows is not ideal.", $html);
    }


    public function testRequestAssignmentCreatesAction(): void
    {
        // 1. Se connecter en tant que manager
        $this->login('manager');

        // 2. Créer une salle en état NOT_LINKED et NO_DATA
        $roomName = 'TEST_ASSIGNMENT_ROOM';
        $room = $this->createRoom($roomName, RoomStateEnum::NO_DATA, SensorStateEnum::NOT_LINKED);

        // 3. Accéder à la page de mise à jour de la salle
        $crawler = $this->client->request('GET', '/en/rooms/' . $room->getName() . '/update');
        $this->assertResponseIsSuccessful();

        // 4. Trouver et soumettre le formulaire de demande d'assignation
        $form = $crawler->selectButton('Request assignment')->form(); // Assurez-vous que le bouton a bien ce label
        $this->client->submit($form);

        // 7. Vérifier que la tâche a bien été créée en base
        /** @var ActionRepository $actionRepository */
        $actionRepository = $this->entityManager->getRepository(Action::class);
        $action = $actionRepository->findOneBy([
            'info' => ActionInfoEnum::ASSIGNMENT,
            'state' => ActionStateEnum::TO_DO,
            'room' => $room,
        ]);

        $this->assertNotNull($action, 'La tâche d\'assignment n\'a pas été créée.');
        $this->assertEquals(ActionInfoEnum::ASSIGNMENT, $action->getInfo());
        $this->assertEquals(ActionStateEnum::TO_DO, $action->getState());
        $this->assertEquals($roomName, $action->getRoom()->getName());
    }

    /**
     * Teste la création d'une tâche d'UNASSIGNMENT pour une salle en état LINKED et STABLE.
     */
    public function testRequestUnassignmentCreatesAction(): void
    {
        // 1. Se connecter en tant que manager
        $this->login('manager');

        // 2. Créer une salle en état LINKED et STABLE
        $roomName = 'TEST_UNASSIGNMENT_ROOM';
        $room = $this->createRoom($roomName, RoomStateEnum::STABLE, SensorStateEnum::LINKED);

        // 3. Accéder à la page de mise à jour de la salle
        $crawler = $this->client->request('GET', '/en/rooms/' . $room->getName() . '/update');
        $this->assertResponseIsSuccessful();

        // 4. Trouver et soumettre le formulaire de demande de désassignation
        $form = $crawler->selectButton('Request unassignment')->form(); // Assurez-vous que le bouton a bien ce label
        $this->client->submit($form);

        // 7. Vérifier que la tâche a bien été créée en base
        /** @var ActionRepository $actionRepository */
        $actionRepository = $this->entityManager->getRepository(Action::class);
        $action = $actionRepository->findOneBy([
            'info' => ActionInfoEnum::UNASSIGNMENT,
            'state' => ActionStateEnum::TO_DO,
            'room' => $room,
        ]);

        $this->assertNotNull($action, 'La tâche de désassignment n\'a pas été créée.');
        $this->assertEquals(ActionInfoEnum::UNASSIGNMENT, $action->getInfo());
        $this->assertEquals(ActionStateEnum::TO_DO, $action->getState());
        $this->assertEquals($roomName, $action->getRoom()->getName());
    }

    /**
     * Teste l'annulation d'une tâche d'installation ou de désinstallation en cours.
     */
    public function testCancelInstallationDeletesAction(): void
    {
        // 1. Se connecter en tant que manager
        $this->login('manager');

        // 2. Créer une salle en état NOT_LINKED et NO_DATA
        $roomName = 'TEST_CANCEL_INSTALLATION_ROOM';
        $room = $this->createRoom($roomName, RoomStateEnum::NO_DATA, SensorStateEnum::NOT_LINKED);

        // 3. Créer une tâche d'ASSIGNMENT en état TO_DO
        $action = new Action();
        $action->setInfo(ActionInfoEnum::ASSIGNMENT);
        $action->setState(ActionStateEnum::TO_DO);
        $action->setCreatedAt(new \DateTime());
        $action->setRoom($room);

        $this->entityManager->persist($action);
        $this->entityManager->flush();

        // Vérifier que la tâche existe avant annulation
        $actionRepository = $this->entityManager->getRepository(Action::class);
        $existingAction = $actionRepository->find($action->getId());
        $this->assertNotNull($existingAction, 'La tâche d\'assignment devrait exister avant l\'annulation.');

        // 4. Accéder à la page de mise à jour de la salle
        $crawler = $this->client->request('GET', '/en/rooms/' . $room->getName() . '/update');
        $this->assertResponseIsSuccessful();

        // 5. Trouver et soumettre le formulaire d'annulation de l'installation
        $form = $crawler->selectButton('Cancel installation')->form(); // Assurez-vous que le bouton a bien ce label
        $this->client->submit($form);

        // 8. Vérifier que la tâche a bien été supprimée de la base
        /** @var ActionRepository $actionRepository */
        $actionRepository = $this->entityManager->getRepository(Action::class);
        $deletedAction = $actionRepository->find($action->getId());

        $this->assertNull($deletedAction, 'La tâche d\'installation/désinstallation n\'a pas été supprimée.');
    }

}
