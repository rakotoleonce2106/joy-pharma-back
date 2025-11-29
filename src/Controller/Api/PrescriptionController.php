<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\ProductRepository;
use App\Service\OCRService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class PrescriptionController extends AbstractController
{
    public function __construct(
        private readonly OCRService $ocrService,
        private readonly ProductRepository $productRepository
    ) {
    }

    #[Route('/api/prescription/upload', name: 'api_prescription_upload', methods: ['POST'])]
    public function uploadPrescription(Request $request): JsonResponse
    {
        try {
            // Vérifier qu'un fichier a été uploadé
            $file = $request->files->get('file');
            
            if (!$file instanceof UploadedFile) {
                return $this->json([
                    'error' => 'Aucun fichier fourni. Veuillez envoyer un fichier image avec la clé "file".',
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'Bad Request'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier le type MIME du fichier
            $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file->getMimeType(), $allowedMimeTypes, true)) {
                return $this->json([
                    'error' => 'Type de fichier non supporté. Veuillez envoyer une image (JPEG, PNG, GIF, WebP).',
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'Bad Request'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier la taille du fichier (max 10MB)
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($file->getSize() > $maxSize) {
                return $this->json([
                    'error' => 'Le fichier est trop volumineux. Taille maximale: 10MB.',
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'Bad Request'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Appeler le service OCR pour extraire les données de la prescription
            $ocrData = $this->ocrService->transcribePrescription($file);

            // Extraire les titres des produits
            $productTitles = $this->ocrService->extractProductTitles($ocrData);

            // Rechercher les produits dans la base de données
            $foundProducts = [];
            if (!empty($productTitles)) {
                $foundProducts = $this->productRepository->searchByNames($productTitles, 10);
            }

            // Préparer la réponse avec les objets Product directement
            $response = [
                'prescription' => [
                    'patient_nom' => $ocrData['patient_nom'] ?? null,
                    'facture_date' => $ocrData['facture_date'] ?? null,
                    'total_final_ar' => $ocrData['total_final_ar'] ?? null,
                    'articles' => $ocrData['articles'] ?? [],
                ],
                'extracted_products' => $productTitles,
                'found_products' => $foundProducts,
                'statistics' => [
                    'total_articles' => count($ocrData['articles'] ?? []),
                    'extracted_titles' => count($productTitles),
                    'found_products_count' => count($foundProducts),
                ]
            ];

            // Retourner la réponse avec sérialisation automatique des objets Product
            return $this->json(
                $response,
                Response::HTTP_OK,
                [],
                ['groups' => ['product:read', 'image:read', 'media_object:read', 'id:read']]
            );
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors du traitement de la prescription: ' . $e->getMessage(),
                'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                'title' => 'Internal Server Error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}

