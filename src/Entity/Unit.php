<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Repository\UnitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UnitRepository::class)]
class Unit
{
    use EntityIdTrait;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read', 'unit:read', 'unit:write'])]
    #[Assert\NotBlank(groups: ['create'])]
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

    #[Groups(['unit:read'])]
    public function getName(): ?string
    {
        return $this->label;
    }
}
