<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\ProductDataTableType;
use App\Entity\MediaObject;
use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\ProductService;
use App\Traits\ToastTrait;
use Doctrine\ORM\EntityManagerInterface;
use Kreyu\Bundle\DataTableBundle\DataTableFactoryAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductController extends AbstractController
{
    use DataTableFactoryAwareTrait;
    use ToastTrait;

    public  function __construct(
        private  readonly ProductRepository $productRepository,
        private readonly ProductService $productService,
        private readonly EntityManagerInterface $entityManager
    ) {}
    #[Route('/product', name: 'admin_product')]
    public function index(Request $request): Response
    {
        $query = $this->productRepository->createQueryBuilder('product');

        $datatable = $this->createNamedDataTable('products', ProductDataTableType::class, $query);
        $datatable->handleRequest($request);

        return $this->render('admin/product/index.html.twig', [
            'datatable' => $datatable->createView()
        ]);
    }

    #[Route('/product/new', name: 'admin_product_new', defaults: ['title' => 'Create product'])]
    public function createAction(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product, ['action' => $this->generateUrl('admin_product_new')]);
        return $this->handleCreate($request, $form, $product);
    }

    #[Route('/product/{id}/edit', name: 'admin_product_edit', defaults: ['title' => 'Edit product'])]
    public function editAction(Request $request, Product $product): Response
    {
        // Recharger le produit avec ses relations pour éviter les problèmes de lazy loading
        $product = $this->productRepository->createQueryBuilder('p')
            ->leftJoin('p.images', 'img')
            ->leftJoin('p.category', 'cat')
            ->leftJoin('p.brand', 'brand')
            ->leftJoin('p.manufacturer', 'manufacturer')
            ->leftJoin('p.form', 'form')
            ->leftJoin('p.unit', 'unit')
            ->addSelect('img', 'cat', 'brand', 'manufacturer', 'form', 'unit')
            ->where('p.id = :id')
            ->setParameter('id', $product->getId())
            ->getQuery()
            ->getOneOrNullResult();
        
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $form = $this->createForm(ProductType::class, $product, [
            'action' => $this->generateUrl('admin_product_edit', ['id' => $product->getId()])
        ]);
        return $this->handleUpdate($request, $form, $product);
    }

    #[Route('/product/{id}/delete', name: 'admin_product_delete', methods: ['POST', 'GET'])]
    public  function deleteAction(Product $product, Request $request): Response
    {
        // Only allow POST method for actual deletion
        if ($request->getMethod() !== 'POST') {
            $this->addWarningToast('Invalid request', 'Please use the delete button in the table to delete products.');
            return $this->redirectToRoute('admin_product', [], Response::HTTP_SEE_OTHER);
        }

        try {
            $this->productService->deleteProduct($product);
            $this->addSuccessToast('Product deleted!', 'The product has been successfully deleted.');
        } catch (\Exception $e) {
            $this->addErrorToast('Delete failed!', 'An error occurred while deleting the product: ' . $e->getMessage());
        }
        return $this->redirectToRoute('admin_product', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/product/batch-delete', name: 'admin_product_batch_delete', methods: ['POST'])]
    public function batchDeleteAction(Request $request): Response
    {
        try {
            $productIds = $request->request->all('id');
            
            if (empty($productIds)) {
                $this->addWarningToast('No products selected', 'Please select at least one product to delete.');
                return $this->redirectToRoute('admin_product', [], Response::HTTP_SEE_OTHER);
            }

            $result = $this->productService->batchDeleteProducts($productIds);

            if ($result['failure_count'] > 0) {
                $this->addWarningToast(
                    'Partial deletion!',
                    "{$result['success_count']} product(s) deleted successfully. {$result['failure_count']} product(s) could not be deleted."
                );
            } else {
                $this->addSuccessToast(
                    "Products deleted!",
                    "{$result['success_count']} product(s) have been successfully deleted."
                );
            }
        } catch (\Exception $e) {
            $this->addErrorToast('Delete failed!', 'An error occurred while deleting products: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_product', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/product/upload-json', name: 'admin_product_upload_json', defaults: ['title' => 'Upload Products JSON'])]
    public function uploadJsonAction(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $jsonContent = $request->request->get('json_content');
            
            if (empty($jsonContent)) {
                $this->addErrorToast('Error!', 'JSON content is required.');
                return $this->render('admin/product/upload_json.html.twig');
            }

            try {
                $data = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
                
                if (!is_array($data)) {
                    $this->addErrorToast('Error!', 'JSON must be an array of products.');
                    return $this->render('admin/product/upload_json.html.twig');
                }

                $count = 0;
                $errors = [];
                
                foreach ($data as $index => $elt) {
                    try {
                        $this->productService->createProductFromJson($elt);
                        $count++;
                    } catch (\Exception $e) {
                        $errors[] = "Product at index $index: " . $e->getMessage();
                    }
                }

                if (count($errors) > 0) {
                    $this->addWarningToast(
                        'Partial success!', 
                        "$count product(s) added successfully. " . count($errors) . " error(s) occurred."
                    );
                } else {
                    $this->addSuccessToast(
                        'Products added!', 
                        "$count product(s) have been successfully added."
                    );
                }

                return $this->redirectToRoute('admin_product', [], Response::HTTP_SEE_OTHER);
            } catch (\JsonException $e) {
                $this->addErrorToast('Error!', 'Invalid JSON format: ' . $e->getMessage());
            } catch (\Exception $e) {
                $this->addErrorToast('Error!', 'An error occurred: ' . $e->getMessage());
            }
        }

        return $this->render('admin/product/upload_json.html.twig');
    }


    private function handleCreate(Request $request, $form, Product $product): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle images field if it exists
            if ($form->has('images')) {
                /** @var UploadedFile[] $images */
                $images = $form->get('images')->getData();
                if ($images && count($images) > 0) {
                    foreach ($images as $uploadedImage) {
                        $mediaObject = new MediaObject();
                        $this->entityManager->persist($mediaObject); // Persist AVANT setFile pour VichUploaderBundle
                        $mediaObject->setFile($uploadedImage);
                        $product->addImage($mediaObject);
                    }
                }
            }
            
            $this->productService->createProduct($product);
            $this->addSuccessToast('Product created!', "The product has been successfully created.");

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/product/create.html.twig", 'stream_success', [
                    'product' => $product
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_product', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/product/create.html.twig", [
            'product' => $product,
            'form' => $form
        ]);
    }

    private function handleUpdate(Request $request, $form, Product $product): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // IMPORTANT: Recharger le produit depuis la base de données pour s'assurer qu'il est géré par Doctrine
            // Cela évite de créer un nouveau produit au lieu de mettre à jour l'existant
            $productId = $product->getId();
            if (!$productId) {
                throw new \RuntimeException('Cannot update product without ID');
            }

            $managedProduct = $this->productRepository->createQueryBuilder('p')
                ->leftJoin('p.images', 'img')
                ->leftJoin('p.category', 'cat')
                ->leftJoin('p.brand', 'brand')
                ->leftJoin('p.manufacturer', 'manufacturer')
                ->leftJoin('p.form', 'form')
                ->leftJoin('p.unit', 'unit')
                ->addSelect('img', 'cat', 'brand', 'manufacturer', 'form', 'unit')
                ->where('p.id = :id')
                ->setParameter('id', $productId)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$managedProduct) {
                throw $this->createNotFoundException('Product not found');
            }

            // Mettre à jour les propriétés de base depuis le formulaire
            // Le formulaire Symfony gère déjà les relations, mais nous devons nous assurer que l'entité est managed
            // Copier les propriétés simples
            $managedProduct->setName($product->getName());
            $managedProduct->setCode($product->getCode());
            $managedProduct->setDescription($product->getDescription());
            $managedProduct->setForm($product->getForm());
            $managedProduct->setBrand($product->getBrand());
            $managedProduct->setManufacturer($product->getManufacturer());
            $managedProduct->setIsActive($product->isActive());
            $managedProduct->setQuantity($product->getQuantity());
            $managedProduct->setUnit($product->getUnit());
            $managedProduct->setUnitPrice($product->getUnitPrice());
            $managedProduct->setTotalPrice($product->getTotalPrice());
            $managedProduct->setCurrency($product->getCurrency());
            $managedProduct->setStock($product->getStock());
            $managedProduct->setVariants($product->getVariants());

            // Gérer les catégories (ManyToMany)
            $managedProduct->getCategory()->clear();
            foreach ($product->getCategory() as $category) {
                $managedProduct->addCategory($category);
            }

            // Handle images field if it exists - ajouter de nouvelles images
            if ($form->has('images')) {
                /** @var UploadedFile[] $images */
                $images = $form->get('images')->getData();
                if ($images && count($images) > 0) {
                    foreach ($images as $uploadedImage) {
                        $mediaObject = new MediaObject();
                        $this->entityManager->persist($mediaObject); // Persist AVANT setFile pour VichUploaderBundle
                        $mediaObject->setFile($uploadedImage);
                        $managedProduct->addImage($mediaObject);
                    }
                }
            }
            
            $this->productService->updateProduct($managedProduct);

            $this->addSuccessToast('Product updated!', "The product has been successfully updated.");

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/product/edit.html.twig", 'stream_success', [
                    'product' => $managedProduct
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_product', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/product/edit.html.twig", [
            'product' => $product,
            'form' => $form
        ]);
    }
}
