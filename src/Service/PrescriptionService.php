<?php

namespace App\Service;

use App\Entity\MediaObject;
use App\Entity\Prescription;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\PrescriptionRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;

class PrescriptionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PrescriptionRepository $prescriptionRepository,
        private readonly ProductRepository $productRepository,
        private readonly OCRService $ocrService,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Traite un fichier de prescription uploadé et crée une entité Prescription
     */
    public function processPrescriptionFile(UploadedFile $file, UserInterface $user): Prescription
    {
        $this->logger->info('Processing prescription file', [
            'filename' => $file->getClientOriginalName(),
            'user_id' => $user->getId(),
            'size' => $file->getSize()
        ]);

        try {
            // Étape 1: OCR - extraire les données de la prescription
            $ocrData = $this->ocrService->transcribePrescription($file);

            // Étape 2: Extraire les noms de produits
            $productTitles = $this->ocrService->extractProductTitles($ocrData);

            // Étape 3: Rechercher les produits dans la base de données
            $foundProducts = [];
            if (!empty($productTitles)) {
                $foundProducts = $this->productRepository->searchByNames($productTitles, 10);
            }

            // Étape 4: Créer l'entité Prescription
            $prescription = new Prescription();
            $prescription->setTitle($this->generatePrescriptionTitle($ocrData));
            $prescription->setUser($user);

            // Ajouter les notes avec les données OCR
            $notes = $this->generatePrescriptionNotes($ocrData, $productTitles, $foundProducts);
            $prescription->setNotes($notes);

            // Étape 5: Ajouter les produits trouvés
            foreach ($foundProducts as $product) {
                $prescription->addProduct($product);
            }

            // Étape 6: Sauvegarder en base
            $this->entityManager->persist($prescription);
            $this->entityManager->flush();

            $this->logger->info('Prescription created successfully', [
                'prescription_id' => $prescription->getId(),
                'user_id' => $user->getId(),
                'products_found' => count($foundProducts),
                'products_searched' => count($productTitles)
            ]);

            return $prescription;

        } catch (\Exception $e) {
            $this->logger->error('Error processing prescription file', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId(),
                'filename' => $file->getClientOriginalName()
            ]);

            throw $e;
        }
    }

    /**
     * Associe un fichier MediaObject à une prescription existante
     */
    public function associateFileToPrescription(Prescription $prescription, MediaObject $mediaObject): void
    {
        $prescription->setPrescriptionFile($mediaObject);
        $this->entityManager->flush();

        $this->logger->info('Prescription file associated', [
            'prescription_id' => $prescription->getId(),
            'media_object_id' => $mediaObject->getId()
        ]);
    }

    /**
     * Génère un titre pour la prescription basé sur les données OCR
     */
    private function generatePrescriptionTitle(array $ocrData): string
    {
        $date = $ocrData['facture_date'] ?? date('d/m/Y');
        $patient = $ocrData['patient_nom'] ?? 'Patient';

        return "Ordonnance - {$patient} - {$date}";
    }

    /**
     * Génère les notes de la prescription avec les données OCR
     */
    private function generatePrescriptionNotes(array $ocrData, array $productTitles, array $foundProducts): string
    {
        $notes = [];

        if (!empty($ocrData['patient_nom'])) {
            $notes[] = "Patient: " . $ocrData['patient_nom'];
        }

        if (!empty($ocrData['facture_date'])) {
            $notes[] = "Date: " . $ocrData['facture_date'];
        }

        if (!empty($ocrData['total_final_ar'])) {
            $notes[] = "Total: " . $ocrData['total_final_ar'] . " Ar";
        }

        $notes[] = "Produits recherchés: " . count($productTitles);
        $notes[] = "Produits trouvés: " . count($foundProducts);

        if (!empty($productTitles)) {
            $notes[] = "Noms extraits: " . implode(', ', $productTitles);
        }

        return implode("\n", $notes);
    }
}