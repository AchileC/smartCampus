<?php

namespace App\Entity;

use App\Repository\ThresholdRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ThresholdRepository::class)]
class Threshold
{
    // CO2 thresholds (fixed for safety reasons)
    public const CO2_CRITICAL_MIN = 400.0;
    public const CO2_WARNING_MIN = 1000.0;
    public const CO2_CRITICAL_MAX = 1500.0;
    public const CO2_ERROR_MAX = 2000.0;

    // Fixed thresholds for aberrant data (sensor malfunction)
    public const TEMP_ABERRANT_MIN = 10.0;
    public const TEMP_ABERRANT_MAX = 40.0;
    public const HUM_ABERRANT_MIN = 20.0;
    public const HUM_ABERRANT_MAX = 100.0;
    public const CO2_ABERRANT_MIN = 400.0;
    public const CO2_ABERRANT_MAX = 2000.0;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Temperature thresholds for heating period
    #[ORM\Column]
    #[Assert\Range(min: 10, max: 35)]
    private float $heatingTempCriticalMin = 17.0;

    #[ORM\Column]
    #[Assert\Range(min: 10, max: 35)]
    private float $heatingTempWarningMin = 19.0;

    #[ORM\Column]
    #[Assert\Range(min: 10, max: 35)]
    private float $heatingTempWarningMax = 21.0;

    #[ORM\Column]
    #[Assert\Range(min: 10, max: 35)]
    private float $heatingTempCriticalMax = 23.0;

    // Temperature thresholds for non-heating period
    #[ORM\Column]
    #[Assert\Range(min: 10, max: 35)]
    private float $nonHeatingTempCriticalMin = 22.0;

    #[ORM\Column]
    #[Assert\Range(min: 10, max: 35)]
    private float $nonHeatingTempWarningMin = 24.0;

    #[ORM\Column]
    #[Assert\Range(min: 10, max: 35)]
    private float $nonHeatingTempWarningMax = 28.0;

    #[ORM\Column]
    #[Assert\Range(min: 10, max: 35)]
    private float $nonHeatingTempCriticalMax = 30.0;

    // Humidity thresholds
    #[ORM\Column]
    #[Assert\Range(min: 0, max: 100)]
    private float $humCriticalMin = 20.0;

    #[ORM\Column]
    #[Assert\Range(min: 0, max: 100)]
    private float $humWarningMin = 30.0;

    #[ORM\Column]
    #[Assert\Range(min: 0, max: 100)]
    private float $humWarningMax = 60.0;

    #[ORM\Column]
    #[Assert\Range(min: 0, max: 100)]
    private float $humCriticalMax = 70.0;

    public function getId(): ?int
    {
        return $this->id;
    }

    // Getters and setters for heating period temperature thresholds
    public function getHeatingTempCriticalMin(): float
    {
        return $this->heatingTempCriticalMin;
    }

    public function setHeatingTempCriticalMin(float $value): self
    {
        $this->heatingTempCriticalMin = $value;
        return $this;
    }

    public function getHeatingTempWarningMin(): float
    {
        return $this->heatingTempWarningMin;
    }

    public function setHeatingTempWarningMin(float $value): self
    {
        $this->heatingTempWarningMin = $value;
        return $this;
    }

    public function getHeatingTempWarningMax(): float
    {
        return $this->heatingTempWarningMax;
    }

    public function setHeatingTempWarningMax(float $value): self
    {
        $this->heatingTempWarningMax = $value;
        return $this;
    }

    public function getHeatingTempCriticalMax(): float
    {
        return $this->heatingTempCriticalMax;
    }

    public function setHeatingTempCriticalMax(float $value): self
    {
        $this->heatingTempCriticalMax = $value;
        return $this;
    }

    // Getters and setters for non-heating period temperature thresholds
    public function getNonHeatingTempCriticalMin(): float
    {
        return $this->nonHeatingTempCriticalMin;
    }

    public function setNonHeatingTempCriticalMin(float $value): self
    {
        $this->nonHeatingTempCriticalMin = $value;
        return $this;
    }

    public function getNonHeatingTempWarningMin(): float
    {
        return $this->nonHeatingTempWarningMin;
    }

    public function setNonHeatingTempWarningMin(float $value): self
    {
        $this->nonHeatingTempWarningMin = $value;
        return $this;
    }

    public function getNonHeatingTempWarningMax(): float
    {
        return $this->nonHeatingTempWarningMax;
    }

    public function setNonHeatingTempWarningMax(float $value): self
    {
        $this->nonHeatingTempWarningMax = $value;
        return $this;
    }

    public function getNonHeatingTempCriticalMax(): float
    {
        return $this->nonHeatingTempCriticalMax;
    }

    public function setNonHeatingTempCriticalMax(float $value): self
    {
        $this->nonHeatingTempCriticalMax = $value;
        return $this;
    }

    // Getters and setters for humidity thresholds
    public function getHumCriticalMin(): float
    {
        return $this->humCriticalMin;
    }

    public function setHumCriticalMin(float $value): self
    {
        $this->humCriticalMin = $value;
        return $this;
    }

    public function getHumWarningMin(): float
    {
        return $this->humWarningMin;
    }

    public function setHumWarningMin(float $value): self
    {
        $this->humWarningMin = $value;
        return $this;
    }

    public function getHumWarningMax(): float
    {
        return $this->humWarningMax;
    }

    public function setHumWarningMax(float $value): self
    {
        $this->humWarningMax = $value;
        return $this;
    }

    public function getHumCriticalMax(): float
    {
        return $this->humCriticalMax;
    }

    public function setHumCriticalMax(float $value): self
    {
        $this->humCriticalMax = $value;
        return $this;
    }

    // Getters for CO2 thresholds (fixed values)
    public function getCo2CriticalMin(): float
    {
        return self::CO2_CRITICAL_MIN;
    }

    public function getCo2WarningMin(): float
    {
        return self::CO2_WARNING_MIN;
    }

    public function getCo2CriticalMax(): float
    {
        return self::CO2_CRITICAL_MAX;
    }

    public function getCo2ErrorMax(): float
    {
        return self::CO2_ERROR_MAX;
    }

    // Add methods to check for aberrant values
    public function isTemperatureAberrant(?float $temperature): bool
    {
        if ($temperature === null) return false;
        return $temperature < self::TEMP_ABERRANT_MIN || $temperature > self::TEMP_ABERRANT_MAX;
    }

    public function isHumidityAberrant(?float $humidity): bool
    {
        if ($humidity === null) return false;
        return $humidity < self::HUM_ABERRANT_MIN || $humidity > self::HUM_ABERRANT_MAX;
    }

    public function isCo2Aberrant(?float $co2): bool
    {
        if ($co2 === null) return false;
        return $co2 < self::CO2_ABERRANT_MIN || $co2 > self::CO2_ABERRANT_MAX;
    }

    public function hasAberrantValues(?float $temperature, ?float $humidity, ?float $co2): bool
    {
        return $this->isTemperatureAberrant($temperature) ||
               $this->isHumidityAberrant($humidity) ||
               $this->isCo2Aberrant($co2);
    }

    // Validation methods
    public function validateHeatingTemperatureThresholds(): bool
    {
        return $this->heatingTempCriticalMin < $this->heatingTempWarningMin &&
            $this->heatingTempWarningMin < $this->heatingTempWarningMax &&
            $this->heatingTempWarningMax < $this->heatingTempCriticalMax;
    }

    public function validateNonHeatingTemperatureThresholds(): bool
    {
        return $this->nonHeatingTempCriticalMin < $this->nonHeatingTempWarningMin &&
            $this->nonHeatingTempWarningMin < $this->nonHeatingTempWarningMax &&
            $this->nonHeatingTempWarningMax < $this->nonHeatingTempCriticalMax;
    }

    public function validateHumidityThresholds(): bool
    {
        return $this->humCriticalMin < $this->humWarningMin &&
            $this->humWarningMin < $this->humWarningMax &&
            $this->humWarningMax < $this->humCriticalMax;
    }
} 