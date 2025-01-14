<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\AcquisitionSystem;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\HttpClient;

class ApiTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    protected function setUp(): void
    {
        parent::setUp();

        static::createClient();

        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        // Purge la base et charge les Fixtures
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        $loader = new Loader();
        $loader->addFixture(new AppFixtures(
            static::getContainer()->get('security.password_hasher')
        ));
        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($loader->getFixtures());
    }

    /**
     * Teste la récupération des données d'un capteur temp depuis l'API.
     */
    public function testFetchSensorData(): void
    {
        // récupère un AcquisitionSystem dans la BDD (pour récupérer dbName + name)
        /** @var AcquisitionSystem|null $acquisitionSystem */
        $acquisitionSystem = $this->entityManager
            ->getRepository(AcquisitionSystem::class)
            ->findOneBy([]);
        $this->assertNotNull($acquisitionSystem, 'No AcquisitionSystem found in the database.');

        $client = HttpClient::create();

        $response = $client->request('GET', 'https://sae34.k8s.iut-larochelle.fr/api/captures/last', [
            'headers' => [
                'dbname'   => $acquisitionSystem->getDbName(),
                'username' => 'm1eq2',
                'userpass' => 'kabxaq-4qopra-quXvit',
            ],
            'query' => [
                'nom'   => 'temp',
                'nomsa' => $acquisitionSystem->getName(),
            ],
        ]);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertNotEmpty($data, 'Expected some data in the response.');
    }

    /**
     * Teste la récupération des données historiques  depuis l'API.
     */
    public function testFetchHistoricalData(): void
    {
        // Récupération d'un AcquisitionSystem
        /** @var AcquisitionSystem|null $acquisitionSystem */
        $acquisitionSystem = $this->entityManager
            ->getRepository(AcquisitionSystem::class)
            ->findOneBy([]);
        $this->assertNotNull($acquisitionSystem);

        $client = HttpClient::create();

        $response = $client->request('GET', 'https://sae34.k8s.iut-larochelle.fr/api/captures/interval', [
            'headers' => [
                'dbname'   => $acquisitionSystem->getDbName(),
                'username' => 'm1eq2',
                'userpass' => 'kabxaq-4qopra-quXvit',
            ],
            'query' => [
                'nom'   => 'temp',
                'date1' => (new \DateTime('-7 days'))->format('Y-m-d'),
                'date2' => (new \DateTime('+1 day'))->format('Y-m-d'),
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertNotEmpty($data, 'Historical data should not be empty.');
    }
}
