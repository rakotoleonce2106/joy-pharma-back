<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\BrandDataTableType;
use App\Entity\Brand;
use App\Form\BrandType;
use App\Repository\BrandRepository;
use App\Service\BrandService;
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
        private readonly BrandService $brandService
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
        try {
            $this->brandService->deleteBrand($brand);
            $this->addSuccessToast('Brand deleted!', 'The brand has been successfully deleted.');
        } catch (\Exception $e) {
            $this->addErrorToast('Delete failed!', 'An error occurred while deleting the brand: ' . $e->getMessage());
        }
        return $this->redirectToRoute('admin_brand', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/brand/batch-delete', name: 'admin_brand_batch_delete', methods: ['POST'])]
    public function batchDeleteAction(Request $request): Response
    {
        try {
            $brandIds = $request->request->all('id');
            
            if (empty($brandIds)) {
                $this->addWarningToast('No brands selected', 'Please select at least one brand to delete.');
                return $this->redirectToRoute('admin_brand', [], Response::HTTP_SEE_OTHER);
            }

            $result = $this->brandService->batchDeleteBrands($brandIds);

            if ($result['failure_count'] > 0) {
                $this->addWarningToast(
                    'Partial deletion!',
                    "{$result['success_count']} brand(s) deleted successfully. {$result['failure_count']} brand(s) could not be deleted."
                );
            } else {
                $this->addSuccessToast(
                    "Brands deleted!",
                    "{$result['success_count']} brand(s) have been successfully deleted."
                );
            }
        } catch (\Exception $e) {
            $this->addErrorToast('Delete failed!', 'An error occurred while deleting brands: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_brand', [], Response::HTTP_SEE_OTHER);
    }


    private function handleBrandForm(Request $request, $form, $brand, string $action): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile|null $image */
            $image = $form->get('image')->getData();
            if ($image) {
                $brand->setImageFile($image);
            }

            if ($action === 'create') {
                $this->brandService->createBrand($brand);
            } else {
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
