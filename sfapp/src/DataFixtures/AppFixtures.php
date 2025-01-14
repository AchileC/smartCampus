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
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
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
        $rooms = [
            ['nomsa' => 'ESP-004', 'localisation' => 'D205', 'dbname' => 'sae34bdk1eq1'],
            ['nomsa' => 'ESP-008', 'localisation' => 'D206', 'dbname' => 'sae34bdk1eq2'],
            ['nomsa' => 'ESP-006', 'localisation' => 'D207', 'dbname' => 'sae34bdk1eq3'],
            ['nomsa' => 'ESP-014', 'localisation' => 'D204', 'dbname' => 'sae34bdk2eq1'],
            ['nomsa' => 'ESP-012', 'localisation' => 'D203', 'dbname' => 'sae34bdk2eq2'],
            ['nomsa' => 'ESP-005', 'localisation' => 'D303', 'dbname' => 'sae34bdk2eq3'],
            ['nomsa' => 'ESP-011', 'localisation' => 'D304', 'dbname' => 'sae34bdl1eq1'],
            ['nomsa' => 'ESP-007', 'localisation' => 'C101', 'dbname' => 'sae34bdl1eq2'],
            ['nomsa' => 'ESP-024', 'localisation' => 'D109', 'dbname' => 'sae34bdl1eq3'],
            ['nomsa' => 'ESP-026', 'localisation' => 'Secretariat', 'dbname' => 'sae34bdl2eq1'],
            ['nomsa' => 'ESP-030', 'localisation' => 'D001', 'dbname' => 'sae34bdl2eq2'],
            ['nomsa' => 'ESP-028', 'localisation' => 'D002', 'dbname' => 'sae34bdl2eq3'],
            ['nomsa' => 'ESP-020', 'localisation' => 'D004', 'dbname' => 'sae34bdm1eq1'],
            ['nomsa' => 'ESP-021', 'localisation' => 'C004', 'dbname' => 'sae34bdm1eq2'],
            ['nomsa' => 'ESP-022', 'localisation' => 'C007', 'dbname' => 'sae34bdm1eq3'],
        ];

        $roomEntities = [];

        $cardinalDirections = [
            CardinalEnum::NORTH,
            CardinalEnum::EAST,
            CardinalEnum::SOUTH,
            CardinalEnum::WEST
        ];

        // First loop: Create and persist Room entities
        foreach ($rooms as $data) {
            $room = new Room();
            $room->setName($data['localisation']);

            // Setting floor dynamically based on room name
            if (str_starts_with($data['localisation'], 'D0') || str_starts_with($data['localisation'], 'C0')) {
                $room->setFloor(FloorEnum::GROUND);
            } elseif (str_starts_with($data['localisation'], 'D2') || str_starts_with($data['localisation'], 'C2')) {
                $room->setFloor(FloorEnum::SECOND);
            } elseif (str_starts_with($data['localisation'], 'D3') || str_starts_with($data['localisation'], 'C3')) {
                $room->setFloor(FloorEnum::THIRD); // Assuming C corresponds to the first floor
            } else {
                $room->setFloor(FloorEnum::FIRST); // Default for unknown patterns
            }

            $room->setNbHeaters(2); // Default value, adjust as needed
            $room->setNbWindows(3); // Default value, adjust as needed
            $room->setSurface(20); // Default value, adjust as needed
            $room->setSensorState(SensorStateEnum::LINKED); // Default value, adjust as needed
            $room->setCardinalDirection($cardinalDirections[array_rand($cardinalDirections)]);
            $room->setState(RoomStateEnum::NO_DATA); // Default value, adjust as needed

            $manager->persist($room);
            $roomEntities[$data['nomsa']] = $room;
        }

        // Second loop: Create and persist AcquisitionSystem entities
        foreach ($rooms as $data) {
            $acquisitionSystem = new AcquisitionSystem();
            $acquisitionSystem->setName($data['nomsa']);
            $acquisitionSystem->setState(SensorStateEnum::LINKED); // Default value
            $acquisitionSystem->setDbName($data['dbname']); // Définir dbName

            // Correctly associate the AcquisitionSystem with its corresponding Room
            $room = $roomEntities[$data['nomsa']];
            $acquisitionSystem->setRoom($room);
            $room->setAcquisitionSystem($acquisitionSystem);

            $manager->persist($acquisitionSystem);
        }

        // Create and persist User entities

        // Manager user
        $user1 = new User();
        $user1->setUsername('manager');
        $hashedPassword = $this->passwordHasher->hashPassword($user1, 'manager');
        $user1->setPassword($hashedPassword);
        $user1->setRoles([UserRoleEnum::ROLE_MANAGER]);
        $manager->persist($user1);

        // Technician user
        $user2 = new User();
        $user2->setUsername('technician');
        $hashedPassword = $this->passwordHasher->hashPassword($user2, 'technician');
        $user2->setPassword($hashedPassword);
        $user2->setRoles([UserRoleEnum::ROLE_TECHNICIAN]);
        $manager->persist($user2);

        $manager->flush();
    }
}
