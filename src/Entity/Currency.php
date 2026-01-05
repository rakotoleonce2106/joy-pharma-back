<?php

namespace App\Entity;

use App\Repository\CurrencyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CurrencyRepository::class)]
class Currency
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['id:read', 'currency:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 3, unique: true)]
    #[Groups(['currency:read', 'currency:write'])]
    #[Assert\Length(min: 3, max: 3, groups: ['create'])]
    private ?string $isoCode = null;

    #[ORM\Column(length: 255)]
    #[Groups(['currency:read', 'currency:write'])]
    #[Assert\NotBlank(groups: ['create'])]
    private ?string $label = null;

    #[ORM\Column(length: 10)]
    #[Groups(['currency:read', 'currency:write'])]
    #[Assert\NotBlank(groups: ['create'])]
    private ?string $symbol = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIsoCode(): ?string
    {
        return $this->isoCode;
    }

    public function setIsoCode(?string $isoCode): static
    {
        $this->isoCode = $isoCode;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): static
    {
        $this->symbol = $symbol;

        return $this;
    }
}
