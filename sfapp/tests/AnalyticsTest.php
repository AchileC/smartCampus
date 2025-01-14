<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Room;
use App\Entity\AcquisitionSystem;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group functional
 */
class AnalyticsTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialiser le client
        $this->client = static::createClient();
        $this->client->setServerParameter('HTTP_ACCEPT_LANGUAGE', 'en');

        // Récupérer l'EntityManager
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        // Purger la base
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        // Charger les fixtures
        $loader = new Loader();
        $loader->addFixture(new AppFixtures(static::getContainer()->get('security.password_hasher')));
        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($loader->getFixtures());

        // Vérifier que les fixtures sont bien chargées
        $rooms = $this->entityManager->getRepository(Room::class)->findAll();
        $this->assertNotEmpty($rooms, 'Les fixtures n\'ont pas été chargées correctement.');
    }

    /**
     * Helper pour connecter un technicien (si nécessaire).
     */
    private function loginAsTechnician(): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $technicianUser = $userRepository->findOneByUsername('technician');
        $this->assertNotNull($technicianUser, 'Technician user not found.');

        $this->client->loginUser($technicianUser);
    }

    /**
     * Crée un Room + AcquisitionSystem fictif.
     */
    private function createRoomWithAcquisitionSystem(
        string $roomName = 'TEST_ANALYTICS',
        string $dbName   = 'sae34bdk1eqXX'
    ): Room {
        $room = new Room();
        $room->setName($roomName)
             ->setFloor(FloorEnum::FIRST)
             ->setState(RoomStateEnum::STABLE)
             ->setSensorState(SensorStateEnum::LINKED);

        // AcquisitionSystem
        $acq = new AcquisitionSystem();
        $acq->setName('ESP-TEST-01')
            ->setDbName($dbName)
            ->setState(SensorStateEnum::LINKED)
            ->setRoom($room);

        $this->entityManager->persist($room);
        $this->entityManager->persist($acq);
        $this->entityManager->flush();

        return $room;
    }

    /**
     * Vérifie qu’un utilisateur non connecté obtient 401/403 (selon config).
     *
     * Si tu n'exiges pas l'authentification pour la page analytics,
     * tu peux supprimer ou adapter ce test.
     */
    public function testAnalyticsAccessDeniedIfNotLoggedIn(): void
    {
        // Tenter d’accéder à la page analytics sans être connecté
        $this->client->request('GET', '/rooms/FAKE_ROOM/analytics/FAKE_DB');
        
        // Vérifier le statut HTTP => soit 401 (non authentifié) ou 403 (forbidden)
        // Selon ta config, le code peut varier.
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED, 'Expected 401 Unauthorized.');
    }

    /**
     * Vérifie qu'un code 404 est renvoyé si la salle n’existe pas.
     */
    public function testAnalyticsRoomNotFound(): void
    {
        $this->loginAsTechnician();

        $this->client->request('GET', '/rooms/UNKNOWN_ROOM/analytics/sae34bdk1eqXX');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND, 'Expected 404 for unknown room.');
    }

    /**
     * Vérifie qu'un code 404 est renvoyé si la salle existe
     * mais le dbName passé en paramètre ne correspond pas.
     */
    public function testAnalyticsWrongDbName(): void
    {
        $this->loginAsTechnician();

        // 1) Créer la salle + AS avec un dbName "TEST_DB"
        $room = $this->createRoomWithAcquisitionSystem('ROOM_DB_MISMATCH', 'TEST_DB');

        // 2) Requête GET avec un dbName incorrect
        $this->client->request('GET', '/rooms/'.$room->getName().'/analytics/WRONG_DB');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND, 'Expected 404 for wrong dbName.');
    }

    /**
     * Vérifie qu'on obtient un statut 200 et un contenu correct 
     * quand tout est OK (salle existante, dbName correct).
     */
    public function testAnalyticsSuccess(): void
    {
        $this->loginAsTechnician();

        // 1) Créer la salle + AS
        $roomName = 'ANALYTICS_ROOM';
        $dbName   = 'ANALYTICS_DB';
        $room     = $this->createRoomWithAcquisitionSystem($roomName, $dbName);

        // 2) Appeler la route analytics
        $crawler = $this->client->request('GET', '/rooms/'.$roomName.'/analytics/'.$dbName);

        // 3) Vérifier le statut de la réponse
        $this->assertResponseIsSuccessful();

        // 4) Vérifier que le titre apparaît bien
        $this->assertSelectorTextContains('h1', 'Summary '.$roomName);

        // 5) Vérifier la présence des boutons/éléments importants
        $this->assertSelectorExists('button[data-range="month"]');
        $this->assertSelectorExists('button[data-range="week"]');

        // 6) Vérifier la présence des charts
        $this->assertSelectorExists('#temperatureChart');
        $this->assertSelectorExists('#humidityChart');
        $this->assertSelectorExists('#co2Chart');
    }
}
