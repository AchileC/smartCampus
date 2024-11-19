<?php

namespace App\DataFixtures;

use App\Entity\AcquisitionSystem;
use App\Entity\Room;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $room1 = new Room();
        $room1->setName("D001");
        $room1->setFloor(FloorEnum::GROUND);
        $room1->setDescription("Salle en coin dans le premier étage");
        $room1->setState(RoomStateEnum::PROBLEM);

        $room2 = new Room();
        $room2->setName("D002");
        $room2->setFloor(FloorEnum::GROUND);
        $room2->setDescription("Salle en pause");
        $room2->setState(RoomStateEnum::CRITICAL);

        $room3 = new Room();
        $room3->setName("D204");
        $room3->setFloor(FloorEnum::SECOND);
        $room3->setDescription("Première salle en entrants");
        $room3->setState(RoomStateEnum::OK);

        $as1 = new AcquisitionSystem();
        $as1->setTemperature(20.5);
        $as1->setHumidity(40);
        $as1->setCo2(100);
        $as1->setRoom($room1);

        $room4 = new Room();
        $room4->setName('TestRoom');
        $room4->setState(RoomStateEnum::PENDING_ASSIGNMENT);
        $room4->setFloor(FloorEnum::SECOND);
        $room4->setDescription('Room for testing pending assignment state');

        // Persist entities to the database
        $manager->persist($room1);
        $manager->persist($room2);
        $manager->persist($room3);
        $manager->persist($room4);
        $manager->persist($as1);

        $manager->flush();
    }
}
