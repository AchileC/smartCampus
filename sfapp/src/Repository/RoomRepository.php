<?php
//RoomRepository.php
namespace App\Repository;

use App\Entity\Room;
use App\Entity\Action;
use App\Utils\ActionInfoEnum;
use App\Utils\ActionStateEnum;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Room>
 */
class RoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Room::class);
    }

    public function findByCriteria(array $criteria): array
    {
        $queryBuilder = $this->createQueryBuilder('r');

        if (isset($criteria['name']) && !empty($criteria['name'])) {
            $queryBuilder->andWhere('r.name LIKE :name')
                ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        if (isset($criteria['floor'])) {
            $queryBuilder->andWhere('r.floor = :floor')
                ->setParameter('floor', $criteria['floor']);
        }

        if (isset($criteria['state'])) {
            $queryBuilder->andWhere('r.state = :state')
                ->setParameter('state', $criteria['state']);
        }

        if (isset($criteria['sensorStatus']) && is_array($criteria['sensorStatus'])) {
            $queryBuilder->andWhere('r.sensorState IN (:sensorStatus)')
                ->setParameter('sensorStatus', $criteria['sensorStatus']);
        }

        return $queryBuilder->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function loadSensorData(Room $room): array
    {
        // Vérifie s'il y a un système d'acquisition associé
        if (!$room->getAcquisitionSystem()) {
            return []; // Aucun système d'acquisition, donc pas de données à charger
        }

        // Chemin du fichier JSON basé sur le nom de la salle
        $filePath = __DIR__ . '/../../assets/json/' . $room->getName() . '.json';

        if (!file_exists($filePath)) {
            return []; // Fichier non trouvé
        }

        // Lecture et décodage du JSON
        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON format in ' . $filePath);
        }

        return $data;
    }

    /**
     * Updates the acquisition system data from a JSON file
     *
     * The JSON file should be located in assets/json/ directory
     * and named after the room (e.g., "room1.json")
     *
     * Expected JSON format:
     * [
     *   {"nom": "temp", "valeur": "23.5"},
     *   {"nom": "hum", "valeur": "45"},
     *   {"nom": "co2", "valeur": "800"}
     * ]
     */
    public function updateAcquisitionSystemFromJson(Room $room): void
    {
        // Check if room has an acquisition system
        $acquisitionSystem = $room->getAcquisitionSystem();

        if (!$acquisitionSystem) {
            return;
        }

        // Load data from JSON file
        $data = $this->loadSensorData($room);

        if (empty($data)) {
            return;
        }

        // Update sensor values from JSON data
        foreach ($data as $entry) {
            if ($entry['nom'] === 'temp') {
                $acquisitionSystem->setTemperature((float) $entry['valeur']);
            } elseif ($entry['nom'] === 'hum') {
                $acquisitionSystem->setHumidity((int) $entry['valeur']);
            } elseif ($entry['nom'] === 'co2') {
                $acquisitionSystem->setCo2((int) $entry['valeur']);
            }
        }

        // Persist changes to database
        $entityManager = $this->getEntityManager();
        $entityManager->persist($acquisitionSystem);
        $entityManager->flush();
    }

    /**
     * Updates the room's state based on sensor data (temperature, humidity, CO2)
     *
     * Room State Levels (in order of priority):
     * - CRITICAL (highest priority)
     * - AT_RISK
     * - STABLE
     * - NONE (when no acquisition system)
     *
     * Heating Period: November to April (months 11, 12, 1, 2, 3, 4)
     * Non-Heating Period: May to October (months 5, 6, 7, 8, 9, 10)
     */
    public function updateRoomState(Room $room): void
    {
        // Check if room has an acquisition system
        $acquisitionSystem = $room->getAcquisitionSystem();

        if (!$acquisitionSystem) {
            $room->setState(RoomStateEnum::NONE);
            return;
        }

        // Get current sensor data
        $temperature = $acquisitionSystem->getTemperature();
        $humidity = $acquisitionSystem->getHumidity();
        $co2 = $acquisitionSystem->getCo2();

        // Determine if we're in heating period (November to April)
        $currentMonth = (int)(new \DateTime())->format('m');
        $isHeatingPeriod = $currentMonth >= 11 || $currentMonth <= 4;

        $state = RoomStateEnum::STABLE; // Default state
        $sensorState = $room->getSensorState();

        if ($temperature == null && $humidity == null && $co2 == null) {
            $state = RoomStateEnum::WAITING;
        }

        // Temperature evaluation
        // Heating period (Nov-Apr): optimal 19-21°C, warning 17-19°C and 21-23°C, critical <17°C or >23°C
        // Non-heating (May-Oct): optimal 24-28°C, warning 22-24°C and 28-30°C, critical <22°C or >30°C
        // Évaluation de la température
        if ($temperature !== null) {
            if ($isHeatingPeriod) {
                if ($temperature < 17 || $temperature > 23) {
                    $state = RoomStateEnum::CRITICAL;
                } elseif ($temperature < 19 || $temperature > 21) {
                    $state = $state !== RoomStateEnum::CRITICAL ? RoomStateEnum::AT_RISK : $state;
                }
            } else {
                if ($temperature < 22 || $temperature > 30) {
                    $state = RoomStateEnum::CRITICAL;
                } elseif ($temperature < 24 || $temperature > 28) {
                    $state = $state !== RoomStateEnum::CRITICAL ? RoomStateEnum::AT_RISK : $state;
                }
            }
        }

        // CO2 evaluation (in ppm - parts per million)
        // Optimal: 440-1000 ppm
        // Warning: 1000-1500 ppm
        // Critical: <440 ppm or >1500 ppm
        if ($co2 !== null) {
            if ($co2 < 440 || $co2 > 2000) {
                $state = RoomStateEnum::CRITICAL;
                $sensorState = SensorStateEnum::NOT_WORKING;

            } elseif ($co2 > 1500) {
                $state = RoomStateEnum::CRITICAL;
            } elseif ($co2 > 1000) {
                $state = $state !== RoomStateEnum::CRITICAL ? RoomStateEnum::AT_RISK : $state;
            }
        }

        // Humidity evaluation (in percentage)
        // Optimal: 30-60%
        // Warning: 20-30% or 60-70%
        // Critical: <20% or >70%
        if ($humidity !== null) {
            if ($humidity < 20) {
                $state = RoomStateEnum::CRITICAL;
            } elseif ($humidity < 30 || ($humidity > 60 && $humidity <= 70)) {
                $state = $state !== RoomStateEnum::CRITICAL ? RoomStateEnum::AT_RISK : $state;
            } elseif ($humidity > 70) {
                $state = RoomStateEnum::CRITICAL;
            }
        }

        if ($sensorState === SensorStateEnum::NOT_WORKING) {
            $this->createTaskForTechnician($room);
        }

        // Update room state and persist changes
        $room->setSensorState($sensorState);
        $acquisitionSystem->setState($sensorState);
        $room->setState($state);
        $this->getEntityManager()->persist($room);
        $this->getEntityManager()->flush();
    }

    private function createTaskForTechnician(Room $room): void
    {
        $entityManager = $this->getEntityManager();

        $existingTask = $entityManager->getRepository(Action::class)->findOneBy([
            'room' => $room,
            'info' => ActionInfoEnum::MAINTENANCE, // Vérifie uniquement les tâches de maintenance
            'state' => ActionStateEnum::TO_DO, // Vérifie les tâches qui ne sont pas encore terminées
        ]);

        if ($existingTask) {
            return; // Une tâche existe déjà, donc on ne fait rien
        }

        // Crée une nouvelle tâche si aucune n'existe
        $action = new Action();
        $action->setRoom($room);
        $action->setInfo(ActionInfoEnum::MAINTENANCE); // Type d'action spécifique
        $action->setState(ActionStateEnum::TO_DO);
        $action->setCreatedAt(new \DateTime());

        $entityManager->persist($action);
        $entityManager->flush();
    }


    public function countByState(string $state): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)') // Sélectionne uniquement le nombre d'ID
            ->where('a.state = :state') // Ajoute un filtre par état
            ->setParameter('state', $state) // Définit la valeur du paramètre
            ->getQuery()
            ->getSingleScalarResult(); // Récupère le résultat unique (le nombre)
    }



    /**
     * Récupère les salles sans système d'acquisition lié.
     *
     * @return Room[]
     */
    public function findRoomsWithoutAS(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.acquisitionSystem', 'acq') // Changement de l'alias de 'as' à 'acq'
            ->where('acq.id IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les salles avec un système d'acquisition lié.
     *
     * @return Room[]
     */
    public function findRoomsWithAS(): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.acquisitionSystem', 'acq') // Changement de l'alias de 'as' à 'acq'
            ->getQuery()
            ->getResult();
    }
}
