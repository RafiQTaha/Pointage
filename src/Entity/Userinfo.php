<?php

namespace App\Entity;

use App\Repository\UserinfoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserinfoRepository::class)]
class Userinfo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $USERID;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $Badgenumber;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $Name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $street;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private $defaultdeptid;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $Ophone;

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

    public function getBadgenumber(): ?string
    {
        return $this->Badgenumber;
    }

    public function setBadgenumber(?string $Badgenumber): self
    {
        $this->Badgenumber = $Badgenumber;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->Name;
    }

    public function setName(?string $Name): self
    {
        $this->Name = $Name;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getDefaultdeptid(): ?int
    {
        return $this->defaultdeptid;
    }

    public function setDefaultdeptid(?int $defaultdeptid): self
    {
        $this->defaultdeptid = $defaultdeptid;

        return $this;
    }

    public function getOphone(): ?string
    {
        return $this->Ophone;
    }

    public function setOphone(?string $Ophone): self
    {
        $this->Ophone = $Ophone;

        return $this;
    }
}
