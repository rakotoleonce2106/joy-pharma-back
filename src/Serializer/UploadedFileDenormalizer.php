<?php

namespace App\Serializer;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UploadedFileDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, string $type, string $format = null, array $context = []): File|UploadedFile
    {
        // If data is already a File or UploadedFile instance, return it as-is
        if ($data instanceof File || $data instanceof UploadedFile) {
            return $data;
        }
        
        // This should not happen if supportsDenormalization is working correctly
        return $data;
    }

    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        // Support both File and UploadedFile types
        $supportedTypes = [File::class, UploadedFile::class];
        
        // Check if the requested type is supported
        if (!in_array($type, $supportedTypes, true)) {
            return false;
        }
        
        // Check if data is already a File or UploadedFile instance
        return $data instanceof File || $data instanceof UploadedFile;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            File::class => true,
            UploadedFile::class => true,
        ];
    }
}
