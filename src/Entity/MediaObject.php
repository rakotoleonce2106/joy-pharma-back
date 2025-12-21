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
            uriTemplate: '/media_objects',
            inputFormats: ['multipart' => ['multipart/form-data']],
            outputFormats: ['jsonld' => ['application/ld+json'], 'json' => ['application/json']],
            processor: MediaObjectProcessor::class,
            openapiContext: [
                'summary' => 'Upload a file (POST only)',
                'description' => <<<'DESC'
Upload a file to create a MediaObject. This endpoint uses POST method only because:

**Technical Reasons:**
- PHP's $_FILES superglobal only works with POST requests
- php://input doesn't parse multipart/form-data for PUT/PATCH requests
- Symfony follows PHP's native behavior for file uploads

**Usage Pattern:**
1. Upload file using POST /api/media_objects with multipart/form-data
2. Receive the MediaObject IRI in response (e.g., "/api/media_objects/123")
3. Use this IRI in your create/update requests (PUT/PATCH with JSON)

**Example Request (Create):**
```
POST /api/media_objects
Content-Type: multipart/form-data

file: [binary file data]
mapping: "category_images" (optional)
```

**Example Request (Update):**
```
POST /api/media_objects
Content-Type: multipart/form-data

id: 123 (optional - if provided and MediaObject exists, it will be updated)
file: [binary file data]
mapping: "category_images" (optional)
```

**Example Response:**
```json
{
  "@id": "/api/media_objects/123",
  "contentUrl": "/images/categories/abc123.jpg"
}
```

**For Updates:**
- Use POST /api/media_objects with `id` field to update existing MediaObject
- If `id` is provided and MediaObject exists, it will be updated with the new file
- If `id` is provided but MediaObject doesn't exist, a new MediaObject will be created
- Or use PUT/PATCH with JSON and reference existing MediaObject IRI
DESC
            ]
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
            $mapping = $this->mapping ?? 'media_object';
            $prefix = match($mapping) {
                'category_images' => '/images/categories/',
                'category_icons' => '/icons/categories/',
                'product_images' => '/images/products/',
                'brand_images' => '/images/brands/',
                'manufacturer_images' => '/images/manufacturers/',
                'user_images' => '/images/users/',
                'store_images' => '/images/stores/',
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


