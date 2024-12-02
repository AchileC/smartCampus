<?php
//Room.php
namespace App\Entity;

use App\Repository\RoomRepository;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use App\Utils\CardinalEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;




#[ORM\Entity(repositoryClass: RoomRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'The room name must be unique. This name is already in use.', groups: ['add'])]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Room name is required.', groups: ['add'])]
    private ?string $name = null;
    #[ORM\Column(type: 'string', enumType: FloorEnum::class)]
    private ?FloorEnum $floor = null;
    #[ORM\Column(type: 'string', enumType: RoomStateEnum::class, nullable: true)]
    private ?RoomStateEnum $state = null;
    #[ORM\Column(type: 'string', enumType: RoomStateEnum::class, nullable: true)]
    private ?RoomStateEnum $previousState = null;
    #[ORM\Column(type: 'string', enumType: SensorStateEnum::class, nullable: true)]
    private ?SensorStateEnum $sensorState = null;
    #[ORM\Column(type: 'string', enumType: SensorStateEnum::class, nullable: true)]
    private ?SensorStateEnum $previousSensorState = null;
    #[ORM\Column(type: 'string', enumType: CardinalEnum::class, nullable: true)]
    private ?CardinalEnum $cardinalDirection = null;
    #[ORM\Column(nullable: true)]
    private ?int $nbHeaters = null;
    #[ORM\Column(nullable: true)]
    private ?int $nbWindows = null;
    #[ORM\Column]
    private ?float $surface = null;
    #[ORM\OneToOne(mappedBy: 'room', cascade: ['persist'], orphanRemoval: false)]
    private ?AcquisitionSystem $acquisitionSystem = null;


    public function getId(): ?int
    {
        return $this->id;
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

    public function getFloor(): ?FloorEnum
    {
        return $this->floor;
    }

    public function setFloor(FloorEnum $floor): static
    {
        $this->floor = $floor;

        return $this;
    }

    public function getState(): ?RoomStateEnum
    {
        return $this->state;
    }

    public function setState(RoomStateEnum $state): static
    {
        $this->state = $state;
        return $this;
    }

    public function getPreviousState(): ?RoomStateEnum
    {
        return $this->previousState;
    }

    public function setPreviousState(?RoomStateEnum $previousState): static
    {
        $this->previousState = $previousState;
        return $this;
    }

    public function getSensorState(): ?SensorStateEnum
    {
        return $this->sensorState;
    }

    public function setSensorState(SensorStateEnum $sensorState): static
    {
        $this->sensorState = $sensorState;
        return $this;
    }

    public function getPreviousSensorState(): ?SensorStateEnum
    {
        return $this->previousSensorState;
    }

    public function setPreviousSensorState(SensorStateEnum $previousSensorState): static
    {
        $this->previousSensorState = $previousSensorState;
        return $this;
    }

    public function getCardinalDirection(): ?CardinalEnum
    {
        return $this->cardinalDirection;
    }

    public function setCardinalDirection(CardinalEnum $cardinalDirection): static
    {
        $this->cardinalDirection = $cardinalDirection;
        return $this;
    }




    public function getNbHeaters(): ?int
    {
        return $this->nbHeaters;
    }

    public function setNbHeaters(?int $nbHeaters): static
    {
        $this->nbHeaters = $nbHeaters;

        return $this;
    }

    public function getNbWindows(): ?int
    {
        return $this->nbWindows;
    }

    public function setNbWindows(?int $nbWindows): static
    {
        $this->nbWindows = $nbWindows;

        return $this;
    }

    public function getSurface(): ?float
    {
        return $this->surface;
    }

    public function setSurface(float $surface): static
    {
        $this->surface = $surface;

        return $this;
    }

    public function getAcquisitionSystem(): ?AcquisitionSystem
    {
        return $this->acquisitionSystem;
    }

    public function setAcquisitionSystem(?AcquisitionSystem $acquisitionSystem): static
    {
        // unset the owning side of the relation if necessary
        if ($acquisitionSystem === null && $this->acquisitionSystem !== null) {
            $this->acquisitionSystem->setRoom(null);
        }

        // set the owning side of the relation if necessary
        if ($acquisitionSystem !== null && $acquisitionSystem->getRoom() !== $this) {
            $acquisitionSystem->setRoom($this);
        }

        $this->acquisitionSystem = $acquisitionSystem;

        return $this;
    }

}
