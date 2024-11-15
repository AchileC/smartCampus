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

        // Accéder à la page des détails de la salle
        $crawler = $client->request('GET', '/rooms/TestRoom001');

        // Vérifier que la requête a réussi
        $this->assertResponseIsSuccessful();

        // Vérifier que les informations correctes sont affichées
        $this->assertSelectorTextContains('h1', 'Room TestRoom001');
        $this->assertSelectorTextContains('.card-title', 'Description');
        $this->assertSelectorTextContains('.card-text', 'Test description for TestRoom001');
        $this->assertSelectorTextContains('.card-text', 'Floor: ground');
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

    public function testSubmitValidAddForm()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/add');

        // Récupérer le formulaire et remplir les champs
        $form = $crawler->selectButton('Add Room')->form([
            'add_room[name]' => 'NewRoom001',
            'add_room[floor]' => FloorEnum::GROUND,
            'add_room[description]' => 'Description for NewRoom001',
        ]);

        // Soumettre le formulaire
        $client->submit($form);

        // Vérifier la redirection après la soumission réussie
        $this->assertResponseRedirects('/rooms');

        // Suivre la redirection
        $client->followRedirect();

        // Vérifier que la nouvelle salle est affichée sur la page des salles
        $this->assertSelectorTextContains('.card-title', 'NewRoom001');
    }

    public function testSubmitInvalidAddForm()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/add');

        // Soumettre un formulaire invalide (nom de salle vide)
        $form = $crawler->selectButton('Add Room')->form([
            'add_room[name]' => '',
            'add_room[floor]' => FloorEnum::GROUND,
            'add_room[description]' => 'Description for room without a name',
        ]);

        $crawler = $client->submit($form);

        // Vérifier que la réponse est réussie mais le formulaire n'est pas validé
        $this->assertResponseIsSuccessful();

        // Vérifier qu'un message d'erreur est affiché concernant le nom manquant
        $this->assertSelectorTextContains('.invalid-feedback', 'Room name is required.');
    }

    public function testDeleteRoomSuccessfully()
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        // Créer une salle à supprimer
        $room = new Room();
        $room->setName('DeleteTestRoom');
        $room->setFloor(\App\Utils\FloorEnum::GROUND);
        $room->setState(\App\Utils\RoomStateEnum::OK);
        $room->setDescription('Room to be deleted');
        $entityManager->persist($room);
        $entityManager->flush();

        // Accéder à la page de suppression de la salle
        $crawler = $client->request('GET', '/rooms');

        // Récupérer le formulaire de suppression de la salle avec le nom 'DeleteTestRoom'
        $deleteForm = $crawler->selectButton('Delete')->form([
            '_token' => $crawler->filter('input[name="_token"]')->attr('value')
        ]);

        // Soumettre le formulaire
        $client->submit($deleteForm);

        // Vérifier la redirection après suppression
        $this->assertResponseRedirects('/rooms');

        // Suivre la redirection
        $client->followRedirect();

        // Vérifier que la salle n'est plus présente dans la liste
        $this->assertSelectorNotExists('.card-title:contains("DeleteTestRoom")');

        // Vérifier directement dans la base de données que la salle est supprimée
        $deletedRoom = $entityManager->getRepository(Room::class)->findOneBy(['name' => 'DeleteTestRoom']);
        $this->assertNull($deletedRoom, 'Expected room to be deleted from the database.');
    }

    public function testDeleteRoomWithInvalidCsrfToken()
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        // Créer une salle à supprimer
        $room = new Room();
        $room->setName('InvalidTokenRoom');
        $room->setFloor(\App\Utils\FloorEnum::GROUND);
        $room->setState(\App\Utils\RoomStateEnum::OK);
        $room->setDescription('Room to test CSRF token');
        $entityManager->persist($room);
        $entityManager->flush();

        // Accéder à la page des salles
        $crawler = $client->request('GET', '/rooms');

        // Récupérer le formulaire de suppression mais modifier le token CSRF
        $deleteForm = $crawler->selectButton('Delete')->form([
            '_token' => 'invalid_csrf_token_value'
        ]);

        // Soumettre le formulaire avec un mauvais token CSRF
        $client->submit($deleteForm);

        // Vérifier qu'une erreur d'accès est renvoyée (403)
        $this->assertResponseStatusCodeSame(403, 'Expected a 403 Forbidden response due to invalid CSRF token.');
    }

    public function testDeleteNonExistentRoom()
    {
        $client = static::createClient();

        // Tenter de supprimer une salle qui n'existe pas
        $client->request('POST', '/rooms/NonExistentRoom/delete', [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('delete_room')->getValue()
        ]);

        // Vérifier que la page renvoie un code HTTP 404
        $this->assertResponseStatusCodeSame(404, 'Expected 404 Not Found for non-existent room.');
    }

    public function testUpdatePageDisplaysCurrentRoomInfo()
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        // Créer une salle à mettre à jour
        $room = new Room();
        $room->setName('UpdateTestRoom');
        $room->setFloor(FloorEnum::FIRST);
        $room->setState(RoomStateEnum::OK);
        $room->setDescription('Description before update');
        $entityManager->persist($room);
        $entityManager->flush();

        // Accéder à la page de mise à jour de la salle
        $crawler = $client->request('GET', '/rooms/UpdateTestRoom/update');

        // Vérifier que la requête a réussi
        $this->assertResponseIsSuccessful();

        // Vérifier que les informations actuelles de la salle sont affichées
        $this->assertSelectorExists('input[value="UpdateTestRoom"]');
        $this->assertSelectorTextContains('textarea', 'Description before update');
        $this->assertSelectorExists('select option[selected][value="first"]');
    }

    public function testSubmitValidUpdateForm()
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        // Créer une salle à mettre à jour
        $room = new Room();
        $room->setName('ValidUpdateRoom');
        $room->setFloor(FloorEnum::SECOND);
        $room->setState(RoomStateEnum::OK);
        $room->setDescription('Initial description');
        $entityManager->persist($room);
        $entityManager->flush();

        // Accéder à la page de mise à jour
        $crawler = $client->request('GET', '/rooms/ValidUpdateRoom/update');

        // Remplir le formulaire avec de nouvelles données
        $form = $crawler->selectButton('Update Room')->form([
            'add_room[name]' => 'UpdatedRoomName',
            'add_room[floor]' => FloorEnum::GROUND,
            'add_room[description]' => 'Updated description for the room',
        ]);

        // Soumettre le formulaire
        $client->submit($form);

        // Vérifier la redirection après la soumission réussie
        $this->assertResponseRedirects('/rooms');

        // Suivre la redirection
        $client->followRedirect();

        // Vérifier que la nouvelle salle est affichée avec les données mises à jour
        $this->assertSelectorTextContains('.card-title', 'UpdatedRoomName');

        // Vérifier directement dans la base de données que la salle a bien été mise à jour
        $updatedRoom = $entityManager->getRepository(Room::class)->findOneBy(['name' => 'UpdatedRoomName']);
        $this->assertNotNull($updatedRoom, 'Expected room to be updated in the database.');
        $this->assertSame('Updated description for the room', $updatedRoom->getDescription());
        $this->assertSame(FloorEnum::GROUND, $updatedRoom->getFloor());
    }

    public function testSubmitInvalidUpdateForm()
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        // Créer une salle à mettre à jour
        $room = new Room();
        $room->setName('InvalidUpdateRoom');
        $room->setFloor(FloorEnum::SECOND);
        $room->setState(RoomStateEnum::OK);
        $room->setDescription('Initial description for invalid update');
        $entityManager->persist($room);
        $entityManager->flush();

        // Accéder à la page de mise à jour de la salle
        $crawler = $client->request('GET', '/rooms/InvalidUpdateRoom/update');

        // Remplir le formulaire avec des données invalides (nom vide)
        $form = $crawler->selectButton('Update Room')->form([
            'add_room[name]' => '',
            'add_room[floor]' => FloorEnum::GROUND,
            'add_room[description]' => 'Description with empty name',
        ]);

        // Soumettre le formulaire
        $crawler = $client->submit($form);

        // Vérifier que la requête est réussie mais le formulaire n'est pas validé
        $this->assertResponseIsSuccessful();

        // Vérifier qu'un message d'erreur est affiché concernant le nom manquant
        $this->assertSelectorTextContains('.invalid-feedback', 'Room name is required.');
    }

    public function testUpdateNonExistentRoom()
    {
        $client = static::createClient();

        // Tenter de mettre à jour une salle qui n'existe pas
        $client->request('GET', '/rooms/NonExistentRoom/update');

        // Vérifier que la page renvoie un code HTTP 404
        $this->assertResponseStatusCodeSame(404, 'Expected 404 Not Found for non-existent room.');
    }

    public function testRequestInstallationForExistingRoom()
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        // Créer une salle pour demander l'installation
        $room = new Room();
        $room->setName('InstallationTestRoom');
        $room->setFloor(FloorEnum::FIRST);
        $room->setState(RoomStateEnum::NOT_LINKED);
        $room->setDescription('Room to request installation of acquisition system');
        $entityManager->persist($room);
        $entityManager->flush();

        // Soumettre la demande d'installation
        $client->request('POST', '/rooms/InstallationTestRoom/request-assignment', [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('delete_room')->getValue()
        ]);

        // Vérifier la redirection après la demande
        $this->assertResponseRedirects('/rooms');

        // Suivre la redirection
        $client->followRedirect();

        // Vérifier que la salle est maintenant dans l'état "PENDING_ASSIGNMENT"
        $updatedRoom = $entityManager->getRepository(Room::class)->findOneBy(['name' => 'InstallationTestRoom']);
        $this->assertNotNull($updatedRoom, 'Expected room to exist in the database.');
        $this->assertSame(RoomStateEnum::PENDING_ASSIGNMENT, $updatedRoom->getState(), 'Expected room to be in state PENDING_ASSIGNMENT.');
    }

    public function testRequestInstallationForNonExistentRoom()
    {
        $client = static::createClient();

        // Tenter de demander l'installation pour une salle qui n'existe pas
        $client->request('POST', '/rooms/NonExistentRoom/request-assignment', [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('delete_room')->getValue()
        ]);

        // Vérifier que la page renvoie un code HTTP 404
        $this->assertResponseStatusCodeSame(404, 'Expected 404 Not Found for non-existent room.');
    }

    public function testCancelInstallationPendingAssignment()
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        // Créer une salle avec un état PENDING_ASSIGNMENT
        $room = new Room();
        $room->setName('CancelAssignmentTestRoom');
        $room->setFloor(FloorEnum::FIRST);
        $room->setState(RoomStateEnum::PENDING_ASSIGNMENT);
        $room->setDescription('Room to cancel assignment request');
        $entityManager->persist($room);
        $entityManager->flush();

        // Annuler la demande d'installation
        $client->request('POST', '/rooms/CancelAssignmentTestRoom/cancel-installation', [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('delete_room')->getValue()
        ]);

        // Vérifier la redirection après l'annulation
        $this->assertResponseRedirects('/rooms');

        // Suivre la redirection
        $client->followRedirect();

        // Vérifier que l'état de la salle est passé à "NOT_LINKED"
        $updatedRoom = $entityManager->getRepository(Room::class)->findOneBy(['name' => 'CancelAssignmentTestRoom']);
        $this->assertNotNull($updatedRoom, 'Expected room to exist in the database.');
        $this->assertSame(RoomStateEnum::NOT_LINKED, $updatedRoom->getState(), 'Expected room state to be NOT_LINKED after canceling the assignment.');
    }

    public function testCancelInstallationPendingUnassignment()
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        // Créer une salle avec un état PENDING_UNASSIGNMENT et un état précédent défini
        $room = new Room();
        $room->setName('CancelUnassignmentTestRoom');
        $room->setFloor(FloorEnum::SECOND);
        $room->setState(RoomStateEnum::PENDING_UNASSIGNMENT);
        $room->setDescription('Room to cancel unassignment request');
        $room->setPreviousState(RoomStateEnum::OK);
        $entityManager->persist($room);
        $entityManager->flush();

        // Annuler la demande de désassignation
        $client->request('POST', '/rooms/CancelUnassignmentTestRoom/cancel-installation', [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('delete_room')->getValue()
        ]);

        // Vérifier la redirection après l'annulation
        $this->assertResponseRedirects('/rooms');

        // Suivre la redirection
        $client->followRedirect();

        // Vérifier que l'état de la salle est revenu à son état précédent (OK)
        $updatedRoom = $entityManager->getRepository(Room::class)->findOneBy(['name' => 'CancelUnassignmentTestRoom']);
        $this->assertNotNull($updatedRoom, 'Expected room to exist in the database.');
        $this->assertSame(RoomStateEnum::OK, $updatedRoom->getState(), 'Expected room state to be restored to previous state (OK) after canceling the unassignment.');
    }

    public function testCancelInstallationForNonExistentRoom()
    {
        $client = static::createClient();

        // Tenter d'annuler une demande d'installation pour une salle qui n'existe pas
        $client->request('POST', '/rooms/NonExistentRoom/cancel-installation', [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('delete_room')->getValue()
        ]);

        // Vérifier que la page renvoie un code HTTP 404
        $this->assertResponseStatusCodeSame(404, 'Expected 404 Not Found for non-existent room.');
    }

    public function testRequestUnassignmentForExistingRoom()
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        // Créer une salle pour demander la désassignation
        $room = new Room();
        $room->setName('UnassignmentTestRoom');
        $room->setFloor(FloorEnum::FIRST);
        $room->setState(RoomStateEnum::OK);
        $room->setDescription('Room to request unassignment of acquisition system');
        $entityManager->persist($room);
        $entityManager->flush();

        // Soumettre la demande de désassignation
        $client->request('POST', '/rooms/UnassignmentTestRoom/request-unassignment', [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('delete_room')->getValue()
        ]);

        // Vérifier la redirection après la demande
        $this->assertResponseRedirects('/rooms');

        // Suivre la redirection
        $client->followRedirect();

        // Vérifier que la salle est maintenant dans l'état "PENDING_UNASSIGNMENT" et que l'état précédent est "OK"
        $updatedRoom = $entityManager->getRepository(Room::class)->findOneBy(['name' => 'UnassignmentTestRoom']);
        $this->assertNotNull($updatedRoom, 'Expected room to exist in the database.');
        $this->assertSame(RoomStateEnum::PENDING_UNASSIGNMENT, $updatedRoom->getState(), 'Expected room state to be PENDING_UNASSIGNMENT after request.');
        $this->assertSame(RoomStateEnum::OK, $updatedRoom->getPreviousState(), 'Expected previous state of room to be OK.');
    }

    public function testRequestUnassignmentForNonExistentRoom()
    {
        $client = static::createClient();

        // Tenter de demander la désassignation pour une salle qui n'existe pas
        $client->request('POST', '/rooms/NonExistentRoom/request-unassignment', [
            '_token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('delete_room')->getValue()
        ]);

        // Vérifier que la page renvoie un code HTTP 404
        $this->assertResponseStatusCodeSame(404, 'Expected 404 Not Found for non-existent room.');
    }


}
