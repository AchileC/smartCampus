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

        // Assert the page loaded successfully
        $this->assertResponseIsSuccessful();

        // Assert that the filter form is present on the page
        $this->assertSelectorExists('form[name="filter_room"]', 'Filter form should be present on the page.');

        // Add debug statement to check the HTML content
        // echo $client->getResponse()->getContent();

        // Simulate submitting the filter form with a room name
        $form = $crawler->selectButton('Search')->form([
            'filter_room[name]' => 'D001',
        ]);

        $crawler = $client->submit($form);

        // Assert that the response after submission is successful
        $this->assertResponseIsSuccessful();

        // Check if the expected room with the name 'D001' is present
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

        // Vérifier que la page s'est chargée avec succès
        $this->assertResponseIsSuccessful();

        // Vérifier que le formulaire de filtre est présent
        $this->assertSelectorExists('form[name="filter_room"]', 'Le formulaire de filtre devrait être présent sur la page.');

        // Simuler la soumission du formulaire avec le filtre de l'étage
        $form = $crawler->selectButton('Search')->form([
            'filter_room[floor]' => FloorEnum::GROUND->value,
        ]);

        $crawler = $client->submit($form);

        // Vérifier que la réponse après soumission est réussie
        $this->assertResponseIsSuccessful();

        // Vérifier qu'il y a au moins une salle au sol
        $this->assertGreaterThan(
            0,
            $crawler->filter('.card-text:contains("Floor: ground")')->count(),
            'Au moins une salle au sol devrait être affichée.'
        );
    }

    public function testFilterRoomsByState()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/rooms');

        // Vérifier que la page s'est chargée avec succès
        $this->assertResponseIsSuccessful();

        // Vérifier que le formulaire de filtre est présent
        $this->assertSelectorExists('form[name="filter_room"]', 'Le formulaire de filtre devrait être présent sur la page.');

        // Simuler la soumission du formulaire avec le filtre d'état
        $form = $crawler->selectButton('Search')->form([
            'filter_room[state]' => RoomStateEnum::PENDING_ASSIGNMENT->value,
        ]);

        $crawler = $client->submit($form);

        // Vérifier que la réponse après soumission est réussie
        $this->assertResponseIsSuccessful();

        // Vérifier qu'il y a au moins une salle avec l'état "Pending assignment"
        $this->assertGreaterThan(
            0,
            $crawler->filter('.badge:contains("Pending assignment")')->count(),
            'Au moins une salle avec l\'état "Pending assignment" devrait être affichée.'
        );
    }

    public function testNoRoomsFoundWithFilters()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/rooms');

        // Assert the page loaded successfully
        $this->assertResponseIsSuccessful();

        // Assert that the filter form is present
        $this->assertSelectorExists('form[name="filter_room"]', 'Filter form should be present on the page.');

        // Submit the filter form with a non-existent room name
        $form = $crawler->selectButton('Search')->form([
            'filter_room[name]' => 'NonExistentRoom',
        ]);

        $crawler = $client->submit($form);

        // Assert the response after submission is successful
        $this->assertResponseIsSuccessful();

        // Check if the message indicating no rooms found is displayed
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
