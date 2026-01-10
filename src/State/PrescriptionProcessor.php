<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MediaObject;
use App\Entity\Prescription;
use App\Service\PrescriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;

final class PrescriptionProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $mediaObjectProcessor,
        private readonly PrescriptionService $prescriptionService,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Pour les opérations normales sur Prescription, on laisse le comportement par défaut
        if ($data instanceof Prescription) {
            // Création ou mise à jour normale d'une prescription
            $this->entityManager->persist($data);
            $this->entityManager->flush();

            return $data;
        }

        // Pour les uploads de fichiers de prescription, on traite différemment
        $request = $this->requestStack->getCurrentRequest();

        if ($request && $request->files->has('file')) {
            $file = $request->files->get('file');

            if ($file instanceof UploadedFile && $this->isPrescriptionFile($file)) {
                // Debug: Vérifier l'utilisateur dans le contexte du processor
                $request = $this->requestStack->getCurrentRequest();
                $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null); // Disable SQL logging for debug

                // Traiter le fichier et créer la prescription (utilisateur récupéré automatiquement)
                $prescription = $this->prescriptionService->processPrescriptionFile($file);

                // Créer le MediaObject pour le fichier
                $mediaObject = new MediaObject();
                $mediaObject->setFile($file);
                $mediaObject->setMapping('prescription_files');

                // Upload via le processor MediaObject
                $uploadedMediaObject = $this->mediaObjectProcessor->process($mediaObject, $operation, $uriVariables, $context);

                // Associer le fichier à la prescription
                $this->prescriptionService->associateFileToPrescription($prescription, $uploadedMediaObject);

                return $prescription;
            }
        }

        // Comportement par défaut pour les autres cas
        return $data;
    }

    /**
     * Vérifie si le fichier semble être une prescription (image)
     */
    private function isPrescriptionFile(UploadedFile $file): bool
    {
        $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

        return in_array($file->getMimeType(), $allowedMimeTypes, true);
    }
}