<?php

namespace App\Service;


use App\Entity\Category;
use App\Entity\Currency;
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
        private CurrencyService           $currencyService,
    ) {}

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
            
            if (isset($productData['price']['quantity'])) {
                
                $quantityString = $productData['price']['quantity'];
                // Exemple : "2 x 45 pc(s)"
                if (preg_match('/(\d+)\s*x\s*(\d+)\s*(.+)/u', $quantityString, $matches)) {
                    $unitLabel = trim($matches[3]);
                    $unit = $this->unitService->getOrCreateUnit($unitLabel);
                    $product->setUnit($unit);
                    $count = (float)$matches[1] * (float)$matches[2];
                    $product->setQuantity($count);
                }
                // Exemple : "12 pc(s)"
                elseif (preg_match('/(\d+)\s*(.+)/u', $quantityString, $matches)) {
                    $unitLabel = trim($matches[2]);
                    $unit= $this->unitService->getOrCreateUnit($unitLabel);
                    $$product->setUnit($unit);
                    $product->setQuantity((float)$matches[1]);
                } 
            }
            
            if (
                isset($productData['price']['unitPrice']) &&
                preg_match('/([\d,]+)\s*€\s*\/\s*([\d]+)\s*(.+)/u', $productData['price']['unitPrice'], $matches)
            ) {
                $unitPriceValue = (float)str_replace(',', '.', $matches[1]);
                $product->setUnitPrice($unitPriceValue);
            }

            if (isset($productData['price']['totalPrice']) && preg_match('/€\s*([\d,\.]+)/u', $productData['price']['totalPrice'], $matches)) {
                if (isset($matches[1])) {
                    $product->setTotalPrice((float)str_replace(',', '.', $matches[1]));
                    $currency= $this->currencyService->getOrCreateCurrency('€');
                    $product->setCurrency($currency);
                }
            }
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
