<?php

namespace App\Repository;

use App\Entity\Action;
use App\Utils\ActionStateEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class ActionRepository
 *
 * Repository class for managing Action entities.
 *
 * @extends ServiceEntityRepository<Action>
 *
 * @package App\Repository
 */
class ActionRepository extends ServiceEntityRepository
{
    /**
     * ActionRepository constructor.
     *
     * Initializes the repository with the Action entity class.
     *
     * @param ManagerRegistry $registry The manager registry.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Action::class);
    }

    /**
     * Retrieves all Action entities except those with a state of DONE.
     *
     * This method fetches all actions that are not marked as done, ordered by their creation date in ascending order.
     *
     * @return Action[] An array of Action entities excluding those with state DONE.
     */
    public function findAllExceptDone(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.state != :done')
            ->setParameter('done', ActionStateEnum::DONE)
            ->orderBy('a.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les actions en fonction des critères.
     *
     * @param array $criteria
     * @return Action[]
     */
    public function findByCriteria(array $criteria): array
    {
        return $this->findBy($criteria);
    }
}
