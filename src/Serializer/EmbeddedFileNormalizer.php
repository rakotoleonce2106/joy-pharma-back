<?php
// src/Serializer/EmbeddedFileNormalizer.php

namespace App\Serializer;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;

class EmbeddedFileNormalizer implements NormalizerInterface
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function normalize($object, ?string $format = null, array $context = []): array
    {

        if (!$object instanceof EmbeddedFile || !$object->getName()) {
            return [];
        }

        return [
            'name' => $object->getName(),
            'size' => $object->getSize(),
            'mimeType' => $object->getMimeType(),
            'url' => '/images/profile/' . $object->getName()
            // If you need a more dynamic URL, use the router
            // 'url' => $this->router->generate('image_path', ['filename' => $object->getName()])
        ];
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof EmbeddedFile;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            EmbeddedFile::class => true,
        ];
    }


}
