<?php

namespace App\Entity;

use App\Repository\SituationSyncRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SituationSyncRepository::class)]
class SituationSync
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?float $sync = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSync(): ?float
    {
        return $this->sync;
    }

    public function setSync(?float $sync): static
    {
        $this->sync = $sync;

        return $this;
    }
}
