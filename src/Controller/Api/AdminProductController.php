<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminProductController extends AbstractController
{
    public function __construct(
        private readonly ProductService $productService
    ) {
    }

    #[Route('/api/admin/product/upload-json', name: 'api_admin_product_upload_json', methods: ['POST'])]
    public function uploadJsonAction(Request $request): JsonResponse
    {
        $jsonContent = $request->getContent();
        
        if (empty($jsonContent)) {
            return $this->json([
                'error' => 'JSON content is required.',
                'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                'title' => 'Bad Request'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $data = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
            
            if (!is_array($data)) {
                return $this->json([
                    'error' => 'JSON must be an array of products.',
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'Bad Request'
                ], Response::HTTP_BAD_REQUEST);
            }

            $count = 0;
            $errors = [];
            
            foreach ($data as $index => $elt) {
                try {
                    $this->productService->createProductFromJson($elt);
                    $count++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'message' => $e->getMessage()
                    ];
                }
            }

            $response = [
                'success' => true,
                'message' => count($errors) > 0 
                    ? "$count product(s) added successfully. " . count($errors) . " error(s) occurred."
                    : "$count product(s) have been successfully added.",
                'data' => [
                    'created' => $count,
                    'errors' => count($errors),
                    'errors_detail' => $errors
                ]
            ];

            $statusCode = count($errors) > 0 
                ? Response::HTTP_PARTIAL_CONTENT 
                : Response::HTTP_OK;

            return $this->json($response, $statusCode);
        } catch (\JsonException $e) {
            return $this->json([
                'error' => 'Invalid JSON format: ' . $e->getMessage(),
                'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                'title' => 'Bad Request'
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'An error occurred: ' . $e->getMessage(),
                'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                'title' => 'Internal Server Error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

