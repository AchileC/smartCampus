<?php

namespace App\Repository;

use App\Entity\Threshold;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Threshold>
 */
class ThresholdRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Threshold::class);
    }

    public function getDefaultThresholds(): Threshold
    {
        $threshold = $this->findOneBy([]);
        
        if (!$threshold) {
            $threshold = new Threshold();
            $this->getEntityManager()->persist($threshold);
            $this->getEntityManager()->flush();
        }
        
        return $threshold;
    }

    public function resetToDefault(): Threshold
    {
        $threshold = $this->getDefaultThresholds();
        
        // Reset to default values
        $threshold
            // Heating period temperature
            ->setHeatingTempCriticalMin(17.0)
            ->setHeatingTempWarningMin(19.0)
            ->setHeatingTempWarningMax(21.0)
            ->setHeatingTempCriticalMax(23.0)
            // Non-heating period temperature
            ->setNonHeatingTempCriticalMin(22.0)
            ->setNonHeatingTempWarningMin(24.0)
            ->setNonHeatingTempWarningMax(28.0)
            ->setNonHeatingTempCriticalMax(30.0)
            // Humidity
            ->setHumCriticalMin(20.0)
            ->setHumWarningMin(30.0)
            ->setHumWarningMax(60.0)
            ->setHumCriticalMax(70.0);

        $this->getEntityManager()->flush();
        
        return $threshold;
    }
} 