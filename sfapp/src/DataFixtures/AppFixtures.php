<?php

namespace App\DataFixtures;

use App\Entity\AcquisitionSystem;
use App\Entity\Room;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use App\Utils\CardinalEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $room1 = new Room();
        $room1->setName("D001");
        $room1->setFloor(FloorEnum::GROUND);
        $room1->setDescription("Not a chill room");
        $room1->setNbHeaters(2);
        $room1->setNbWindows(3);
        $room1->setSurface(20);
        $room1->setState(RoomStateEnum::STABLE);
        $room1->setSensorState(SensorStateEnum::LINKED);
        $room1->setCardinalDirection(CardinalEnum::EAST);

        $as1 = new AcquisitionSystem();
        $as1->setTemperature(19);
        $as1->setHumidity(45);
        $as1->setCo2(420);
        $as1->setRoom($room1);

        $room2 = new Room();
        $room2->setName("D002");
        $room2->setFloor(FloorEnum::GROUND);
        $room2->setDescription("Classroom without computer");
        $room2->setNbHeaters(3);
        $room2->setNbWindows(3);
        $room2->setSurface(30);
        $room2->setState(RoomStateEnum::CRITICAL);
        $room2->setSensorState(SensorStateEnum::LINKED);
        $room2->setCardinalDirection(CardinalEnum::WEST);

        $room3 = new Room();
        $room3->setName("D204");
        $room3->setFloor(FloorEnum::SECOND);
        $room3->setDescription("Room for sae autonomies");
        $room3->setNbHeaters(4);
        $room3->setNbWindows(3);
        $room3->setSurface(25);
        $room3->setState(RoomStateEnum::AT_RISK);
        $room3->setSensorState(SensorStateEnum::LINKED);
        $room3->setCardinalDirection(CardinalEnum::WEST);

        $room4 = new Room();
        $room4->setName('T001');
        $room4->setFloor(FloorEnum::FIRST);
        $room4->setDescription('Room for testing pending assignment state');
        $room4->setNbHeaters(1);
        $room4->setNbWindows(1);
        $room4->setSurface(1);
        $room4->setState(RoomStateEnum::WAITING);
        $room4->setSensorState(SensorStateEnum::ASSIGNMENT);
        $room4->setCardinalDirection(CardinalEnum::EAST);

        $manager->persist($room1);
        $manager->persist($room2);
        $manager->persist($room3);
        $manager->persist($room4);
        $manager->persist($as1);

        $manager->flush();
    }
}
