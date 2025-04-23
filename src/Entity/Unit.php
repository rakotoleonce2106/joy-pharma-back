<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Repository\UnitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UnitRepository::class)]
class Unit
{
    use EntityIdTrait;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read'])]
    private ?string $label = null;


    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }
}
