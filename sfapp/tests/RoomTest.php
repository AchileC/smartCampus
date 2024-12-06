<?php

namespace App\Tests;

use App\Entity\Room;
use App\Utils\FloorEnum;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RoomTest extends WebTestCase
{
    public function testCreateRoom(): void
    {
        $client = static::createClient();

        // Accéder à la page de création de salle
        $crawler = $client->request('GET', '/rooms/add');
        $this->assertResponseIsSuccessful();

        // Soumettre le formulaire de création
        $form = $crawler->selectButton('Add Room')->form([
            'add_room[name]' => 'Test Room',
            'add_room[floor]' => FloorEnum::GROUND,
            'add_room[surface]' => 25.5,
            'add_room[nbHeaters]' => 2,
            'add_room[nbWindows]' => 3,
            'add_room[cardinalDirection]' => 'NORTH',
        ]);
        $client->submit($form);

        // Vérifier la redirection après la soumission
        $this->assertResponseRedirects('/rooms');
        $client->followRedirect();

        // Vérifier que la salle est affichée dans la liste
        $this->assertSelectorTextContains('.card-title', 'Test Room');
    }
}
