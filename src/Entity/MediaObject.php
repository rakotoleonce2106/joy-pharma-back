<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\MediaObjectRepository;
use App\State\MediaObjectProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: MediaObjectRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['media_object:read']],
    types: ['https://schema.org/MediaObject'],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            inputFormats: ['multipart' => ['multipart/form-data']],
            processor: MediaObjectProcessor::class
        )
    ]
)]
class MediaObject
{
    use EntityIdTrait;
    use EntityTimestampTrait;

    #[ApiProperty(types: ['https://schema.org/contentUrl'])]
    private ?string $contentUrl = null;

    #[Groups(['media_object:read', 'image:read', 'product:read'])]
    public function getContentUrl(): ?string
    {
        if ($this->contentUrl) {
            return $this->contentUrl;
        }
        
        if ($this->filePath) {
            // Si c'est une référence externe, retourner le chemin tel quel
            if ($this->isExternalReference) {
                return $this->filePath;
            }
            
            // Construire l'URL en fonction du mapping
            $prefix = match($this->mapping) {
                'category_images' => '/images/categories/',
                'category_icons' => '/icons/categories/',
                'product_images' => '/images/products/',
                default => '/media/',
            };
            
            return $prefix . $this->filePath;
        }
        
        return null;
    }

    public function setContentUrl(?string $contentUrl): void
    {
        $this->contentUrl = $contentUrl;
    }

    #[Vich\UploadableField(mapping: 'media_object', fileNameProperty: 'filePath')]
    #[Groups(['media_object:write'])]
    public ?File $file = null;

    #[ORM\Column(nullable: true)]
    public ?string $filePath = null;

    /**
     * VichUploader mapping name (media_object, category_images, category_icons, product_images)
     */
    #[ORM\Column(length: 50, nullable: true, options: ['default' => 'media_object'])]
    private ?string $mapping = 'media_object';

    /**
     * Indicates if this is a reference to an existing file (not managed by VichUploader)
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isExternalReference = false;

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): void
    {
        $this->filePath = $filePath;
    }

    public function isExternalReference(): bool
    {
        return $this->isExternalReference;
    }

    public function setIsExternalReference(bool $isExternalReference): void
    {
        $this->isExternalReference = $isExternalReference;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): void
    {
        $this->file = $file;
        if ($file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getMapping(): ?string
    {
        return $this->mapping;
    }

    public function setMapping(?string $mapping): void
    {
        $this->mapping = $mapping;
    }
}

