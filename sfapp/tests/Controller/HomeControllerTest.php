<?php
namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class HomeControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->client->setServerParameter('HTTP_ACCEPT_LANGUAGE', 'en');
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
    }

    public function testNotificationButtonVisibleForManagers(): void
    {
        // Récupérer un utilisateur avec le rôle ROLE_MANAGER
        $testUser = $this->userRepository->findOneBy(['username' => 'manager']);

        // Connecter l'utilisateur
        $this->client->loginUser($testUser);

        // Faire une requête GET sur la page d'accueil
        $crawler = $this->client->request('GET', '/rooms');

        // Vérifiez que la réponse est réussie
        $this->assertResponseIsSuccessful();

        // Vérifiez que le bouton de notification est présent
        $this->assertSelectorExists('button#notificationButton', 'Le bouton de notification devrait être visible pour les managers.');
    }

    public function testNotificationButtonNotVisibleForNonManagers(): void
    {
        // Récupérer un utilisateur sans le rôle ROLE_MANAGER
        $testUser = $this->userRepository->findOneBy(['username' => 'user']);

        // Connecter l'utilisateur
        $this->client->loginUser($testUser);

        // Faire une requête GET sur la page d'accueil
        $crawler = $this->client->request('GET', '/rooms');

        // Vérifiez que la réponse est réussie
        $this->assertResponseIsSuccessful();

        // Vérifiez que le bouton de notification n'est pas présent
        $this->assertSelectorNotExists('button#notificationButton', 'Le bouton de notification ne devrait pas être visible pour les non-managers.');
    }
}
