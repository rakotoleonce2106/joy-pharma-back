<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;


readonly class DownlaodFileService
{

    private string $projectDir;

    public function __construct(string $projectDir)
    {

        $this->projectDir = $projectDir;

    }


    public function generateImageUrl(string $imageUrl, string $subDirectory): array
    {
        $uploadDir = $this->projectDir . '/public/images/' . $subDirectory;

        // Create directory structure if it doesn't exist
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                throw new \RuntimeException('Failed to create upload directory');
            }
        }

        // Generate unique filename to prevent overwrites
        $extension = pathinfo($imageUrl, PATHINFO_EXTENSION) ?: 'jpg';
        $fileName = uniqid('', true) . '.' . $extension;
        $newUrl = '/uploads/' . $subDirectory . '/' . $fileName;
        $fullPath = $uploadDir . '/' . $fileName;

        // Download and save the image
        $content = @file_get_contents($imageUrl);
        if ($content === false) {
            throw new \RuntimeException('Failed to download image from: ' . $imageUrl);
        }

        if (file_put_contents($fullPath, $content) === false) {
            throw new \RuntimeException('Failed to save image to: ' . $fullPath);
        }

        // Verify the file was created and is readable
        if (!is_readable($fullPath)) {
            throw new \RuntimeException('Created file is not readable: ' . $fullPath);
        }

        return ['url' => $newUrl, 'path' => $fullPath];
    }

}

