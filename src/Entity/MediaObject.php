<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use App\Entity\Traits\EntityIdTrait;
use App\Entity\Traits\EntityTimestampTrait;
use App\Repository\MediaObjectRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: MediaObjectRepository::class)]
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
            $mapping = $this->mapping ?? 'media_object';
            $prefix = match($mapping) {
                'category_images' => '/images/categories/',
                'category_icons' => '/icons/categories/',
                'product_images' => '/images/products/',
                'brand_images' => '/images/brands/',
                'manufacturer_images' => '/images/manufacturers/',
                'user_images' => '/images/users/',
                'store_images' => '/images/stores/',
                'deliver_documents' => '/uploads/deliver/',
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


