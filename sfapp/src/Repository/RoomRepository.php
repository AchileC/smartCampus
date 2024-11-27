<?php
//RoomRepository.php
namespace App\Repository;

use App\Entity\Room;
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
}
