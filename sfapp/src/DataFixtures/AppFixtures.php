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
        $room = new Room();
        $room->setName('D302');
        $room->setFloor(3);
        $room->setNbWindows(2);
        $room->setNbHeaters(1);
        $room->setSurface(30);
        $room->setCardinalDirection('north');
        $room->setSensorState('linked');
        $room->setState('stable');

        $manager->persist($room);
        $manager->flush();


        $room2 = new Room();
        $room2->setName('D303');
        $room2->setFloor(3);
        $room2->setNbWindows(3);
        $room2->setNbHeaters(2);
        $room2->setSurface(25);
        $room2->setCardinalDirection('north');
        $room2->setSensorState('linked');
        $room2->setState('stable');

        $manager->persist($room2);
        $manager->flush();
    }
}
