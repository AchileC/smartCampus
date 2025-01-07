<?php
//AcquisitionSystemRepository.php
namespace App\Repository;

use App\Entity\AcquisitionSystem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Utils\SensorStateEnum;

/**
 * @brief Repository for managing AcquisitionSystem entities.
 *
 * The AcquisitionSystemRepository provides methods to query and manipulate AcquisitionSystem entities.
 *
 * @extends ServiceEntityRepository<AcquisitionSystem>
 */
class AcquisitionSystemRepository extends ServiceEntityRepository
{
    /**
     * @brief Constructs the repository with the given registry.
     *
     * @param ManagerRegistry $registry The manager registry.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AcquisitionSystem::class);
    }

    /**
     * @brief Finds acquisition systems based on specified criteria.
     *
     * Supports filtering by name (partial match) and state.
     *
     * @param array $criteria The criteria for filtering, which can include:
     *                        - 'name': string (partial match)
     *                        - 'state': string (exact match)
     *
     * @return AcquisitionSystem[] An array of AcquisitionSystem entities matching the criteria.
     */
    public function findByCriteria(array $criteria): array
    {
        $queryBuilder = $this->createQueryBuilder('r');

        // name criteria
        if (isset($criteria['name']) && !empty($criteria['name'])) {
            $queryBuilder->andWhere('r.name LIKE :name')
                ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        // state criteria
        if (isset($criteria['state'])) {
            $queryBuilder->andWhere('r.state = :state')
                ->setParameter('state', $criteria['state']);
        }

        return $queryBuilder->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @brief Finds all acquisition systems that are not linked to any room.
     *
     * @return AcquisitionSystem[] An array of AcquisitionSystem entities that are not linked.
     */
    public function findSystemsNotLinked(): array
    {
        return $this->createQueryBuilder('acq')
            ->where('acq.state = :state')
            ->andWhere('acq.room IS NULL')
            ->setParameter('state', SensorStateEnum::NOT_LINKED->value)
            ->orderBy('acq.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
