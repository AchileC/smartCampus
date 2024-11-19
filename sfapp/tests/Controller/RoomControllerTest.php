<?php

namespace App\Tests\Controller;

use App\Entity\Room;
use App\Form\FilterRoomType;
use App\Form\AddRoomType;
use App\Repository\RoomRepository;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RoomControllerTest extends WebTestCase
{

    public function testIndexPageDisplaysRoomsList()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/rooms');

        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('h1', 'Rooms List');

        $this->assertGreaterThan(
            0,
            $crawler->filter('.card-title')->count(),
            'Expected at least one room to be displayed.'
        );
    }

    public function testFilterRoomsByName()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/rooms');

        // Simuler la soumission du formulaire de filtre avec un nom de salle
        $form = $crawler->selectButton('Filter')->form([
            'filter_room[name]' => 'D001',
        ]);

        $crawler = $client->submit($form);

        // Vérifier que la requête a réussi
        $this->assertResponseIsSuccessful();

        // Vérifier que seule la salle correspondant au filtre est affichée
        $this->assertEquals(
            1,
            $crawler->filter('.card-title:contains("D001")')->count(),
            'Expected only one room with name "D001" to be displayed.'
        );
    }

    public function testFilterRoomsByFloor()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/rooms');

        // Simuler la soumission du formulaire de filtre par étage
        $form = $crawler->selectButton('Filter')->form([
            'filter_room[floor]' => FloorEnum::GROUND->value, // Utilisation de ->value pour obtenir la valeur de l'énumération
        ]);

        $crawler = $client->submit($form);

        // Vérifier que la requête a réussi
        $this->assertResponseIsSuccessful();

        // Vérifier qu'au moins une salle du rez-de-chaussée est affichée
        $this->assertGreaterThan(
            0,
            $crawler->filter('.card-text:contains("Floor: ground")')->count(),
            'Expected at least one room on the ground floor to be displayed.'
        );
    }

    public function testFilterRoomsByState()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/rooms');

        // Simuler la soumission du formulaire de filtre par état
        $form = $crawler->selectButton('Filter')->form([
            'filter_room[state]' => RoomStateEnum::PENDING_ASSIGNMENT->value, // Utilisation de ->value pour obtenir la valeur de l'énumération
        ]);

        $crawler = $client->submit($form);

        // Vérifier que la requête a réussi
        $this->assertResponseIsSuccessful();

        // Vérifier qu'au moins une salle dans l'état PENDING_ASSIGNMENT est affichée
        $this->assertGreaterThan(
            0,
            $crawler->filter('.badge:contains("Pending assignment")')->count(),
            'Expected at least one room with state "Pending assignment" to be displayed.'
        );
    }

    public function testNoRoomsFoundWithFilters()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/rooms');

        // Simuler la soumission du formulaire de filtre avec un critère qui ne correspond à aucune salle
        $form = $crawler->selectButton('Filter')->form([
            'filter_room[name]' => 'NonExistentRoom',
        ]);

        $crawler = $client->submit($form);

        // Vérifier que la requête a réussi
        $this->assertResponseIsSuccessful();

        // Vérifier qu'aucune salle n'est trouvée
        $this->assertSelectorTextContains(
            '.text-center.text-dark',
            'No match. Check spelling or create a new room.',
            'Expected message indicating no rooms found.'
        );
    }

    public function testDetailsPageDisplaysRoomInfo()
    {
        $client = static::createClient();

        // Ajouter une salle dans la base de données pour le test
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $room = new Room();
        $room->setName('TestRoom001');
        $room->setFloor(FloorEnum::GROUND);
        $room->setState(RoomStateEnum::OK);
        $room->setDescription('Test description for TestRoom001');
        $entityManager->persist($room);
        $entityManager->flush();

        // Utiliser le nom de la salle pour accéder à la page des détails
        $crawler = $client->request('GET', '/rooms/' . $room->getName());

        // Afficher le contenu de la réponse pour le débogage (peut être commenté ensuite)
        // echo $client->getResponse()->getContent();

        // Vérifier que la requête a réussi
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('h1', 'Room TestRoom001'); // Titre de la salle
        $this->assertSelectorTextContains('.card-title', 'Description'); // Titre de la carte de description
        $this->assertSelectorTextContains('.card-text.description', 'Test description for TestRoom001'); // Contenu de la description
        $this->assertSelectorTextContains('.card-text.floor', 'Floor: ground');
    }

    public function testDetailsPageRoomNotFound()
    {
        $client = static::createClient();

        // Accéder à la page des détails pour une salle inexistante
        $client->request('GET', '/rooms/NonExistentRoom');

        // Vérifier que la page renvoie un code HTTP 404
        $this->assertResponseStatusCodeSame(404, 'Expected 404 Not Found for non-existent room.');
    }

    public function testAddPageDisplaysForm()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/add');

        // Vérifier que la requête a réussi
        $this->assertResponseIsSuccessful();

        // Vérifier que le formulaire est affiché correctement
        $this->assertSelectorExists('form');
        $this->assertSelectorTextContains('button', 'Add Room');
    }

}
