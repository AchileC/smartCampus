<?php

namespace App\Repository;

use App\Entity\AcquisitionSystem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Utils\SensorStateEnum;

/**
 * @extends ServiceEntityRepository<AcquisitionSystem>
 * Repository for managing AcquisitionSystem entities.
 * Extends Doctrine's ServiceEntityRepository to provide custom query logic.
 */
class AcquisitionSystemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AcquisitionSystem::class);
    }
    /**
     * Finds AcquisitionSystem entities based on given criteria.
     * Supports filtering by name and state.
     * Results are ordered alphabetically by name.
     *
     * @param array $criteria Key-value pairs for filtering (e.g., ['name' => 'test', 'state' => 'active']).
     * @return AcquisitionSystem[] Array of matching AcquisitionSystem entities.
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

    public function findSystemsNotLinked(): array
    {
        return $this->createQueryBuilder('acq') // Alias pour AcquisitionSystem
        ->where('acq.state = :state')       // Vérifie que l'état correspond
        ->andWhere('acq.room IS NULL')      // Vérifie que le système n'est pas lié à une salle
        ->setParameter('state', SensorStateEnum::NOT_LINKED->value) // Utilisation de l'énumération
        ->orderBy('acq.name', 'ASC')        // Trie les résultats par nom
        ->getQuery()
            ->getResult();
    }
}
