<?php

namespace App\DataFixtures;

use App\Entity\Room;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $room1 = new Room();
        $room1->setName("D001");
        $room1->setFloor(FloorEnum::GROUND);
        $room1->setState(RoomStateEnum::OK);

        $room2 = new Room();
        $room2->setName("D002");
        $room2->setFloor(FloorEnum::GROUND);
        $room2->setState(RoomStateEnum::OK);

        $room3 = new Room();
        $room3->setName("D204");
        $room3->setFloor(FloorEnum::SECOND);
        $room3->setState(RoomStateEnum::OK);




        $manager->persist($room1);
        $manager->persist($room2);
        $manager->persist($room3);
        $manager->flush();
    }
}
