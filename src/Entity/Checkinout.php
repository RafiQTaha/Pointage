<?php

namespace App\Entity;

use App\Repository\CheckinoutRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CheckinoutRepository::class)]
class Checkinout
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $USERID;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $CHECKTIME;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $memoinfo;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $sn;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $CHECKTYPE;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created = null;

    #[ORM\ManyToOne(inversedBy: 'checkinouts')]
    private ?Machines $machine = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUSERID(): ?int
    {
        return $this->USERID;
    }

    public function setUSERID(?int $USERID): self
    {
        $this->USERID = $USERID;

        return $this;
    }

    public function getCHECKTIME(): ?\DateTimeInterface
    {
        return $this->CHECKTIME;
    }

    public function setCHECKTIME(?\DateTimeInterface $CHECKTIME): self
    {
        $this->CHECKTIME = $CHECKTIME;

        return $this;
    }

    public function getMemoinfo(): ?string
    {
        return $this->memoinfo;
    }

    public function setMemoinfo(?string $memoinfo): self
    {
        $this->memoinfo = $memoinfo;

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

    public function getCHECKTYPE(): ?string
    {
        return $this->CHECKTYPE;
    }

    public function setCHECKTYPE(?string $CHECKTYPE): self
    {
        $this->CHECKTYPE = $CHECKTYPE;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(?\DateTimeInterface $created): static
    {
        $this->created = $created;

        return $this;
    }

    public function getMachine(): ?Machines
    {
        return $this->machine;
    }

    public function setMachine(?Machines $machine): static
    {
        $this->machine = $machine;

        return $this;
    }
    
}
