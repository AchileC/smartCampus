<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AnalyticsTest extends WebTestCase
{
    public function testAnalyticsPageAccessibleForManager()
    {
        $client = static::createClient();

        // Simule un utilisateur avec le rôle ROLE_MANAGER
        $client->loginUser($this->createUserWithRole('ROLE_MANAGER'));

        $crawler = $client->request('GET', '/rooms/TestRoom/analytics/TestDbName');

        // Vérifie que la réponse est réussie
        $this->assertResponseIsSuccessful();

        // Vérifie que la page contient le titre Analytics
        $this->assertSelectorTextContains('h1', 'Analytics Summary TestRoom');

        // Vérifie que les graphiques sont présents
        $this->assertSelectorExists('#temperatureChart', 'The temperature chart should be displayed.');
        $this->assertSelectorExists('#humidityChart', 'The humidity chart should be displayed.');
        $this->assertSelectorExists('#co2Chart', 'The CO2 chart should be displayed.');
    }

    public function testAnalyticsPageForbiddenForNonManager()
    {
        $client = static::createClient();

        // Simule un utilisateur sans le rôle ROLE_MANAGER
        $client->loginUser($this->createUserWithRole('ROLE_USER'));

        $client->request('GET', '/rooms/TestRoom/analytics/TestDbName');

        // Vérifie que l'accès est interdit
        $this->assertResponseStatusCodeSame(403, 'Non-manager users should receive a 403 response.');
    }

    public function testAnalyticsPageDisplaysNoDataMessageWhenEmpty()
    {
        $client = static::createClient();

        // Simule un utilisateur avec le rôle ROLE_MANAGER
        $client->loginUser($this->createUserWithRole('ROLE_MANAGER'));

        $crawler = $client->request('GET', '/rooms/TestRoom/analytics/EmptyDbName');

        // Vérifie que la réponse est réussie
        $this->assertResponseIsSuccessful();

        // Vérifie que les messages "No data" sont affichés
        $this->assertSelectorExists('#temperatureNoData', 'No data message for temperature should be displayed.');
        $this->assertSelectorExists('#humidityNoData', 'No data message for humidity should be displayed.');
        $this->assertSelectorExists('#co2NoData', 'No data message for CO2 should be displayed.');
    }

    public function testAnalyticsPageDisplaysDataCorrectly()
    {
        $client = static::createClient();

        // Simule un utilisateur avec le rôle ROLE_MANAGER
        $client->loginUser($this->createUserWithRole('ROLE_MANAGER'));

        $crawler = $client->request('GET', '/rooms/TestRoom/analytics/ValidDbName');

        // Vérifie que la réponse est réussie
        $this->assertResponseIsSuccessful();

        // Vérifie que les graphiques sont affichés avec des données
        $this->assertSelectorExists('#temperatureChart', 'Temperature chart should display.');
        $this->assertSelectorExists('#humidityChart', 'Humidity chart should display.');
        $this->assertSelectorExists('#co2Chart', 'CO2 chart should display.');
    }

    public function testAnalyticsPageRangeSelectorUpdatesCharts()
    {
        $client = static::createClient();

        // Simule un utilisateur avec le rôle ROLE_MANAGER
        $client->loginUser($this->createUserWithRole('ROLE_MANAGER'));

        $crawler = $client->request('GET', '/rooms/TestRoom/analytics/ValidDbName');

        // Vérifie que la page contient les boutons de sélection de plage temporelle
        $this->assertSelectorExists('.time-range-selector button[data-range="month"]');
        $this->assertSelectorExists('.time-range-selector button[data-range="week"]');

        // Simule un clic sur le bouton "Last 7 Days"
        $button = $crawler->filter('.time-range-selector button[data-range="week"]')->first();
        $crawler = $client->click($button->link());

        // Vérifie que les graphiques sont mis à jour pour afficher les données des 7 derniers jours
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#temperatureChart', 'Temperature chart should be updated.');
    }

    private function createUserWithRole(string $role)
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setRoles([$role]);

        return $user;
    }
}
