<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\BatchDeleteInput;
use App\Service\ProductService;
use App\Service\CategoryService;
use App\Service\BrandService;
use App\Service\ManufacturerService;
use App\Service\FormService;
use App\Service\UnitService;
use App\Service\StoreService;
use App\Service\OrderService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BatchDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly CategoryService $categoryService,
        private readonly BrandService $brandService,
        private readonly ManufacturerService $manufacturerService,
        private readonly FormService $formService,
        private readonly UnitService $unitService,
        private readonly StoreService $storeService,
        private readonly OrderService $orderService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        if (!$data instanceof BatchDeleteInput) {
            throw new BadRequestHttpException('Invalid input data');
        }

        // Determine resource type from operation name or URI
        $resourceType = $this->extractResourceType($operation, $context);
        
        $result = match($resourceType) {
            'product' => $this->productService->batchDeleteProducts($data->ids),
            'category' => $this->categoryService->batchDeleteCategories($data->ids),
            'brand' => $this->brandService->batchDeleteBrands($data->ids),
            'manufacturer' => $this->manufacturerService->batchDeleteManufacturers($data->ids),
            'form' => $this->formService->batchDeleteForms($data->ids),
            'unit' => $this->unitService->batchDeleteUnits($data->ids),
            'store' => $this->storeService->batchDeleteStores($data->ids),
            'order' => $this->orderService->batchDeleteOrders($data->ids),
            default => throw new BadRequestHttpException('Unknown resource type')
        };

        return [
            'success' => true,
            'success_count' => $result['success_count'] ?? 0,
            'failure_count' => $result['failure_count'] ?? 0,
            'message' => sprintf(
                '%d item(s) deleted successfully. %d item(s) could not be deleted.',
                $result['success_count'] ?? 0,
                $result['failure_count'] ?? 0
            )
        ];
    }

    private function extractResourceType(Operation $operation, array $context): string
    {
        // Try to extract from URI
        $uri = $operation->getUriTemplate() ?? '';
        if (preg_match('/\/(\w+)\/batch-delete/', $uri, $matches)) {
            return $matches[1];
        }

        // Try to extract from context
        if (isset($context['resource_class'])) {
            $class = $context['resource_class'];
            if (str_contains($class, 'Product')) return 'product';
            if (str_contains($class, 'Category')) return 'category';
            if (str_contains($class, 'Brand')) return 'brand';
            if (str_contains($class, 'Manufacturer')) return 'manufacturer';
            if (str_contains($class, 'Form')) return 'form';
            if (str_contains($class, 'Unit')) return 'unit';
            if (str_contains($class, 'Store')) return 'store';
            if (str_contains($class, 'Order')) return 'order';
        }

        throw new BadRequestHttpException('Could not determine resource type');
    }
}

