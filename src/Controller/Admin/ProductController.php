<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\ProductDataTableType;
use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\ProductService;
use App\Service\MediaFileService;
use App\Traits\ToastTrait;
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
        private readonly MediaFileService $mediaFileService
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
        return $this->handleProductForm($request, $form, $product, 'create');
    }

    #[Route('/product/{id}/edit', name: 'admin_product_edit', defaults: ['title' => 'Edit product'])]
    public function editAction(Request $request, Product $product): Response
    {

        $form = $this->createForm(ProductType::class, $product, [
            'action' => $this->generateUrl('admin_product_edit', ['id' => $product->getId()])
        ]);
        return $this->handleProductForm($request, $form, $product, 'edit');
    }

    #[Route('/product/{id}/delete', name: 'admin_product_delete', methods: ['POST'])]
    public  function deleteAction(Product $product): Response
    {
        $this->productService->deleteProduct($product);
        $this->addSuccessToast('Product deleted!', 'The product has been successfully deleted.');
        return $this->redirectToRoute('admin_product');
    }

    #[Route('/product/batch-delete', name: 'admin_product_batch_delete', methods: ['POST'])]
    public function batchDeleteAction(Request $request): Response
    {
        $productIds = $request->request->all('id');
        $this->productService->batchDeleteProducts(
            $productIds
        );

        $this->addSuccessToast("Products deleted!", "The products have been successfully deleted.");
        return $this->redirectToRoute('admin_product');
    }


    private function handleProductForm(Request $request, $form, $product, string $action): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($action === 'create') {
                /** @var UploadedFile|null $uploadedFile */
                $image = $form->get('image')->getData();
                if ($image) {
                    $mediaFile = $this->mediaFileService->createMediaByFile($image, 'images/product/');
                    $product->setImage($mediaFile);
                }
                /** @var UploadedFile|null $uploadedFile */
                $svg = $form->get('svg')->getData();

                if ($svg) {
                    $svgFile = $this->mediaFileService->createMediaByFile($svg, 'icons/product/');
                    $product->setSvg($svgFile);
                }
                $this->productService->createProduct($product);
            } else {
                /** @var UploadedFile|null $uploadedFile */
                $image = $form->get('image')->getData();
                if ($image) {
                    $mediaFile = $this->mediaFileService->updateMediaFileFromFile($product->getImage(), $image, 'images/product/');
                    $product->setImage($mediaFile);
                }
                /** @var UploadedFile|null $uploadedFile */
                $svg = $form->get('svg')->getData();

                if ($svg) {
                    $svgFile = $this->mediaFileService->updateMediaFileFromFile($product->getSvg(), $svg, 'icons/product/');
                    $product->setSvg($svgFile);
                }
                $this->productService->updateProduct($product);
            }


            $this->addSuccessToast(
                $action === 'create' ? 'Product created!' : 'Product updated!',
                "The product has been successfully {$action}d."
            );

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/product/{$action}.html.twig", 'stream_success', [
                    'product' => $product
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_product', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/product/{$action}.html.twig", [
            'product' => $product,
            'form' => $form
        ]);
    }
}
