<?php

namespace App\Entity\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

trait EntityStatusTrait
{
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['status:read'])]
    #[Assert\Type(type: 'bool', groups: ['admin:write'])]
    private bool $active = true;

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function activate(): static
    {
        $this->active = true;

        return $this;
    }

    public function deactivate(): static
    {
        $this->active = false;

        return $this;
    }
}