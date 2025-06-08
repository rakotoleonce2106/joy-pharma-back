<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\BrandDataTableType;
use App\Entity\Brand;
use App\Form\BrandType;
use App\Repository\BrandRepository;
use App\Service\BrandService;
use App\Service\MediaFileService;
use App\Traits\ToastTrait;
use Kreyu\Bundle\DataTableBundle\DataTableFactoryAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BrandController extends AbstractController
{
    use DataTableFactoryAwareTrait;
    use ToastTrait;

    public  function __construct(
        private  readonly BrandRepository $brandRepository,
        private readonly BrandService $brandService,
        private readonly MediaFileService $mediaFileService
    ) {}
    #[Route('/brand', name: 'admin_brand')]
    public function index(Request $request): Response
    {
        $query = $this->brandRepository->createQueryBuilder('brand');

        $datatable = $this->createNamedDataTable('categories', BrandDataTableType::class, $query);
        $datatable->handleRequest($request);

        return $this->render('admin/brand/index.html.twig', [
            'datatable' => $datatable->createView()
        ]);
    }

    #[Route('/brand/new', name: 'admin_brand_new')]
    public function createAction(Request $request): Response
    {
        $brand = new Brand();
        $form = $this->createForm(BrandType::class, $brand, ['action' => $this->generateUrl('admin_brand_new')]);
        return $this->handleBrandForm($request, $form, $brand, 'create');
    }

    #[Route('/brand/{id}/edit', name: 'admin_brand_edit')]
    public function editAction(Request $request, Brand $brand): Response
    {

        $form = $this->createForm(BrandType::class, $brand, [
            'action' => $this->generateUrl('admin_brand_edit', ['id' => $brand->getId()])
        ]);
        return $this->handleBrandForm($request, $form, $brand, 'edit');
    }


    #[Route('/brand/{id}/delete', name: 'admin_brand_delete', methods: ['POST'])]
    public  function deleteAction(Brand $brand): Response
    {
        $this->brandService->deleteBrand($brand);
        $this->addSuccessToast('Brand deleted!', 'The brand has been successfully deleted.');
        return $this->redirectToRoute('admin_brand');
    }

    #[Route('/brand/batch-delete', name: 'admin_brand_batch_delete', methods: ['POST'])]
    public function batchDeleteAction(Request $request): Response
    {
        $brandIds = $request->request->all('id');
        $this->brandService->batchDeleteBrands(
            $brandIds
        );

        $this->addSuccessToast("Brands deleted!", "The brands have been successfully deleted.");
        return $this->redirectToRoute('admin_brand');
    }


    private function handleBrandForm(Request $request, $form, $brand, string $action): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($action === 'create') {
                /** @var UploadedFile|null $uploadedFile */
                $image = $form->get('image')->getData();
                if ($image) {
                    $mediaFile = $this->mediaFileService->createMediaByFile($image, 'images/brand/');
                    $brand->setImage($mediaFile);
                }

                $this->brandService->createBrand($brand);
            } else {
                /** @var UploadedFile|null $uploadedFile */
                $image = $form->get('image')->getData();
                if ($image) {
                    $mediaFile = $this->mediaFileService->updateMediaFileFromFile($brand->getImage(), $image, 'images/brand/');
                    $brand->setImage($mediaFile);
                }

                $this->brandService->updateBrand($brand);
            }


            $this->addSuccessToast(
                $action === 'create' ? 'Product created!' : 'Product updated!',
                "The product has been successfully {$action}d."
            );

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/Brand/{$action}.html.twig", 'stream_success', [
                    'brand' => $brand,
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_Brand', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/Brand/{$action}.html.twig", [
            'brand' => $brand,
            'form' => $form
        ]);
    }
}
