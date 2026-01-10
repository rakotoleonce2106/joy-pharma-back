<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class OCRService
{
    private string $ocrApiUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $ocrApiUrl = null
    ) {
        $this->ocrApiUrl = $ocrApiUrl ?? $_ENV['OCR_API_URL'] ?? 'https://lwww.joy-pharma.com/transcribe';
    }

    /**
     * Envoie une image de prescription à l'API OCR et retourne les données extraites
     *
     * @param UploadedFile $file Le fichier image de la prescription
     * @return array Les données extraites (patient_nom, facture_date, total_final_ar, articles)
     * @throws \Exception Si l'appel à l'API échoue
     */
    public function transcribePrescription(UploadedFile $file): array
    {
        try {
            $this->logger->info('Sending prescription to OCR service', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);

            // Construire le body multipart/form-data manuellement
            $boundary = '----WebKitFormBoundary' . uniqid();
            $eol = "\r\n";
            
            // Lire le contenu du fichier
            $fileContent = file_get_contents($file->getPathname());
            if ($fileContent === false) {
                throw new \Exception('Impossible de lire le fichier uploadé');
            }

            // Construire le body
            $body = '';
            $body .= '--' . $boundary . $eol;
            $body .= 'Content-Disposition: form-data; name="file"; filename="' . 
                     addslashes($file->getClientOriginalName()) . '"' . $eol;
            $body .= 'Content-Type: ' . $file->getMimeType() . $eol . $eol;
            $body .= $fileContent . $eol;
            $body .= '--' . $boundary . '--' . $eol;

            $response = $this->httpClient->request('POST', $this->ocrApiUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
                ],
                'body' => $body,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent();

            if ($statusCode !== 200) {
                throw new \Exception("L'API OCR a retourné une erreur: HTTP $statusCode - $content");
            }

            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Réponse invalide de l\'API OCR: ' . json_last_error_msg());
            }

            $this->logger->info('OCR service response received', [
                'status_code' => $statusCode,
                'has_patient_nom' => isset($data['patient_nom']),
                'articles_count' => isset($data['articles']) ? count($data['articles']) : 0
            ]);

            return $data;
        } catch (\Exception $e) {
            $this->logger->error('OCR service error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Erreur lors de la transcription de la prescription: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Extrait les titres des produits depuis les articles de la prescription
     *
     * @param array $ocrData Les données retournées par l'API OCR
     * @return array Liste des titres de produits
     */
    public function extractProductTitles(array $ocrData): array
    {
        $titles = [];
        
        if (!isset($ocrData['articles']) || !is_array($ocrData['articles'])) {
            return $titles;
        }

        foreach ($ocrData['articles'] as $article) {
            // Chercher différents champs possibles pour le titre du produit
            if (isset($article['title']) && !empty($article['title'])) {
                $titles[] = trim($article['title']);
            } elseif (isset($article['nom']) && !empty($article['nom'])) {
                $titles[] = trim($article['nom']);
            } elseif (isset($article['name']) && !empty($article['name'])) {
                $titles[] = trim($article['name']);
            } elseif (isset($article['libelle']) && !empty($article['libelle'])) {
                $titles[] = trim($article['libelle']);
            }
        }

        // Supprimer les doublons et les valeurs vides
        $titles = array_filter(array_unique($titles));
        
        return array_values($titles);
    }
}

