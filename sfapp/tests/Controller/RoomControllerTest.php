<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
/**
 * @class RoomControllerTest
 * @brief Test suite for the RoomController.
 *
 * This class contains unit tests to verify:
 * - The presence and correctness of form fields and labels in the room list.
 * - Role-based visibility of the "Add Room" button for managers.
 */
class RoomControllerTest extends WebTestCase
{
    /**
     * @brief Verifies the presence and correctness of form fields and labels on the room list page.
     *
     * @test This test checks:
     * - If the filter form contains the expected fields: name, floor, and state.
     * - If the filter form fields have correct labels.
     * - If the reset and search buttons are present and labeled correctly.
     *
     * @return void
     */


    public function testRoomListFormFields(): void
    {
        // Create a client to simulate a browser
        $client = static::createClient();

        // Set the HTTP_ACCEPT_LANGUAGE header to simulate the English language
        $client->setServerParameter('HTTP_ACCEPT_LANGUAGE', 'en');

        // Make a GET request to the '/rooms' page
        $crawler = $client->request('GET', '/rooms');

        // Assert that the response is successful (status code 200)
        $this->assertResponseIsSuccessful();

        // Verify the presence of form fields
        $this->assertSelectorExists('input#filter_room_name', 'The name input field is missing.');
        $this->assertSelectorExists('select#filter_room_floor', 'The floor select field is missing.');
        $this->assertSelectorExists('select#filter_room_state', 'The state select field is missing.');

        // Verify the labels for the form fields
        $this->assertSelectorTextContains('label[for="filter_room_name"]', 'Name');
        $this->assertSelectorTextContains('label[for="filter_room_floor"]', 'Floor');
        $this->assertSelectorTextContains('label[for="filter_room_state"]', 'State');

        // Verify the presence and correctness of form buttons
        $this->assertSelectorExists('button#filter_room_reset', 'The reset button is missing.');
        $this->assertSelectorTextContains('button#filter_room_reset', 'Reset');

        $this->assertSelectorExists('button#filter_room_filter', 'The search button is missing.');
        $this->assertSelectorTextContains('button#filter_room_filter', 'Search');
    }

    /**
     * @brief Ensures the "Add Room", "Edit", and "Delete" buttons are visible only for users with the "manager" role.
     *
     * @test This test checks:
     * - That unauthenticated users do not see the "Add Room", "Edit", and "Delete" buttons on the '/rooms' page.
     * - That managers see these buttons after logging in.
     * @return void
     */
    public function testRoomButtonsVisibleForManagersOnly(): void
    {
        $client = static::createClient();

        // Test as an unauthenticated user first
        $client->request('GET', '/rooms');

        // Assert buttons not visible
        $this->assertSelectorNotExists('a.btn-success');
        $this->assertSelectorNotExists('a.btn-outline-secondary');
        $this->assertSelectorNotExists('button.btn-outline-danger');


        $userRepository = static::getContainer()->get(UserRepository::class);

        $testUser = $userRepository->findOneByUsername('manager');

        $client->loginUser($testUser);


        // Get the correct rooms URL using the router

        $client->request('GET', '/rooms');

        // Assert buttons are visible
        $this->assertSelectorExists('a.btn-success', 'Add Room button not found - user might not be properly authenticated');
        $this->assertSelectorExists('a.btn-outline-secondary', 'Edit button not found - user might not be properly authenticated');
        $this->assertSelectorExists('button.btn-outline-danger', 'Delete button not found - user might not be properly authenticated');
    }
}