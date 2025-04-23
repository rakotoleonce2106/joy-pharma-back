<?php

namespace App\Entity;

use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\MediaFileRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MediaFileRepository::class)]
class MediaFile
{
    use EntityIdTrait;
    use EntityTimestampTrait;

    #[ORM\Column(length: 255)]
    #[Groups(['image:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['image:read'])]
    private ?int $size = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['image:read'])]
    private ?string $mimeType = null;

    #[ORM\Column(length: 255)]
    #[Groups(['image:read'])]
    private ?string $url = null;

    #[ORM\ManyToOne(inversedBy: 'images')]
    private ?Product $product = null;


    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }
}
