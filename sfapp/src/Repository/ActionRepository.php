<?php
//ActionRepository.php
namespace App\Repository;

use App\Entity\Action;
use App\Utils\ActionStateEnum;
use App\Utils\ActionInfoEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @brief Repository for managing Action entities.
 *
 * The ActionRepository provides methods to query and manipulate Action entities.
 *
 * @extends ServiceEntityRepository<Action>
 */
class ActionRepository extends ServiceEntityRepository
{

    /**
     * @brief Constructs the repository with the given registry.
     *
     * @param ManagerRegistry $registry The manager registry.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Action::class);
    }


    /**
     * @brief Finds all actions except those marked as done.
     *
     * @return Action[] An array of Action entities not in the DONE state.
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
     * @brief Finds tasks associated with a room that are pending or in progress and of type 'assignment'.
     *
     * @param int   $roomId The ID of the room.
     * @param array $states The states to filter by, defaulting to ['to do', 'doing'].
     *
     * @return Action[] An array of Action entities matching the criteria.
     */
    public function findTasksForRoomToDelete(int $roomId, array $states = ['to do', 'doing']): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.room = :room')
            ->andWhere('a.info = :info') // Only tasks of type 'assignment'
            ->andWhere('a.state IN (:states)')
            ->setParameter('room', $roomId)
            ->setParameter('info', 'assignment')
            ->setParameter('states', $states)
            ->getQuery()
            ->getResult();
    }


    /**
     * @brief Finds the latest five actions that are not marked as done.
     *
     * @return Action[] An array of the latest five Action entities not in the DONE state.
     */
    public function findLatestFive(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.state != :done')
            ->setParameter('done', ActionStateEnum::DONE)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    /**
     * @brief Finds actions based on arbitrary criteria.
     *
     * @param array $criteria The criteria for filtering actions.
     *
     * @return Action[] An array of Action entities matching the criteria.
     */
    public function findByCriteria(array $criteria): array
    {
        return $this->findBy($criteria);
    }

    public function findOngoingTaskForRoom(int $roomId): ?Action
    {
        return $this->createQueryBuilder('a')
            ->where('a.room = :room')
            ->andWhere('a.state IN (:states)')
            ->andWhere('a.info IN (:infos)')
            ->setParameter('room', $roomId)
            ->setParameter('states', [ActionStateEnum::TO_DO, ActionStateEnum::DOING])
            ->setParameter('infos', [ActionInfoEnum::ASSIGNMENT, ActionInfoEnum::UNASSIGNMENT, ActionInfoEnum::MAINTENANCE])
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}
