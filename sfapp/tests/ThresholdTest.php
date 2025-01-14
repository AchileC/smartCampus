<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Repository\ThresholdRepository;
use App\Entity\Room;
use App\Entity\Threshold;
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
use Symfony\Component\HttpFoundation\Response;


/**
 * @group functional
 */
class ThresholdTest extends WebTestCase
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

    private function loginAsTechnician(): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $technicianUser = $userRepository->findOneByUsername('technician');

        $this->client->loginUser($technicianUser);
    }

   

    public function testThresholdModificationAccessDenied(): void
    {
        try {
            // Essayer d'accéder à la page sans être authentifié
            $this->client->request('GET', '/en/home/threshold');
            
            // Si aucune exception n'est levée, le test doit échouer
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $e) {
            // Vérifie que l'exception correspond à une authentification manquante
            $this->assertEquals(Response::HTTP_UNAUTHORIZED, $e->getStatusCode()); 
            $this->assertStringContainsString('Full authentication is required to access this resource.', $e->getMessage());
        }
    }
    


    public function testThresholdResetToDefault(): void
    {
        $this->loginAsTechnician();

        // Soumettre une demande de réinitialisation des seuils
        $this->client->request('POST', '/en/home/threshold/reset');
        $this->assertResponseRedirects('/en/home/threshold');
        $this->client->followRedirect();

        // Vérifier que les seuils ont été réinitialisés
        $threshold = $this->entityManager->getRepository(Threshold::class)->findOneBy([]);
        $this->assertEquals(16.0, $threshold->getHeatingTempCriticalMin());
        $this->assertEquals(18.0, $threshold->getHeatingTempWarningMin());
        $this->assertEquals(22.0, $threshold->getHeatingTempWarningMax());
        $this->assertEquals(24.0, $threshold->getHeatingTempCriticalMax());
    }
}
