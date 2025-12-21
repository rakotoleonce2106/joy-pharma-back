<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\ProductInput;
use App\Entity\MediaObject;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\BrandRepository;
use App\Repository\ManufacturerRepository;
use App\Repository\FormRepository;
use App\Repository\UnitRepository;
use App\Repository\ProductRepository;
use App\Service\MediaObjectService;
use App\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly BrandRepository $brandRepository,
        private readonly ManufacturerRepository $manufacturerRepository,
        private readonly FormRepository $formRepository,
        private readonly UnitRepository $unitRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaObjectService $mediaObjectService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Product
    {
        if (!$data instanceof ProductInput) {
            throw new BadRequestHttpException('Invalid input data');
        }

        $isUpdate = isset($uriVariables['id']);
        
        if ($isUpdate) {
            $product = $this->productRepository->find($uriVariables['id']);
            if (!$product) {
                throw new NotFoundHttpException('Product not found');
            }
        } else {
            // Check if code already exists
            $existingProduct = $this->productRepository->findOneBy(['code' => $data->code]);
            if ($existingProduct) {
                throw new BadRequestHttpException('Product with this code already exists');
            }
            
            $product = new Product();
        }

        // Update product properties
        $product->setName($data->name);
        $product->setCode($data->code);
        $product->setDescription($data->description);
        $product->setIsActive($data->isActive);
        $product->setQuantity($data->quantity);
        $product->setUnitPrice($data->unitPrice);
        $product->setTotalPrice($data->totalPrice);
        $product->setStock($data->stock);
        $product->setCurrency($data->currency);
        $product->setVariants($data->variants);

        // Handle relations
        if ($data->form) {
            $form = $this->formRepository->find($data->form);
            if (!$form) {
                throw new BadRequestHttpException('Form not found');
            }
            $product->setForm($form);
        }

        if ($data->brand) {
            $brand = $this->brandRepository->find($data->brand);
            if (!$brand) {
                throw new BadRequestHttpException('Brand not found');
            }
            $product->setBrand($brand);
        }

        if ($data->manufacturer) {
            $manufacturer = $this->manufacturerRepository->find($data->manufacturer);
            if (!$manufacturer) {
                throw new BadRequestHttpException('Manufacturer not found');
            }
            $product->setManufacturer($manufacturer);
        }

        if ($data->unit) {
            $unit = $this->unitRepository->find($data->unit);
            if (!$unit) {
                throw new BadRequestHttpException('Unit not found');
            }
            $product->setUnit($unit);
        }

        // Handle categories
        $product->getCategory()->clear();
        foreach ($data->categories as $categoryId) {
            $category = $this->categoryRepository->find($categoryId);
            if ($category) {
                $product->addCategory($category);
            }
        }

        // Handle images: API Platform automatically deserializes IRI array to MediaObject entities (JSON-LD)
        $needsFlush = false;
        
        // Store previous image IDs before updating
        $previousImageIds = $product->getImages()->map(fn($img) => $img->getId())->toArray();
        
        // Clear existing images and add new ones
        $product->getImages()->clear();
        
        if (!empty($data->images) && is_array($data->images)) {
            // MediaObject entities from JSON-LD request - API Platform deserialized IRIs automatically
            foreach ($data->images as $imageMediaObject) {
                if ($imageMediaObject instanceof MediaObject) {
                    $imageMediaObject->setMapping('product_images');
                    $product->addImage($imageMediaObject);
                    $needsFlush = true;
                }
            }
        }

        // Flush MediaObjects if any were created so VichUploader can process the uploads
        if ($needsFlush) {
            $this->entityManager->flush();
        }

        // Delete previous MediaObjects that are no longer referenced
        $currentImageIds = $product->getImages()->map(fn($img) => $img->getId())->toArray();
        $idsToDelete = array_diff($previousImageIds, $currentImageIds);
        if (!empty($idsToDelete)) {
            $this->mediaObjectService->deleteMediaObjectsByIds($idsToDelete);
        }

        if ($isUpdate) {
            $this->productService->updateProduct($product);
        } else {
            $this->productService->createProduct($product);
        }

        return $product;
    }
}

