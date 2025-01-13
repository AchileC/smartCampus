<?php

namespace App\Repository;

use App\Entity\Room;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Repository class for managing Room entities.
 *
 * Provides methods to query and manage Room entities in the database,
 * including filtering by various criteria, handling JSON directories,
 * and counting or retrieving specific rooms.
 */
class RoomRepository extends ServiceEntityRepository
{
    /** @var ThresholdRepository */
    private ThresholdRepository $thresholdRepository;

    /** @var HttpClientInterface */
    private HttpClientInterface $httpClient;

    /** @var string Path to the project directory */
    private string $projectDir;

    /** @var string Path to the JSON directory (assets/json/...) */
    private string $jsonDirectory;

    /**
     * @param ManagerRegistry      $registry
     * @param ThresholdRepository  $thresholdRepository
     * @param HttpClientInterface  $httpClient
     * @param string               $projectDir       The project directory path
     * @param string               $jsonDirectory    The directory path for JSON files
     */
    public function __construct(
        ManagerRegistry $registry,
        ThresholdRepository $thresholdRepository,
        HttpClientInterface $httpClient,
        string $projectDir,
        string $jsonDirectory
    ) {
        parent::__construct($registry, Room::class);
        $this->thresholdRepository = $thresholdRepository;
        $this->httpClient = $httpClient;
        $this->projectDir = $projectDir;
        $this->jsonDirectory = $jsonDirectory;


        $liveDir = $this->jsonDirectory . '/live';
        if (!is_dir($liveDir)) {
            if (!mkdir($liveDir, 0755, true) && !is_dir($liveDir)) {
                throw new \RuntimeException(sprintf('Le dossier "%s" n\'a pas pu être créé.', $liveDir));
            }
        }

        // Assurer que le dossier 'history' existe
        $historyDir = $this->jsonDirectory . '/history';
        if (!is_dir($historyDir)) {
            if (!mkdir($historyDir, 0755, true) && !is_dir($historyDir)) {
                throw new \RuntimeException(sprintf('Le dossier "%s" n\'a pas pu être créé.', $historyDir));
            }
        }
    }






    /* ======================================================
     *                 PARTIE REQUÊTES BASIQUES
       ====================================================== */

    /**
     * @brief Finds rooms based on specified criteria.
     *
     * Supports filtering by:
     *   - 'name' (partial match)
     *   - 'floor' (exact match)
     *   - 'state' (exact match)
     *   - 'sensorStatus' (array of statuses)
     *
     * @param array $criteria
     * @return Room[]
     */
    public function findByCriteria(array $criteria): array
    {
        $qb = $this->createQueryBuilder('r');

        if (isset($criteria['name']) && !empty($criteria['name'])) {
            $qb->andWhere('r.name LIKE :name')
               ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        if (isset($criteria['floor'])) {
            $qb->andWhere('r.floor = :floor')
               ->setParameter('floor', $criteria['floor']);
        }

        if (isset($criteria['state'])) {
            $qb->andWhere('r.state = :state')
               ->setParameter('state', $criteria['state']);
        }

        if (isset($criteria['state_not'])) {
            $qb->andWhere('r.state != :state_not')
                ->setParameter('state_not', $criteria['state_not']);
        }

        if (isset($criteria['sensorStatus']) && is_array($criteria['sensorStatus'])) {
            $qb->andWhere('r.sensorState IN (:sensorStatus)')
               ->setParameter('sensorStatus', $criteria['sensorStatus']);
        }

        return $qb->orderBy('r.name', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * @brief Finds rooms that have an associated AcquisitionSystem.
     *
     * @return Room[] Rooms with an AcquisitionSystem
     */
    public function findRoomsWithAS(): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.acquisitionSystem', 'acq')
            ->getQuery()
            ->getResult();
    }

    public function findByName(string $name): ?Room
    {
        return $this->createQueryBuilder('r')
            ->where('r.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @brief Counts the number of actions in a specific state.
     *
     * @param string $state
     * @return int Number of actions in the specified state
     */
    public function countByState(string $state): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.state = :state')
            ->setParameter('state', $state)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
