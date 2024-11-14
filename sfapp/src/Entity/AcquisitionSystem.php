<?php
//AcquisitionSystem.php
namespace App\Entity;

use App\Repository\AcquisitionSystemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AcquisitionSystemRepository::class)]
class AcquisitionSystem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?float $temperature = null;

    #[ORM\Column(nullable: true)]
    private ?int $humidity = null;

    #[ORM\Column(nullable: true)]
    private ?int $co2 = null;

    #[ORM\OneToOne(inversedBy: 'acquisitionSystem', cascade: ['persist', 'remove'])]
    private ?Room $room = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function setRoom(?Room $room): static
    {
        $this->room = $room;

        return $this;
    }
}
