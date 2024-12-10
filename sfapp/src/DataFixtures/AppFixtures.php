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

        // linked room, stable
        $room1 = new Room();
        $room1->setName("D001");
        $room1->setFloor(FloorEnum::GROUND);
        $room1->setNbHeaters(2);
        $room1->setNbWindows(3);
        $room1->setSurface(20);
        $room1->setSensorState(SensorStateEnum::LINKED);
        $room1->setCardinalDirection(CardinalEnum::EAST);
        $room1->setState(RoomStateEnum::STABLE);
        $manager->persist($room1);

        // linked room, critical problem
        $room2 = new Room();
        $room2->setName("D100");
        $room2->setFloor(FloorEnum::FIRST);
        $room2->setNbHeaters(3);
        $room2->setNbWindows(3);
        $room2->setSurface(30);
        $room2->setSensorState(SensorStateEnum::LINKED);
        $room2->setCardinalDirection(CardinalEnum::WEST);
        $room2->setState(RoomStateEnum::CRITICAL);
        $manager->persist($room2);

        // linked room, at risk
        $room3 = new Room();
        $room3->setName("D204");
        $room3->setFloor(FloorEnum::SECOND);
        $room3->setNbHeaters(4);
        $room3->setNbWindows(4);
        $room3->setSurface(30);
        $room3->setSensorState(SensorStateEnum::LINKED);
        $room3->setCardinalDirection(CardinalEnum::WEST);
        $room3->setState(RoomStateEnum::AT_RISK);
        $manager->persist($room3);

        // linked room, waiting for data
        $room4 = new Room();
        $room4->setName("D301");
        $room4->setFloor(FloorEnum::THIRD);
        $room4->setNbHeaters(4);
        $room4->setNbWindows(3);
        $room4->setSurface(40);
        $room4->setSensorState(SensorStateEnum::LINKED);
        $room4->setCardinalDirection(CardinalEnum::WEST);
        $room4->setState(RoomStateEnum::WAITING);
        $manager->persist($room4);

        // not linked room
        $room5 = new Room();
        $room5->setName("D302");
        $room5->setFloor(FloorEnum::THIRD);
        $room5->setNbHeaters(5);
        $room5->setNbWindows(4);
        $room5->setSurface(60);
        $room5->setSensorState(SensorStateEnum::NOT_LINKED);
        $room5->setCardinalDirection(CardinalEnum::SOUTH);
        $room5->setState(RoomStateEnum::NONE);
        $manager->persist($room5);

        // linked Acquisition System with room 1, stable
        $as1 = new AcquisitionSystem();
        $as1->setName("ESP-001");
        $as1->setState(SensorStateEnum::LINKED);
        $as1->setRoom($room1);
        $manager->persist($as1);

        // linked Acquisition System with room 2, critical problem
        $as2 = new AcquisitionSystem();
        $as2->setName("ESP-002");
        $as2->setState(SensorStateEnum::LINKED);
        $as2->setRoom($room2);
        $manager->persist($as2);

        // linked Acquisition System with room 3, at risk
        $as3 = new AcquisitionSystem();
        $as3->setName("ESP-003");
        $as3->setState(SensorStateEnum::LINKED);
        $as3->setRoom($room3);
        $manager->persist($as3);

        // linked Acquisition System with room 4, no data
        $as4 = new AcquisitionSystem();
        $as4->setName("ESP-004");
        $as4->setState(SensorStateEnum::LINKED);
        $as4->setRoom($room4);
        $manager->persist($as4);

        // not linked
        $as5 = new AcquisitionSystem();
        $as5->setName("ESP-005");
        $as5->setState(SensorStateEnum::NOT_LINKED);
        $manager->persist($as5);

        // manager user
        $user1 = new User();
        $user1->setUsername('manager');
        $hashedPassword = $this->passwordHasher->hashPassword($user1, 'manager');
        $user1->setPassword($hashedPassword);
        $user1->setRoles([UserRoleEnum::ROLE_MANAGER]);
        $manager->persist($user1);

        // technician user
        $user2 = new User();
        $user2->setUsername('technician');
        $hashedPassword = $this->passwordHasher->hashPassword($user2, 'technician');
        $user2->setPassword($hashedPassword);
        $user2->setRoles([UserRoleEnum::ROLE_TECHNICIAN]);
        $manager->persist($user2);

        $manager->flush();
    }
}
