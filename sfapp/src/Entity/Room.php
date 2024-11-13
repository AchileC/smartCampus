<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use App\Utils\FloorEnum;
use App\Utils\RoomStateEnum;
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

    #[ORM\Column(type: 'string', enumType: RoomStateEnum::class)]
    private ?RoomStateEnum $state = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
