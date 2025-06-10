<?php

namespace App\Service;

use App\Entity\MediaFile;
use App\Repository\MediaFileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;


readonly class MediaFileService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private MediaFileRepository    $fileRepository
    )
    {
    }


    public function createMediaFileByUrl(string $url): MediaFile
    {

        $parsedUrl = parse_url($url, PHP_URL_PATH);
        $filename = basename($parsedUrl);

        $localUrl = '/images/products/' . $filename;

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $mimeType = $this->getMimeTypeFromExtension($extension);

        $mediaFile = new MediaFile();
        $mediaFile->setName($filename);
        $mediaFile->setMimeType($mimeType);
        $mediaFile->setUrl($localUrl);

        $this->manager->persist($mediaFile);

        return $mediaFile;
    }

    public  function createMediaByFile(UploadedFile $file, string $path):MediaFile
    {
        $filename = $file->getClientOriginalName();
        $extension = $file->guessExtension() ?: 'bin';
        $mimeType = $this->getMimeTypeFromExtension($extension);

        $mediaFile = new MediaFile();
        $mediaFile->setName($filename);
        $mediaFile->setMimeType($mimeType);
        $mediaFile->setUrl('/' . $path . $filename);

        // Move the file to the uploads directory
        $file->move($path, $filename);

        $this->manager->persist($mediaFile);
        return $mediaFile;
    }

    public function updateMediaFileFromFile(MediaFile $mediaFile, UploadedFile $file, string $path): MediaFile
    {
        // Remove the old file if it exists
        if (file_exists($mediaFile->getUrl())) {
            unlink($mediaFile->getUrl());
        }

        $filename = $file->getClientOriginalName();
        $extension = $file->guessExtension() ?: 'bin';
        $mimeType = $this->getMimeTypeFromExtension($extension);

        // Update media file properties
        $mediaFile->setName($filename);
        $mediaFile->setMimeType($mimeType);
        $mediaFile->setUrl('/' . $path . $filename);

        // Move the new file to the uploads directory
        $file->move($path, $filename);

        // Persist the updated media file
        $this->manager->persist($mediaFile);
        return $mediaFile;
    }

    private function getMimeTypeFromExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            default => 'application/octet-stream',
        };
    }

    public function updateMediaFile(MediaFile $mediaFile): void
    {
        $this->manager->flush();
    }

    public function batchDeleteCategories(array $fileIds): void
    {


        foreach ($fileIds as $id) {
            $file = $this->fileRepository->find($id);
            if ($file) {
                $this->deleteMediaFile($file);
            }
        }

    }

    public function deleteMediaFile(MediaFile $mediaFile): void
    {
        $this->manager->remove($mediaFile);
        $this->manager->flush();
    }
}
