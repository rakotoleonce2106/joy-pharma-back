<?php

namespace App\Service;


use App\Entity\Category;
use App\Entity\Currency;
use App\Entity\MediaObject;
use App\Entity\Price;
use App\Entity\Product;
use App\Entity\Quantity;
use App\Entity\Unit;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class  ProductService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ProductRepository      $productRepository,
        private CategoryService        $categoryService,
        private string                 $projectDir,
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

    public function createMediaObject(MediaObject $mediaObject): void
    {
        $this->manager->persist($mediaObject);
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
                $imageUrl = $this->extractImageUrl($elt);
                if ($imageUrl) {
                    // Par défaut, ne pas copier les images - juste les référencer (false)
                    // Changez en true si vous voulez copier les images dans /media/
                    $mediaObject = $this->createMediaObjectFromUrl($imageUrl, false);
                    if ($mediaObject) {
                        $product->addImage($mediaObject);
                    }
                }
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


    public function updateProduct(Product $product): void
    {
        $this->manager->flush();
    }

    public function batchDeleteProducts(array $productIds): array
    {
        $successCount = 0;
        $failureCount = 0;

        if (empty($productIds)) {
            return [
                'success_count' => 0,
                'failure_count' => 0
            ];
        }

        foreach ($productIds as $id) {
            try {
                $product = $this->productRepository->find($id);
                if ($product) {
                    $this->deleteProductRelations($product);
                    $this->manager->remove($product);
                    $successCount++;
                } else {
                    $failureCount++;
                }
            } catch (\Exception $e) {
                $failureCount++;
                // Log the error but continue with other products
                error_log('Failed to delete product with id ' . $id . ': ' . $e->getMessage());
            }
        }

        // Flush once at the end for better performance
        try {
            $this->manager->flush();
        } catch (\Exception $e) {
            error_log('Failed to flush product deletions: ' . $e->getMessage());
            throw $e;
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount
        ];
    }

    public function deleteProduct(Product $product): void
    {
        $this->deleteProductRelations($product);
        $this->manager->remove($product);
        $this->manager->flush();
    }

    /**
     * Delete all relations of a product before deleting the product itself
     */
    private function deleteProductRelations(Product $product): void
    {
        // Remove all category associations (ManyToMany)
        // Use query to ensure we get all categories even if collection is not initialized
        $categories = $this->manager->getRepository(\App\Entity\Category::class)
            ->createQueryBuilder('c')
            ->innerJoin('c.products', 'p')
            ->where('p.id = :productId')
            ->setParameter('productId', $product->getId())
            ->getQuery()
            ->getResult();
        
        foreach ($categories as $category) {
            $product->removeCategory($category);
        }

        // Remove all restricted associations (ManyToMany)
        // Use query to ensure we get all restricted even if collection is not initialized
        try {
            $restricted = $this->manager->getRepository(\App\Entity\Restricted::class)
                ->createQueryBuilder('r')
                ->innerJoin('r.products', 'p')
                ->where('p.id = :productId')
                ->setParameter('productId', $product->getId())
                ->getQuery()
                ->getResult();
            
            foreach ($restricted as $restrictedItem) {
                $product->removeRestricted($restrictedItem);
            }
        } catch (\Exception $e) {
            // Restricted might not exist or have different structure, continue
            error_log('Failed to remove restricted associations: ' . $e->getMessage());
        }

        // Remove all order items (OneToMany - no cascade)
        $orderItems = $this->manager->getRepository(\App\Entity\OrderItem::class)
            ->createQueryBuilder('oi')
            ->where('oi.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getResult();
        
        foreach ($orderItems as $orderItem) {
            $this->manager->remove($orderItem);
        }

        // Remove all store products (OneToMany - no cascade)
        $storeProducts = $this->manager->getRepository(\App\Entity\StoreProduct::class)
            ->createQueryBuilder('sp')
            ->where('sp.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getResult();
        
        foreach ($storeProducts as $storeProduct) {
            $this->manager->remove($storeProduct);
        }

        // Remove all favorites (OneToMany - no cascade)
        $favorites = $this->manager->getRepository(\App\Entity\Favorite::class)
            ->createQueryBuilder('f')
            ->where('f.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getResult();
        
        foreach ($favorites as $favorite) {
            $this->manager->remove($favorite);
        }


        // Remove all images associations (ManyToMany)
        // The MediaObject entities themselves are not deleted as they might be used elsewhere
        // Just clear the collection - Doctrine will handle the join table
        $product->getImages()->clear();
    }

    /**
     * Extract image URL from various formats:
     * - Simple string URL
     * - Complex object with @type, @id, image, id
     * - Direct image object with name, size, mimeType, url
     */
    private function extractImageUrl($imageData): ?string
    {
        // If it's already a string, return it
        if (is_string($imageData)) {
            return $imageData;
        }
        
        // If it's an array/object with 'image' property
        if (is_array($imageData) || is_object($imageData)) {
            $data = is_array($imageData) ? $imageData : (array) $imageData;
            
            // Check if it has an 'image' property (complex object)
            if (isset($data['image'])) {
                $image = $data['image'];
                // If image is an array/object, extract the URL
                if (is_array($image) || is_object($image)) {
                    $imageArray = is_array($image) ? $image : (array) $image;
                    // Get URL from image object
                    if (isset($imageArray['url'])) {
                        return $imageArray['url'];
                    }
                    // If no URL but has name, construct it
                    if (isset($imageArray['name'])) {
                        return '/media/' . $imageArray['name'];
                    }
                }
            }
            
            // Check if it's a direct image object with 'url' property
            if (isset($data['url'])) {
                return $data['url'];
            }
            
            // Check if it has 'name' property (direct image object)
            if (isset($data['name'])) {
                return '/media/' . $data['name'];
            }
            
            // Check if it's an IRI like "/media_objects/123"
            if (isset($data['@id'])) {
                $mediaObject = $this->getMediaObjectFromIri($data['@id']);
                if ($mediaObject) {
                    return $mediaObject->contentUrl ?? ('/media/' . $mediaObject->getFilePath());
                }
                return $data['@id'];
            }
        }
        
        return null;
    }

    /**
     * Get MediaObject from IRI string (e.g., "/media_objects/123" or "/api/media_objects/123")
     */
    private function getMediaObjectFromIri(string $iri): ?MediaObject
    {
        // Extract ID from IRI
        if (preg_match('#/media_objects/(\d+)#', $iri, $matches)) {
            $id = (int) $matches[1];
            return $this->manager->getRepository(MediaObject::class)->find($id);
        }
        
        return null;
    }

    /**
     * Create a MediaObject from an image URL
     * 
     * @param bool $copyFile If true, copy the file to VichUpload directory. If false, just reference it.
     */
    private function createMediaObjectFromUrl(string $imageUrl, bool $copyFile = false): ?MediaObject
    {
        // Check if it's an IRI to an existing MediaObject
        if (preg_match('#/media_objects/(\d+)#', $imageUrl, $matches)) {
            return $this->getMediaObjectFromIri($imageUrl);
        }
        
        // Extract the filename from the URL (works for both URLs and paths)
        $parsedPath = parse_url($imageUrl, PHP_URL_PATH);
        $fileName = basename($parsedPath);
        
        // Check if image exists in products directories
        $localPath = $this->findExistingImageInPublic($imageUrl);
        
        // If found in /public and copyFile is false, create a reference MediaObject
        if ($localPath && !$copyFile) {
            // Log pour debug
            error_log(sprintf('[ProductService] Image trouvée localement: %s -> %s (référence)', $fileName, $localPath));
            return $this->createMediaObjectReference($localPath);
        }
        
        // Otherwise, proceed with the original copy/download logic
        if ($localPath) {
            error_log(sprintf('[ProductService] Image trouvée localement: %s -> copie dans /media/', $fileName));
        } elseif (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            error_log(sprintf('[ProductService] Image externe: %s -> téléchargement', $imageUrl));
        } else {
            error_log(sprintf('[ProductService] Image introuvable: %s', $imageUrl));
        }
        
        return $this->createMediaObjectWithCopy($imageUrl, $localPath);
    }

    /**
     * Find an existing image in the /public directory
     * Supports both local paths and external URLs with filename matching
     */
    private function findExistingImageInPublic(string $imageUrl): ?string
    {
        $parsedPath = parse_url($imageUrl, PHP_URL_PATH);
        $fileName = basename($parsedPath);
        
        // Directories to check
        $mediaDir = $this->projectDir . '/public/media';
        $productsDir = $this->projectDir . '/public/images/products';
        $productDir = $this->projectDir . '/public/images/product';
        
        // First priority: exact filename match in products directories
        $possiblePaths = [
            $productsDir . '/' . $fileName,
            $productDir . '/' . $fileName,
            $mediaDir . '/' . $fileName,
        ];
        
        foreach ($possiblePaths as $possiblePath) {
            if (file_exists($possiblePath) && is_readable($possiblePath)) {
                return $possiblePath;
            }
        }
        
        // Second: try full path resolution for local paths
        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            if (file_exists($this->projectDir . '/public' . $parsedPath)) {
                return $this->projectDir . '/public' . $parsedPath;
            }
            
            if (file_exists($this->projectDir . $parsedPath)) {
                return $this->projectDir . $parsedPath;
            }
        }
        
        // Third: try with different extensions
        $baseName = pathinfo($fileName, PATHINFO_FILENAME);
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        
        foreach ([$productsDir, $productDir, $mediaDir] as $dir) {
            foreach ($extensions as $ext) {
                $testPath = $dir . '/' . $baseName . '.' . $ext;
                if (file_exists($testPath) && is_readable($testPath)) {
                    return $testPath;
                }
            }
        }
        
        // Fourth: case-insensitive filename search (slower but comprehensive)
        if (!empty($baseName)) {
            foreach ([$productsDir, $productDir, $mediaDir] as $dir) {
                if (!is_dir($dir)) {
                    continue;
                }
                
                $files = @scandir($dir);
                if ($files === false) {
                    continue;
                }
                
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }
                    
                    // Case-insensitive comparison of the full filename
                    if (strcasecmp($fileName, $file) === 0) {
                        $testPath = $dir . '/' . $file;
                        if (file_exists($testPath) && is_readable($testPath)) {
                            return $testPath;
                        }
                    }
                    
                    // Case-insensitive comparison of base name only
                    $fileBaseName = pathinfo($file, PATHINFO_FILENAME);
                    if (strcasecmp($baseName, $fileBaseName) === 0) {
                        $testPath = $dir . '/' . $file;
                        if (file_exists($testPath) && is_readable($testPath)) {
                            return $testPath;
                        }
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * Create a MediaObject that references an existing file without copying it
     */
    private function createMediaObjectReference(string $absolutePath): MediaObject
    {
        // Convert absolute path to relative URL path (starting with /)
        $publicDir = $this->projectDir . '/public';
        $relativePath = str_replace($publicDir, '', $absolutePath);
        $relativePath = '/' . ltrim($relativePath, '/');
        
        $mediaObject = new MediaObject();
        $mediaObject->setFilePath($relativePath);
        $mediaObject->setIsExternalReference(true);
        
        $this->manager->persist($mediaObject);
        $this->manager->flush();
        
        return $mediaObject;
    }

    /**
     * Create a MediaObject by copying/downloading the file to /images/products/
     * Preserve the original filename
     */
    private function createMediaObjectWithCopy(string $imageUrl, ?string $existingLocalPath = null): ?MediaObject
    {
        try {
            $parsedPath = parse_url($imageUrl, PHP_URL_PATH);
            $originalFileName = basename($parsedPath);
            
            // Destination directory for product images
            $productsDir = $this->projectDir . '/public/images/products';
            if (!file_exists($productsDir)) {
                if (!mkdir($productsDir, 0755, true) && !is_dir($productsDir)) {
                    error_log('[ProductService] Failed to create products directory');
                    return null;
                }
            }
            
            // Destination path with original filename
            $destinationPath = $productsDir . '/' . $originalFileName;
            
            // If file already exists at destination, just create reference
            if (file_exists($destinationPath)) {
                error_log(sprintf('[ProductService] File already exists at destination: %s (référence)', $originalFileName));
                return $this->createMediaObjectReference($destinationPath);
            }
            
            $sourceFilePath = null;
            
            // If file found locally, use it directly
            if ($existingLocalPath && file_exists($existingLocalPath)) {
                $sourceFilePath = $existingLocalPath;
                error_log(sprintf('[ProductService] Copying local file: %s -> %s', $existingLocalPath, $destinationPath));
            }
            // If it's a URL, download it
            elseif (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                error_log(sprintf('[ProductService] Downloading image: %s', $imageUrl));
                
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 30,
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'follow_location' => true,
                        'max_redirects' => 5
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ]
                ]);
                
                $content = @file_get_contents($imageUrl, false, $context);
                if ($content === false) {
                    error_log('[ProductService] Failed to download image from: ' . $imageUrl);
                    return null;
                }
                
                if (empty($content) || strlen($content) < 100) {
                    error_log('[ProductService] Downloaded image is too small or empty: ' . $imageUrl);
                    return null;
                }
                
                // Write directly to destination
                if (file_put_contents($destinationPath, $content) === false) {
                    error_log('[ProductService] Failed to save image to: ' . $destinationPath);
                    return null;
                }
                
                error_log(sprintf('[ProductService] Image downloaded and saved: %s', $originalFileName));
                return $this->createMediaObjectReference($destinationPath);
            } else {
                error_log('[ProductService] Invalid image URL: ' . $imageUrl);
                return null;
            }
            
            // Copy the local file to destination
            if ($sourceFilePath) {
                if (!copy($sourceFilePath, $destinationPath)) {
                    error_log('[ProductService] Failed to copy image to: ' . $destinationPath);
                    return null;
                }
                
                error_log(sprintf('[ProductService] Image copied successfully: %s', $originalFileName));
                return $this->createMediaObjectReference($destinationPath);
            }
            
            return null;
        } catch (\Exception $e) {
            error_log('[ProductService] Error processing image ' . $imageUrl . ': ' . $e->getMessage());
            return null;
        }
    }
}
