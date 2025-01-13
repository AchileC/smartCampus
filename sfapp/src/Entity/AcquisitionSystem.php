<?php
//AcquisitionSystem.php
namespace App\Entity;

use App\Repository\AcquisitionSystemRepository;
use App\Utils\ActionStateEnum;
use App\Utils\SensorStateEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: AcquisitionSystemRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'The acquisition system name must be unique. This name is already in use.', groups: ['add'])]
class AcquisitionSystem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $dbName = null;

    #[ORM\Column(nullable: true)]
    private ?float $temperature = null;

    #[ORM\Column(nullable: true)]
    private ?int $humidity = null;

    #[ORM\Column(nullable: true)]
    private ?int $co2 = null;

    #[ORM\OneToOne(inversedBy: 'acquisitionSystem', targetEntity: Room::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Room $room = null;

    #[ORM\Column(type: 'string', enumType: SensorStateEnum::class, nullable: true)]
    private ?SensorStateEnum $state = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastCapturedAt = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDbName(): ?string
    {
        return $this->dbName;
    }

    public function setDbName(string $dbName): static
    {
        $this->dbName = $dbName;

        return $this;
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function setTemperature(?float $temperature): static
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function getHumidity(): ?int
    {
        return $this->humidity;
    }

    public function setHumidity(?int $humidity): static
    {
        $this->humidity = $humidity;

        return $this;
    }

    public function getCo2(): ?int
    {
        return $this->co2;
    }

    public function setCo2(?int $co2): static
    {
        $this->co2 = $co2;

        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function getState(): ?SensorStateEnum
    {
        return $this->state;
    }

    public function setState(SensorStateEnum $state): static
    {
        $this->state = $state;
        return $this;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;

        // Si une salle est associée, établir le lien bidirectionnel
        if ($room !== null && $room->getAcquisitionSystem() !== $this) {
            $room->setAcquisitionSystem($this);
        }

        // Si aucune salle n'est associée, mettre à jour l'état du système
        if ($room === null) {
            $this->setState(SensorStateEnum::NOT_LINKED);
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLastCapturedAt(): ?\DateTimeInterface
    {
        return $this->lastCapturedAt;
    }

    public function setLastCapturedAt(?\DateTimeInterface $dateTime): self
    {
        $this->lastCapturedAt = $dateTime;
        return $this;
    }


}
