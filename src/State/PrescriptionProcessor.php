<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MediaObject;
use App\Entity\Prescription;
use App\Service\PrescriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

final class PrescriptionProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $mediaObjectProcessor,
        private readonly PrescriptionService $prescriptionService,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly Security $security
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
                // Étape 1: Extraire les données de la prescription (sans créer l'entité)
                $prescriptionData = $this->prescriptionService->processPrescriptionFile($file);

                // Étape 2: Récupérer l'utilisateur authentifié via Security
                $user = $this->security->getUser();
                dd($user, $prescriptionData);

                if (!$user instanceof UserInterface) {
                    throw new AccessDeniedException('Authentication required to upload prescriptions');
                }

                // Étape 3: Créer le MediaObject pour le fichier
                $mediaObject = new MediaObject();
                $mediaObject->setFile($file);
                $mediaObject->setMapping('prescription_files');

                // Upload via le processor MediaObject
                $uploadedMediaObject = $this->mediaObjectProcessor->process($mediaObject, $operation, $uriVariables, $context);

                // Étape 4: Créer la prescription avec les données extraites, l'utilisateur et le fichier
                $prescription = $this->prescriptionService->createPrescriptionFromData($prescriptionData, $user, $uploadedMediaObject);

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