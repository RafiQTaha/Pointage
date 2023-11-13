<?php

namespace App\Entity;

use App\Repository\MachinesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MachinesRepository::class)]
class Machines
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $MachineAlias;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $IP;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $sn;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $type;

    #[ORM\OneToMany(mappedBy: 'machine', targetEntity: Checkinout::class)]
    private Collection $checkinouts;

    #[ORM\Column(nullable: true)]
    private ?float $active = null;

    public function __construct()
    {
        $this->checkinouts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMachineAlias(): ?string
    {
        return $this->MachineAlias;
    }

    public function setMachineAlias(?string $MachineAlias): self
    {
        $this->MachineAlias = $MachineAlias;

        return $this;
    }

    public function getIP(): ?string
    {
        return $this->IP;
    }

    public function setIP(?string $IP): self
    {
        $this->IP = $IP;

        return $this;
    }

    public function getSn(): ?string
    {
        return $this->sn;
    }

    public function setSn(?string $sn): self
    {
        $this->sn = $sn;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, Checkinout>
     */
    public function getCheckinouts(): Collection
    {
        return $this->checkinouts;
    }

    public function addCheckinout(Checkinout $checkinout): static
    {
        if (!$this->checkinouts->contains($checkinout)) {
            $this->checkinouts->add($checkinout);
            $checkinout->setMachine($this);
        }

        return $this;
    }

    public function removeCheckinout(Checkinout $checkinout): static
    {
        if ($this->checkinouts->removeElement($checkinout)) {
            // set the owning side to null (unless already changed)
            if ($checkinout->getMachine() === $this) {
                $checkinout->setMachine(null);
            }
        }

        return $this;
    }

    public function getActive(): ?float
    {
        return $this->active;
    }

    public function setActive(?float $active): static
    {
        $this->active = $active;

        return $this;
    }
}
