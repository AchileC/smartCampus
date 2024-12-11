<?php
//Action.php
namespace App\Entity;

use App\Repository\ActionRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Utils\ActionInfoEnum;
use App\Utils\ActionStateEnum;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActionRepository::class)]
class Action
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', enumType: ActionInfoEnum::class)]
    private ActionInfoEnum $info;

    #[ORM\Column(type: 'string', enumType: ActionStateEnum::class)]
    private ActionStateEnum $state = ActionStateEnum::TO_DO;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $startedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $completedAt = null;

    #[ORM\ManyToOne(targetEntity: Room::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Room $room = null;

    #[ORM\ManyToOne(targetEntity: AcquisitionSystem::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?AcquisitionSystem $acquisitionSystem = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInfo(): ActionInfoEnum
    {
        return $this->info;
    }

    public function setInfo(ActionInfoEnum $info): self
    {
        $this->info = $info;
        return $this;
    }

    public function getState(): ActionStateEnum
    {
        return $this->state;
    }

    public function setState(ActionStateEnum $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getStartedAt(): ?\DateTime
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTime $startedAt): self
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTime
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTime $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(Room $room): self
    {
        $this->room = $room;
        return $this;
    }

    public function getAcquisitionSystem(): ?AcquisitionSystem
    {
        return $this->acquisitionSystem;
    }

    public function setAcquisitionSystem(?AcquisitionSystem $acquisitionSystem): self
    {
        $this->acquisitionSystem = $acquisitionSystem;

        return $this;
    }
}
