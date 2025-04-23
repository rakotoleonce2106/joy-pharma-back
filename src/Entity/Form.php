<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Repository\FormRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: FormRepository::class)]
class Form
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
