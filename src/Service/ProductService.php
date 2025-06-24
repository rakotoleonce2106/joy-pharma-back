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
        $temp= $this->productRepository->findOneBy(['name' => $productData['title']]);
        if ($temp) {
            return;
        }   
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
        // Handle Price
        if (!empty($productData['price']) && is_array($productData['price'])) {

            // Quantity: e.g. "7 pc(s)"
            if (!empty($productData['price']['quantity'])) {
                $quantityString = $productData['price']['quantity'];

                if (preg_match('/(\d+)\s*(.+)/u', $quantityString, $matches)) {
                    $quantity = (float) $matches[1];
                    $unitLabel = trim($matches[2]);

                    $unit = $this->unitService->getOrCreateUnit($unitLabel);
                    $product->setQuantity($quantity);
                    $product->setUnit($unit);
                }
            }

            // Unit Price: e.g. "0,28 € / 1 pc(s)"
            if (!empty($productData['price']['unitPrice'])) {
                $unitPriceString = $productData['price']['unitPrice'];

                if (preg_match('/([\d,.]+)\s*€\s*\/\s*[\d]+\s*(.+)/u', $unitPriceString, $matches)) {
                    $unitPrice = (float) str_replace(',', '.', str_replace(' ', '', $matches[1])); // remove non-breaking space
                    $product->setUnitPrice($unitPrice);
                }
            }

            // Total Price: e.g. "€ 1,99"
            if (!empty($productData['price']['totalPrice'])) {
                $totalPriceString = $productData['price']['totalPrice'];

                if (preg_match('/€\s*([\d,\.]+)/u', $totalPriceString, $matches)) {
                    $totalPrice = (float) str_replace(',', '.', str_replace(' ', '', $matches[1]));
                    $product->setTotalPrice($totalPrice);

                    $currency = $this->currencyService->getOrCreateCurrency('€');
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
