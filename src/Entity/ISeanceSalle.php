<?php

namespace App\Entity;

use App\Repository\ISeanceSalleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ISeanceSalleRepository::class)]
class ISeanceSalle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $code_salle;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $id_pointeuse;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $emplacement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeSalle(): ?string
    {
        return $this->code_salle;
    }

    public function setCodeSalle(?string $code_salle): self
    {
        $this->code_salle = $code_salle;

        return $this;
    }

    public function getIdPointeuse(): ?string
    {
        return $this->id_pointeuse;
    }

    public function setIdPointeuse(?string $id_pointeuse): self
    {
        $this->id_pointeuse = $id_pointeuse;

        return $this;
    }

    public function getEmplacement(): ?string
    {
        return $this->emplacement;
    }

    public function setEmplacement(?string $emplacement): self
    {
        $this->emplacement = $emplacement;

        return $this;
    }
}
