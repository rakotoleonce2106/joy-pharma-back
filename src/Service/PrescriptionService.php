<?php

namespace App\Service;

use App\Entity\MediaObject;
use App\Entity\Prescription;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\PrescriptionRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

class PrescriptionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PrescriptionRepository $prescriptionRepository,
        private readonly ProductRepository $productRepository,
        private readonly UserRepository $userRepository,
        private readonly OCRService $ocrService,
        private readonly LoggerInterface $logger,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly JWTTokenManagerInterface $jwtManager
    ) {}

    /**
     * Traite un fichier de prescription uploadé et crée une entité Prescription
     */
    public function processPrescriptionFile(UploadedFile $file): Prescription
    {
        // Essayer de récupérer l'utilisateur via le service Security
        $user = $this->security->getUser();

        // Debug: Log de l'état de sécurité
        $this->logger->info('PrescriptionService: Checking authentication', [
            'has_token_storage' => $this->security->getToken() ? 'yes' : 'no',
            'user_from_security' => $user ? 'yes' : 'no',
            'user_class' => $user ? get_class($user) : 'null',
            'user_id' => $user ? $user->getId() : 'null'
        ]);

        // Si l'utilisateur n'est pas disponible via Security, essayer via JWT
        if (!$user) {
            $user = $this->getUserFromJWT();
        }

        // Vérifier que l'utilisateur est authentifié
        if (!$user) {
            $this->logger->error('PrescriptionService: No authenticated user found via Security or JWT');
            throw new AccessDeniedException('Authentication required to upload prescriptions');
        }

        // Vérifier que c'est bien une instance UserInterface
        if (!$user instanceof UserInterface) {
            $this->logger->error('PrescriptionService: User is not instance of UserInterface', [
                'user_class' => get_class($user)
            ]);
            throw new AccessDeniedException('Invalid user authentication');
        }

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

    /**
     * Récupère l'utilisateur depuis le token JWT si Security ne fonctionne pas
     */
    private function getUserFromJWT(): ?UserInterface
    {
        try {
            $request = $this->requestStack->getCurrentRequest();
            if (!$request) {
                return null;
            }

            $authHeader = $request->headers->get('Authorization');
            if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
                return null;
            }

            $token = substr($authHeader, 7); // Remove 'Bearer ' prefix

            // Décoder le token pour récupérer l'email
            $payload = $this->jwtManager->decode($token);
            if (!isset($payload['username'])) {
                return null;
            }

            // Récupérer l'utilisateur depuis la base de données
            $user = $this->userRepository->findOneBy(['email' => $payload['username']]);

            $this->logger->info('PrescriptionService: User retrieved from JWT', [
                'user_id' => $user ? $user->getId() : 'null',
                'email' => $payload['username']
            ]);

            return $user;
        } catch (\Exception $e) {
            $this->logger->error('PrescriptionService: Error retrieving user from JWT', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}