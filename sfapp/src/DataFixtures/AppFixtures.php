<?php

namespace App\DataFixtures;

use App\Entity\AcquisitionSystem;
use App\Entity\Room;
use App\Entity\Action;
use App\Entity\User;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use App\Utils\CardinalEnum;
use App\Utils\ActionStateEnum;
use App\Utils\ActionInfoEnum;
use App\Utils\UserRoleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    public function load(ObjectManager $manager): void
    {
        $room1 = new Room();
        $room1->setName("D001");
        $room1->setFloor(FloorEnum::GROUND);
        $room1->setNbHeaters(2);
        $room1->setNbWindows(3);
        $room1->setSurface(20);
        $room1->setState(RoomStateEnum::STABLE);
        $room1->setSensorState(SensorStateEnum::LINKED);
        $room1->setCardinalDirection(CardinalEnum::EAST);

        $room2 = new Room();
        $room2->setName("D100");
        $room2->setFloor(FloorEnum::GROUND);
        $room2->setNbHeaters(3);
        $room2->setNbWindows(3);
        $room2->setSurface(30);
        $room2->setState(RoomStateEnum::CRITICAL);
        $room2->setSensorState(SensorStateEnum::LINKED);
        $room2->setCardinalDirection(CardinalEnum::WEST);

        $room3 = new Room();
        $room3->setName("D302");
        $room3->setFloor(FloorEnum::SECOND);
        $room3->setNbHeaters(4);
        $room3->setNbWindows(3);
        $room3->setSurface(25);
        $room3->setState(RoomStateEnum::AT_RISK);
        $room3->setSensorState(SensorStateEnum::LINKED);
        $room3->setCardinalDirection(CardinalEnum::WEST);

        $room4 = new Room();
        $room4->setName('T001');
        $room4->setFloor(FloorEnum::FIRST);
        $room4->setNbHeaters(1);
        $room4->setNbWindows(1);
        $room4->setSurface(1);
        $room4->setState(RoomStateEnum::WAITING);
        $room4->setSensorState(SensorStateEnum::ASSIGNMENT);
        $room4->setCardinalDirection(CardinalEnum::EAST);

        $as1 = new AcquisitionSystem();
        $as1->setName("ESP-001");
        $as1->setState(SensorStateEnum::NOT_LINKED);
        $as1->setRoom($room1);

        $as2 = new AcquisitionSystem();
        $as2->setName("ESP-002");
        $as2->setState(SensorStateEnum::NOT_LINKED);
        $as2->setRoom($room2);

        $as3 = new AcquisitionSystem();
        $as3->setName("ESP-003");
        $as3->setState(SensorStateEnum::NOT_LINKED);
        $as3->setRoom($room3);

        $user1 = new User();
        $user1->setUsername('test');
        $hashedPassword = $this->passwordHasher->hashPassword($user1, '1234');
        $user1->setPassword($hashedPassword);
        $user1->setRoles([UserRoleEnum::ROLE_MANAGER]);

        $manager->persist($room1);
        $manager->persist($room2);
        $manager->persist($room3);
        $manager->persist($room4);
        $manager->persist($as1);
        $manager->persist($as2);
        $manager->persist($user1);

        $manager->flush();
    }
}
