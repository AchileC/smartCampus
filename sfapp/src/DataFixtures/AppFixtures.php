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

        $action1 = new Action();
        $action1->setInfo(ActionInfoEnum::ASSIGNMENT);
        $action1->setState(ActionStateEnum::TO_DO);
        $action1->setCreatedAt(new \DateTime('2024-12-01 10:00:00'));
        $action1->setRoom($room1);

        $action2 = new Action();
        $action2->setInfo(ActionInfoEnum::UNASSIGNMENT);
        $action2->setState(ActionStateEnum::DOING);
        $action2->setCreatedAt(new \DateTime('2024-12-02 14:00:00'));
        $action2->setStartedAt(new \DateTime('2024-12-02 16:00:00'));
        $action2->setRoom($room2);

        $action3 = new Action();
        $action3->setInfo(ActionInfoEnum::ASSIGNMENT);
        $action3->setState(ActionStateEnum::DOING);
        $action3->setCreatedAt(new \DateTime('2024-12-03 09:30:00'));
        $action3->setStartedAt(new \DateTime('2024-12-5 14:00:00'));
        $action3->setRoom($room3);

        $action4 = new Action();
        $action4->setInfo(ActionInfoEnum::UNASSIGNMENT);
        $action4->setState(ActionStateEnum::TO_DO);
        $action4->setCreatedAt(new \DateTime('2024-12-04 11:15:00'));
        $action4->setRoom($room2);

        $action5 = new Action();
        $action5->setInfo(ActionInfoEnum::ASSIGNMENT);
        $action5->setState(ActionStateEnum::TO_DO);
        $action5->setCreatedAt(new \DateTime('2024-12-09 12:00:00'));
        $action5->setRoom($room1);

        $user1 = new User();
        $user1->setUsername('manager');
        $hashedPassword = $this->passwordHasher->hashPassword($user1, '1234');
        $user1->setPassword($hashedPassword);
        $user1->setRoles([UserRoleEnum::ROLE_MANAGER]);

        $user2 = new User();
        $user2->setUsername('technician');
        $hashedPassword = $this->passwordHasher->hashPassword($user2, '1234');
        $user2->setPassword($hashedPassword);
        $user2->setRoles([UserRoleEnum::ROLE_TECHNICIAN]);

        $manager->persist($room1);
        $manager->persist($room2);
        $manager->persist($room3);
        $manager->persist($room4);
        $manager->persist($as1);
        $manager->persist($as2);
        $manager->persist($action1);
        $manager->persist($action2);
        $manager->persist($action3);
        $manager->persist($action4);
        $manager->persist($action5);
        $manager->persist($user1);
        $manager->persist($user2);

        $manager->flush();
    }
}
