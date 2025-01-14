<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Utils\UserRoleEnum;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group functional
 *
 * Tests the analytics flow with the new route: /en/rooms/{name}/analytics/{dbname}.
 */
class AnalyticsTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    /**
     * Sets up the client, DB, and loads fixtures before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        // Purge database
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        // Load fixtures
        $loader = new Loader();
        $loader->addFixture(new AppFixtures(static::getContainer()->get('security.password_hasher')));
        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($loader->getFixtures());

        // Sanity check: we expect at least 1 room
        $rooms = $this->entityManager->getRepository(Room::class)->findAll();
        $this->assertNotEmpty($rooms, 'No Room found in fixtures - check AppFixtures.');
    }

    /**
     * Helper to login as Manager.
     */
    private function loginAsManager(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $managerUser = $userRepository->findOneByUsername('manager');
        $this->client->loginUser($managerUser);
    }

    

    /**
     * Fetches a Room that has an AcquisitionSystem
     */
    private function getLinkedRoomAndDbName(): array
    {
        /** @var RoomRepository $roomRepo */
        $roomRepo = $this->entityManager->getRepository(Room::class);

        $room = $roomRepo->findOneBy([]);
        $this->assertNotNull($room, 'No room found in repository.');

        $acqSystem = $room->getAcquisitionSystem();
        $this->assertNotNull($acqSystem, 'Room has no acquisition system — adjust fixture or test logic.');

        // Return both the Room name and the dbName
        return [$room->getName(), $acqSystem->getDbName()];
    }

    /**
     * Test that an unauthenticated user cannot access analytics.
     */
    public function testAnalyticsAccessDeniedForUnauthenticated(): void
{
    [$roomName, $dbName] = $this->getLinkedRoomAndDbName();

    try {
        // Attempt to access the analytics route without logging in
        $this->client->request('GET', sprintf('/rooms/%s/analytics/%s', $roomName, $dbName));

        // If no exception is thrown, the test should fail
        $this->fail('Expected HttpException was not thrown.');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        $this->assertContains(
            $e->getStatusCode(),
            [Response::HTTP_UNAUTHORIZED, Response::HTTP_NOT_FOUND],
            sprintf('Expected 401 Unauthorized or 404 Not Found, but got %d.', $e->getStatusCode())
        );

    }
}


    /**
     * Test that a Manager can load the analytics page successfully.
     */
    public function testAnalyticsAccessGrantedForManager(): void
    {
        $this->loginAsManager();
        [$roomName, $dbName] = $this->getLinkedRoomAndDbName();

        $this->client->request('GET', sprintf('/en/rooms/%s/analytics/%s', $roomName, $dbName));
        $this->assertResponseIsSuccessful(
            'Manager should access analytics page successfully.'
        );
    }



    /**
     * Test that the page renders the analytics elements (charts, script references) when loaded.
     */
    public function testAnalyticsPageRendersCharts(): void
    {
        $this->loginAsManager();
        [$roomName, $dbName] = $this->getLinkedRoomAndDbName();

        $crawler = $this->client->request('GET', sprintf('/en/rooms/%s/analytics/%s', $roomName, $dbName));
        $this->assertResponseIsSuccessful();

        // Check presence of the chart containers, e.g. temperatureChart, humidityChart, co2Chart
        $this->assertSelectorExists(
            '#temperatureChart',
            'Expected temperature chart canvas in analytics page.'
        );
        $this->assertSelectorExists(
            '#humidityChart',
            'Expected humidity chart canvas in analytics page.'
        );
        $this->assertSelectorExists(
            '#co2Chart',
            'Expected CO2 chart canvas in analytics page.'
        );

        // Check that Chart.js is included
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString(
            'https://cdn.jsdelivr.net/npm/chart.js',
            $html,
            'The page should contain a reference to Chart.js library.'
        );
    }


    /**
 * Test that the page outputs a JavaScript variable "historicalData" with data.
 */
public function testAnalyticsHistoricalDataRendered(): void
{
    $this->loginAsManager();
    [$roomName, $dbName] = $this->getLinkedRoomAndDbName();

    // Access analytics (with optional query param 'range=week' for variety)
    $crawler = $this->client->request('GET', sprintf('/en/rooms/%s/analytics/%s?range=week', $roomName, $dbName));
    $this->assertResponseIsSuccessful();

    // Check if the "historicalData" variable is present in <script>
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertStringContainsString(
        'const historicalData =',
        $pageContent,
        'Expected a JS variable "historicalData" to be injected in the analytics page.'
    );
}

    

}
