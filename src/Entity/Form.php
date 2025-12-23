<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Repository\FormRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormRepository::class)]
class Form
{
    use EntityIdTrait;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read', 'form:read', 'form:write'])]
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

    #[Groups(['form:read'])]
    public function getName(): ?string
    {
        return $this->label;
    }
}
