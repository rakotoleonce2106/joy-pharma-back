<?php

namespace App\Service;


use App\Entity\Category;
use App\Entity\Price;
use App\Entity\Product;
use App\Entity\Quantity;
use App\Entity\Unit;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

readonly class  ProductService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ProductRepository      $productRepository,
        private CategoryService        $categoryService,
        private MediaFileService       $fileService,
        private FormService            $formService,
        private BrandService           $brandService,
        private ManufacturerService    $manufacturerService,
        private UnitService            $unitService,
        private PriceService           $priceService,
        private QuantityService        $quantityService,
    )
    {

    }

    public function createProduct(Product $product): void
    {
        $this->manager->persist($product);
        $this->manager->flush();

    }

    /**
     * @throws Exception
     */
    public function createProductFromJson($productData): void
    {
        $product = new Product();
        $product->setName($productData['title']);
        $product->setDescription($productData['description']);

        $product->setIsActive(true);

        // Handle Categories
        if (array_key_exists('categories', $productData) && $productData['categories']) {

            if (in_array('Home', $productData['categories'], true)) {
                $productData['categories'] = array_filter($productData['categories'], static function ($category) {
                    return $category !== 'Home';
                });
            }
            if (!empty($productData['categories'])) {
                $categories = $this->categoryService->getOrCreateCategoryByPath($productData['categories']);

                foreach ($categories as $elt) {
                    $product->addCategory($elt);
                }
            }
        }

        // Handle Image
        if (array_key_exists('images', $productData) && $productData['images']) {
            foreach ($productData['images'] as $elt) {
                $mediaFile = $this->fileService->createMediaFileByUrl($elt);
                $product->addImage($mediaFile);
            }

        }

        if (array_key_exists('details', $productData) && $productData['details']) {

            if (array_key_exists('code_ean', $productData['details'])) {
                $product->setCode($productData['details']['code_ean']);
            }
            if (array_key_exists('forme', $productData['details'])) {
                $form = $this->formService->getOrCreateFormByName($productData['details']['forme']);
                $product->setForm($form);
            }
            if (array_key_exists('marque', $productData['details'])) {
                $brand = $this->brandService->getOrCreateBrandByName($productData['details']['marque']);
                $product->setBrand($brand);
            }
            if (array_key_exists('fabricant', $productData['details'])) {
                $manufacturer = $this->manufacturerService->getOrCreateManufacturerByName($productData['details']['fabricant']);
                $product->setManufacturer($manufacturer);
            }
        }

        // Handle Image
        // Handle Image
        if (array_key_exists('price', $productData) && $productData['price']) {
            $unit = new Unit();
            $quantity = new Quantity();
            $price = new Price();

            // Check if 'quantity' exists and is not empty
            if (isset($productData['price']['quantity']) && preg_match('/(\d+)\s*x\s*(\d+)\s*(.+)/u', $productData['price']['quantity'], $matches)) {
                if (isset($matches[3], $matches[2])) {
                    $unit->setLabel(trim($matches[3]));
                    $quantity->setUnit($unit);
                    $totalCount = (float)$matches[1] * (float)$matches[2]; // Calcul 2x45 = 90
                    $quantity->setCount($totalCount);
                    $this->unitService->createUnit($unit);
                    $this->quantityService->createQuantity($quantity);
                }
            }

            $price->setQuantity($quantity);

            // Check if 'unitPrice' exists and is not empty
            if (isset($productData['price']['unitPrice']) && preg_match('/(\d+,\d+)\s*€\s*\/\s*(\d+)\s*(.+)/u', $productData['price']['unitPrice'], $matches)) {
                if (isset($matches[1])) {
                    $price->setUnitPrice((float)str_replace(',', '.', $matches[1]));
                }
            }

            // Check if 'totalPrice' exists and is not empty
            if (isset($productData['price']['totalPrice']) && preg_match('/€\s*([\d,\.]+)/u', $productData['price']['totalPrice'], $matches)) {
                if (isset($matches[1])) {
                    $price->setTotalPrice((float)str_replace(',', '.', $matches[1]));
                    $price->setCurrency('€');
                }
            }

            $product->setPrice($price);
            $this->priceService->createPrice($price);
        }

        $urls = array_column($productData['variants'], 'url');
        $product->setVariants($urls);

        $this->manager->persist($product);
        $this->manager->flush();
    }

    public function fetchTopSellProducts(): array
    {
       return $this->productRepository->findTopSells();

    }

    public function fetchProductsByCat(int $id): array
    {
        return $this->productRepository->findByCategory($id);

    }


    public function updateProduct(): void
    {
        $this->manager->flush();

    }

    public function batchDeleteProducts(array $productIds): array
    {
        $successCount = 0;
        $failureCount = 0;

        foreach ($productIds as $id) {
            $product = $this->productRepository->find($id);
            if ($product) {
                $this->deleteProduct($product);
            }
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount
        ];

    }

    public function deleteProduct(Product $product): void
    {
        $this->manager->remove($product);
        $this->manager->flush();

    }


}
