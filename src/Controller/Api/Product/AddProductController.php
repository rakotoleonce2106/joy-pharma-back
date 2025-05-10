<?php

declare(strict_types=1);

namespace App\Controller\Api\Product;

use App\Service\ProductService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AddProductController extends AbstractController
{
    public function __construct(
        private readonly ProductService $productService
    )
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            foreach ($data as $elt) {
                $this->productService->createProductFromJson($elt);
            }

            return new JsonResponse(['message' => 'products added ']);

        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

    }
}
