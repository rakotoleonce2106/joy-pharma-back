<?php

namespace App\Entity\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

trait EntitySlugTrait
{
    #[ORM\Column]
    #[Groups(['slug:read'])]
    private ?string $slug = null;

    /**
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @param string|null $slug
     * @return void
     */
    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

}