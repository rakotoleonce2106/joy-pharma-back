<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Repository\QuantityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: QuantityRepository::class)]
class Quantity
{
    use EntityIdTrait;

    #[ORM\Column(nullable: true)]
    #[Groups(['product:read'])]
    private ?float $count = null;

    #[ORM\ManyToOne]
    #[Groups(['product:read'])]
    private ?Unit $unit = null;


    public function getCount(): ?float
    {
        return $this->count;
    }

    public function setCount(?float $count): static
    {
        $this->count = $count;

        return $this;
    }

    public function getUnit(): ?Unit
    {
        return $this->unit;
    }

    public function setUnit(?Unit $unit): static
    {
        $this->unit = $unit;

        return $this;
    }
}
