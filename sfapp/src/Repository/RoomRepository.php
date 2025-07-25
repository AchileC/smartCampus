<?php
//RoomRepository.php
namespace App\Repository;

use App\Entity\Room;
use App\Utils\RoomStateEnum;
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

    //    /**
    //     * @return Room[] Returns an array of Room objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Room
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }



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





    public function updateAcquisitionSystemFromJson(Room $room): void
    {
        // Vérifie s'il y a un système d'acquisition associé
        $acquisitionSystem = $room->getAcquisitionSystem();

        if (!$acquisitionSystem) {
            return; // Aucun système d'acquisition, donc pas de mise à jour possible
        }

        // Charge les données du fichier JSON
        $data = $this->loadSensorData($room);

        if (empty($data)) {
            return; // Aucun fichier ou aucune donnée disponible
        }

        // Met à jour les valeurs dans l'entité AcquisitionSystem
        foreach ($data as $entry) {
            if ($entry['nom'] === 'temp') {
                $acquisitionSystem->setTemperature((float) $entry['valeur']);
            } elseif ($entry['nom'] === 'hum') {
                $acquisitionSystem->setHumidity((int) $entry['valeur']);
            } elseif ($entry['nom'] === 'co2') {
                $acquisitionSystem->setCo2((int) $entry['valeur']);
            }
        }

        // Persiste et sauvegarde les changements
        $entityManager = $this->getEntityManager();
        $entityManager->persist($acquisitionSystem);
        $entityManager->flush();
    }



    public function updateRoomState(Room $room): void
    {
        // Vérifie s'il y a un système d'acquisition associé
        $acquisitionSystem = $room->getAcquisitionSystem();

        if (!$acquisitionSystem) {
            $room->setState(RoomStateEnum::NONE);
            return;
        }

        // Récupère les données actuelles
        $temperature = $acquisitionSystem->getTemperature();
        $humidity = $acquisitionSystem->getHumidity();
        $co2 = $acquisitionSystem->getCo2();

        // Définit les périodes
        $currentMonth = (int)(new \DateTime())->format('m');
        $isHeatingPeriod = $currentMonth >= 11 || $currentMonth <= 4;

        $state = RoomStateEnum::STABLE; // Par défaut

        // Évaluation de la température
        if ($temperature !== null) {
            if ($isHeatingPeriod) {
                if ($temperature < 17) {
                    $state = RoomStateEnum::AT_RISK;
                } elseif ($temperature > 21) {
                    $state = RoomStateEnum::AT_RISK;
                }
            } else {
                if ($temperature < 24) {
                    $state = RoomStateEnum::AT_RISK;
                } elseif ($temperature > 28) {
                    $state = RoomStateEnum::AT_RISK;
                }
            }
        }

        // Évaluation de la qualité de l'air (CO2)
        if ($co2 !== null) {
            if ($co2 < 440 || $co2 > 2000) {
                $state = RoomStateEnum::CRITICAL;
            } elseif ($co2 > 1500) {
                $state = RoomStateEnum::CRITICAL;
            } elseif ($co2 > 1000) {
                $state = max($state, RoomStateEnum::AT_RISK);
            }
        }

        // Évaluation de l'humidité
        if ($humidity !== null) {
            if ($humidity < 30) {
                $state = max($state, RoomStateEnum::AT_RISK);
            } elseif ($humidity > 70 && $temperature > 20) {
                $state = RoomStateEnum::CRITICAL;
            } elseif ($humidity > 60) {
                $state = max($state, RoomStateEnum::AT_RISK);
            }
        }

        // Met à jour l'état de la salle
        $room->setState($state);

        // Persiste les changements
        $this->getEntityManager()->persist($room);
        $this->getEntityManager()->flush();
    }


}
