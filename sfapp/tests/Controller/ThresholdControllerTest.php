<?php

namespace App\Tests\Controller;

use App\Entity\Threshold;
use App\Entity\User;
use App\Repository\ThresholdRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ThresholdControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $thresholdRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->thresholdRepository = $this->entityManager->getRepository(Threshold::class);

        // Create a test user with ROLE_TECHNICIAN
        $user = new User();
        $user->setUsername('test_technician')
            ->setPassword('$2y$13$PJeqRVt1.jH1.5nwuuHYwOX.SHxUxqLYwH6BMxm0pqn6vxf4.q1.q') // 'password123'
            ->setRoles(['ROLE_TECHNICIAN']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up the database
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Threshold')->execute();
        
        $this->entityManager->close();
        $this->entityManager = null;
    }

    /**
     * @group access
     */
    public function testAccessDeniedForNonTechnician(): void
    {
        $this->client->request('GET', '/threshold');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND); // 302 redirect to login
    }

    /**
     * @group access
     */
    public function testAccessGrantedForTechnician(): void
    {
        // Log in as technician
        $this->client->loginUser($this->entityManager->getRepository(User::class)->findOneBy(['username' => 'test_technician']));
        
        $this->client->request('GET', '/threshold');
        $this->assertResponseIsSuccessful();
    }

    /**
     * @group form
     */
    public function testFormDisplaysWithDefaultValues(): void
    {
        // Log in as technician
        $this->client->loginUser($this->entityManager->getRepository(User::class)->findOneBy(['username' => 'test_technician']));
        
        $crawler = $this->client->request('GET', '/threshold');
        
        // Check if form elements exist
        $this->assertSelectorExists('form[name="threshold"]');
        $this->assertSelectorExists('#threshold_heatingTempCriticalMin');
        $this->assertSelectorExists('#threshold_heatingTempWarningMin');
        $this->assertSelectorExists('#threshold_heatingTempWarningMax');
        $this->assertSelectorExists('#threshold_heatingTempCriticalMax');
        $this->assertSelectorExists('#threshold_humCriticalMin');
        $this->assertSelectorExists('#threshold_humWarningMin');
        $this->assertSelectorExists('#threshold_humWarningMax');
        $this->assertSelectorExists('#threshold_humCriticalMax');
    }

    /**
     * @group reset
     */
    public function testResetToDefaultValues(): void
    {
        // Log in as technician
        $this->client->loginUser($this->entityManager->getRepository(User::class)->findOneBy(['username' => 'test_technician']));
        
        // First, modify some values
        $threshold = $this->thresholdRepository->getDefaultThresholds();
        $threshold->setHeatingTempCriticalMin(15.0)
                 ->setHeatingTempWarningMin(17.0)
                 ->setHeatingTempWarningMax(23.0)
                 ->setHeatingTempCriticalMax(25.0);
        
        $this->entityManager->flush();

        // Then reset
        $this->client->request('GET', '/threshold/reset');
        
        // Refresh the entity
        $this->entityManager->refresh($threshold);
        
        // Check if values are reset to defaults
        $this->assertEquals(16.0, $threshold->getHeatingTempCriticalMin());
        $this->assertEquals(18.0, $threshold->getHeatingTempWarningMin());
        $this->assertEquals(22.0, $threshold->getHeatingTempWarningMax());
        $this->assertEquals(24.0, $threshold->getHeatingTempCriticalMax());
    }

    /**
     * @group database
     */
    public function testDatabasePersistence(): void
    {
        // Log in as technician
        $this->client->loginUser($this->entityManager->getRepository(User::class)->findOneBy(['username' => 'test_technician']));
        
        // Submit form with new values
        $this->client->request('POST', '/threshold', [
            'threshold' => [
                'heatingTempCriticalMin' => 15.0,
                'heatingTempWarningMin' => 17.0,
                'heatingTempWarningMax' => 23.0,
                'heatingTempCriticalMax' => 25.0,
                'nonHeatingTempCriticalMin' => 21.0,
                'nonHeatingTempWarningMin' => 23.0,
                'nonHeatingTempWarningMax' => 27.0,
                'nonHeatingTempCriticalMax' => 29.0,
                'humCriticalMin' => 25.0,
                'humWarningMin' => 35.0,
                'humWarningMax' => 65.0,
                'humCriticalMax' => 75.0,
            ]
        ]);

        // Check if redirect after successful submission
        $this->assertResponseRedirects();

        // Verify values in database
        $threshold = $this->thresholdRepository->getDefaultThresholds();
        
        $this->assertEquals(15.0, $threshold->getHeatingTempCriticalMin());
        $this->assertEquals(17.0, $threshold->getHeatingTempWarningMin());
        $this->assertEquals(23.0, $threshold->getHeatingTempWarningMax());
        $this->assertEquals(25.0, $threshold->getHeatingTempCriticalMax());
        
        $this->assertEquals(21.0, $threshold->getNonHeatingTempCriticalMin());
        $this->assertEquals(23.0, $threshold->getNonHeatingTempWarningMin());
        $this->assertEquals(27.0, $threshold->getNonHeatingTempWarningMax());
        $this->assertEquals(29.0, $threshold->getNonHeatingTempCriticalMax());
        
        $this->assertEquals(25.0, $threshold->getHumCriticalMin());
        $this->assertEquals(35.0, $threshold->getHumWarningMin());
        $this->assertEquals(65.0, $threshold->getHumWarningMax());
        $this->assertEquals(75.0, $threshold->getHumCriticalMax());
    }
} 