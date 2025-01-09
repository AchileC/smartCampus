<?php
//ThresholdRepository.php
namespace App\Repository;

use App\Entity\Threshold;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @brief Repository for managing Threshold entities.
 *
 * The ThresholdRepository provides methods to retrieve and reset threshold values for sensor data evaluations.
 *
 * @extends ServiceEntityRepository<Threshold>
 */
class ThresholdRepository extends ServiceEntityRepository
{
    /**
     * @brief Constructs the repository with the given registry.
     *
     * @param ManagerRegistry $registry The manager registry.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Threshold::class);
    }

    /**
     * @brief Retrieves the default thresholds.
     *
     * If no thresholds exist, creates and persists a new Threshold entity with default values.
     *
     * @return Threshold The default Threshold entity.
     */
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

    /**
     * @brief Resets all thresholds to their default values.
     *
     * Updates the Threshold entity with predefined default values.
     *
     * @return Threshold The updated Threshold entity.
     */
    public function resetToDefault(): Threshold
    {
        $threshold = $this->getDefaultThresholds();

        // Reset to default values
        $threshold
            // Heating period temperature
            ->setHeatingTempCriticalMin(16.0)
            ->setHeatingTempWarningMin(18.0)
            ->setHeatingTempWarningMax(22.0)
            ->setHeatingTempCriticalMax(24.0)
            // Non-heating period temperature
            ->setNonHeatingTempCriticalMin(20.0)
            ->setNonHeatingTempWarningMin(22.0)
            ->setNonHeatingTempWarningMax(26.0)
            ->setNonHeatingTempCriticalMax(28.0)
            // Humidity
            ->setHumCriticalMin(20.0)
            ->setHumWarningMin(30.0)
            ->setHumWarningMax(60.0)
            ->setHumCriticalMax(70.0);

        $this->getEntityManager()->flush();

        return $threshold;
    }
}
