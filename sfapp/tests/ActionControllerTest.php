<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Action;
use App\Entity\Room;
use App\Repository\UserRepository;
use App\Utils\ActionInfoEnum;
use App\Utils\ActionStateEnum;
use App\Utils\CardinalEnum;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @group functional
 */
class ActionControllerTest extends WebTestCase
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
    /**
     * Teste que la page /todolist affiche bien une tâche "ASSIGNMENT" en état TO DO.
     */
    public function testTodolistShowsAssignmentTask(): void
    {
        // 1. Démarrer le client
        $this->login('technician');

        // 3. Créer une Room (si obligatoire pour l’Action)
        $room = $this->createRoom('TEST_TASK_TODO', RoomStateEnum::STABLE, SensorStateEnum::LINKED);
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        // 4. Créer l’Action en état TO DO
        $action = new Action();
        $action->setInfo(ActionInfoEnum::ASSIGNMENT);
        $action->setState(ActionStateEnum::TO_DO);
        $action->setCreatedAt(new \DateTime());  // ou DateTimeImmutable
        $action->setRoom($room);

        $this->entityManager->persist($action);
        $this->entityManager->flush();

        // 5. Appeler la route /todolist
        $crawler = $this->client->request('GET', '/en/todolist');
        $this->assertResponseIsSuccessful();

        // 6. Vérifier la présence du badge "TO DO"
        $this->assertSelectorTextContains('span.badge', 'TO DO');

        // 7. Vérifier la présence du bouton d'action (édition)
        $this->assertSelectorExists('a.btn.btn-outline-warning.btn-sm.me-2 > i.bi.bi-pencil');

    }

    public function testTodolistAsNonTechnician(): void
    {
        // On indique à PHPUnit qu'on s'attend à une exception HttpException :
        $this->expectException(HttpException::class);

        // ... et si vous voulez vérifier le message précis :
        $this->expectExceptionMessage('Full authentication is required to access this resource.');

        $this->client->request('GET', '/en/todolist');

    }

    /**
     * Teste que la page /todolist/done affiche bien une tâche "ASSIGNMENT" en état DONE.
     */
    public function testHistoryShowsDoneAction(): void
    {
        // 1. Démarrer le client
        $this->login('technician');

        // 3. Créer une Room
        $room = $this->createRoom('TEST_TASK_DONE', RoomStateEnum::STABLE, SensorStateEnum::LINKED);
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        // 4. Créer l’Action en état DONE
        $action = new Action();
        $action->setInfo(ActionInfoEnum::ASSIGNMENT);
        $action->setState(ActionStateEnum::DONE);
        $action->setCreatedAt(new \DateTime());
        $action->setCompletedAt(new \DateTime()); // pour être cohérent avec un DONE
        $action->setRoom($room);

        $this->entityManager->persist($action);
        $this->entityManager->flush();

        // 5. Appeler la route /todolist/done
        $crawler = $this->client->request('GET', '/en/todolist/done');

        // 6. Vérifier que la requête s’est bien passée
        $this->assertResponseIsSuccessful();

        // 7. Vérifier la présence du badge "ASSIGNMENT" avec les classes appropriées
        $badge = $crawler->filter('span.badge.bg-info.text-uppercase');
        $this->assertCount(1, $badge, 'Le badge ASSIGNMENT avec les classes bg-info text-uppercase n\'a pas été trouvé.');
        $this->assertEquals('Assignment', $badge->text(), 'Le texte du badge ASSIGNMENT est incorrect.');

        $completedOnListItem = $crawler->filterXPath('//li[strong[contains(text(), "Completed on:")]]');
        $this->assertCount(1, $completedOnListItem, 'Le <li> contenant <strong>Completed on:</strong> n\'a pas été trouvé.');
    }

    public function testTodolistHistoryAsNonTechnician(): void
    {
        // On indique à PHPUnit qu'on s'attend à une exception HttpException :
        $this->expectException(HttpException::class);

        // ... et si vous voulez vérifier le message précis :
        $this->expectExceptionMessage('Full authentication is required to access this resource.');

        $this->client->request('GET', '/en/todolist/done');

    }
}
