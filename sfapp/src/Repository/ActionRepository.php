<?php

namespace App\Repository;

use App\Entity\Action;
use App\Utils\ActionStateEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Action>
 */
class ActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Action::class);
    }

    public function findAllExceptDone(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.state != :done')
            ->setParameter('done', ActionStateEnum::DONE)
            ->orderBy('a.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}