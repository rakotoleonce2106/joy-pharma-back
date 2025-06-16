<?php

declare(strict_types=1);

namespace App\Controller\Api\Product;

use App\Service\BrandService;
use App\Service\CategoryService;
use App\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductSummaryController extends AbstractController
{
    public function __construct(
        private readonly ProductService  $productService,
        private readonly CategoryService $categoryService,
        private readonly BrandService    $brandService
    ) {}

    public function __invoke(): JsonResponse
    {

        $categories = $this->categoryService->findParentCategories();
        
        $items = [];
        foreach ($categories as $category) {
            $products = $this->productService->fetchProductsByCat($category->getId());

            $item = [
                'category' => $category,
                'products' => $products
            ];
            $items[] = $item;
        }

        $topSells = $this->productService->fetchTopSellProducts();
        $brands = $this->brandService->findAll();

        return $this->json(
            [
                'categories' => $categories,
                'topSells' => $topSells,
                'items' => $items,
                'brands' => $brands
            ],
            context: [
                'groups' =>  ['id:read','category:read', 'product:read', 'image:read', 'brand:read']
            ]
        );
    }
}
