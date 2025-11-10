<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MediaObject;
use Doctrine\ORM\EntityManagerInterface;

final class MediaObjectProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MediaObject
    {
        if ($data->getFile()) {
            $this->entityManager->persist($data);
            $this->entityManager->flush();
            $data->setContentUrl('/media/' . $data->getFilePath());
        } else {
            // If no file, just persist the entity
            $this->entityManager->persist($data);
            $this->entityManager->flush();
        }

        return $data;
    }
}

