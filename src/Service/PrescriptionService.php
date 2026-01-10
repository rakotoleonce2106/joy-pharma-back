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
     * Traite un fichier de prescription uploadé et retourne les données extraites (sans créer de Prescription)
     */
    public function processPrescriptionFile(UploadedFile $file): array
    {
        $this->logger->info('Processing prescription file for data extraction', [
            'filename' => $file->getClientOriginalName(),
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

            // Étape 4: Générer le titre et les notes
            $title = $this->generatePrescriptionTitle($ocrData);
            $notes = $this->generatePrescriptionNotes($ocrData, $productTitles, $foundProducts);

            $this->logger->info('Prescription data extracted successfully', [
                'title' => $title,
                'products_found' => count($foundProducts),
                'products_searched' => count($productTitles)
            ]);

            return [
                'title' => $title,
                'notes' => $notes,
                'products' => $foundProducts,
                'ocrData' => $ocrData,
                'productTitles' => $productTitles
            ];

        } catch (\Exception $e) {
            $this->logger->error('Error processing prescription file', [
                'error' => $e->getMessage(),
                'filename' => $file->getClientOriginalName()
            ]);

            throw $e;
        }
    }

    /**
     * Crée une Prescription à partir des données extraites
     */
    public function createPrescriptionFromData(array $prescriptionData, UserInterface $user, ?MediaObject $mediaObject = null): Prescription
    {
        $this->logger->info('Creating prescription from extracted data', [
            'user_id' => $user->getId(),
            'title' => $prescriptionData['title'],
            'products_count' => count($prescriptionData['products'])
        ]);

        // Créer l'entité Prescription
        $prescription = new Prescription();
        $prescription->setTitle($prescriptionData['title']);
        $prescription->setUser($user);
        $prescription->setNotes($prescriptionData['notes']);

        // Associer le fichier si fourni
        if ($mediaObject) {
            $prescription->setPrescriptionFile($mediaObject);
        }

        // Ajouter les produits trouvés
        foreach ($prescriptionData['products'] as $product) {
            $prescription->addProduct($product);
        }

        // Sauvegarder en base
        $this->entityManager->persist($prescription);
        $this->entityManager->flush();

        $this->logger->info('Prescription created successfully', [
            'prescription_id' => $prescription->getId(),
            'user_id' => $user->getId(),
            'products_count' => count($prescriptionData['products'])
        ]);

        return $prescription;
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