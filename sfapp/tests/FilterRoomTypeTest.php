<?php

namespace App\Tests\Form;

use App\DataFixtures\AppFixtures;
use App\Entity\Room;
use App\Form\FilterRoomType;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FilterRoomTypeTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private \Symfony\Component\Form\FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer le client
        $client = static::createClient();
        // Récupérer l'EntityManager depuis le conteneur
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        // Purger la base de données
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        // Charger les fixtures
        $loader = new Loader();
        $loader->addFixture(new AppFixtures(static::getContainer()->get('security.password_hasher')));

        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($loader->getFixtures());

        // Vérifier que les fixtures sont chargées correctement
        $rooms = $this->entityManager->getRepository(Room::class)->findAll();
        $this->assertNotEmpty($rooms, 'Les fixtures n\'ont pas été chargées correctement.');

        // Récupérer le FormFactory depuis le conteneur
        $this->formFactory = static::getContainer()->get('form.factory');
    }

    public function testSubmitValidData()
    {
        // Mock du traducteur
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock->method('trans')->willReturnArgument(0);

        // Notre modèle (entité Room)
        $model = new Room();

        // Construction du form builder en utilisant le FormFactory du conteneur
        $form = $this->formFactory->create(FilterRoomType::class, $model, [
            'translator' => $translatorMock,
        ]);

        // Données soumises
        $formData = [
            'name'  => 'Salle 101',
            'floor' => FloorEnum::FIRST->value,
            'state' => RoomStateEnum::AT_RISK->value,
        ];

        // Soumission
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        // Vérifications
        $this->assertSame('Salle 101', $model->getName());
        $this->assertSame(FloorEnum::FIRST, $model->getFloor());
        $this->assertSame(RoomStateEnum::AT_RISK, $model->getState());
    }

    public function testSubmitEmptyData()
    {
        // Mock du traducteur
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock->method('trans')->willReturnArgument(0);

        // Notre modèle (entité Room)
        $model = new Room();

        // Construction du form builder en utilisant le FormFactory du conteneur
        $form = $this->formFactory->create(FilterRoomType::class, $model, [
            'translator' => $translatorMock,
        ]);

        $form->submit([]); // on simule des données vides

        $this->assertTrue($form->isSynchronized());

        $this->assertNull($model->getName());
        $this->assertNull($model->getFloor());
        $this->assertNull($model->getState());
    }

    public function testFloorChoices()
    {
        // Mock du traducteur
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock->method('trans')->willReturnArgument(0);

        // Notre modèle (entité Room)
        $model = new Room();

        // Construction du form builder en utilisant le FormFactory du conteneur
        $form = $this->formFactory->create(FilterRoomType::class, $model, [
            'translator' => $translatorMock,
        ]);

        $floorOptions = $form->get('floor')->getConfig()->getOption('choices');
        $values = [];
        foreach ($floorOptions as $label => $floorEnum) {
            $values[] = $floorEnum->value;
        }

        $this->assertContains(FloorEnum::GROUND->value, $values);
        $this->assertContains(FloorEnum::FIRST->value, $values);
        $this->assertContains(FloorEnum::SECOND->value, $values);
        $this->assertContains(FloorEnum::THIRD->value, $values);
    }
}
