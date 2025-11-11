<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\BrandDataTableType;
use App\Entity\Brand;
use App\Form\BrandType;
use App\Repository\BrandRepository;
use App\Service\BrandService;
use App\Traits\ToastTrait;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly EntityManagerInterface $entityManager
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
        return $this->handleCreate($request, $form, $brand);
    }

    #[Route('/brand/{id}/edit', name: 'admin_brand_edit')]
    public function editAction(Request $request, Brand $brand): Response
    {
        // Recharger la marque avec sa relation image pour éviter les problèmes de lazy loading
        $brand = $this->brandRepository->createQueryBuilder('b')
            ->leftJoin('b.image', 'img')
            ->addSelect('img')
            ->where('b.id = :id')
            ->setParameter('id', $brand->getId())
            ->getQuery()
            ->getOneOrNullResult();
        
        if (!$brand) {
            throw $this->createNotFoundException('Brand not found');
        }

        $form = $this->createForm(BrandType::class, $brand, [
            'action' => $this->generateUrl('admin_brand_edit', ['id' => $brand->getId()])
        ]);
        return $this->handleUpdate($request, $form, $brand);
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


    private function handleCreate(Request $request, $form, Brand $brand): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image
            /** @var UploadedFile|null $image */
            $image = $form->get('image')->getData();
            if ($image) {
                // Nouvelle image fournie
                $brand->setImageFile($image);
                // Si un nouveau MediaObject a été créé, le persister
                if ($brand->getImage()) {
                    $this->entityManager->persist($brand->getImage());
                }
            }

            $this->brandService->createBrand($brand);

            $this->addSuccessToast(
                'Brand created!',
                "The brand has been successfully created."
            );

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/brand/create.html.twig", 'stream_success', [
                    'brand' => $brand,
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_brand', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/brand/create.html.twig", [
            'brand' => $brand,
            'form' => $form
        ]);
    }

    private function handleUpdate(Request $request, $form, Brand $brand): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // IMPORTANT: Recharger la marque depuis la base de données pour s'assurer qu'elle est gérée par Doctrine
            // Cela évite de créer une nouvelle marque au lieu de mettre à jour l'existante
            $brandId = $brand->getId();
            if (!$brandId) {
                throw new \RuntimeException('Cannot update brand without ID');
            }

            $managedBrand = $this->brandRepository->createQueryBuilder('b')
                ->leftJoin('b.image', 'img')
                ->addSelect('img')
                ->where('b.id = :id')
                ->setParameter('id', $brandId)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$managedBrand) {
                throw $this->createNotFoundException('Brand not found');
            }

            // Mettre à jour les propriétés de base
            $managedBrand->setName($brand->getName());

            // Gestion de l'image
            /** @var UploadedFile|null $image */
            $image = $form->get('image')->getData();
            $removeImage = $request->request->get('remove_image') === '1' || $request->request->get('remove_image') === 1 || $request->request->get('remove_image') === 'true';
            
            if ($removeImage) {
                // Supprimer l'image existante
                $managedBrand->setImage(null);
            } elseif ($image) {
                // Nouvelle image fournie - remplacer l'ancienne
                $hadImage = $managedBrand->getImage() !== null;
                $managedBrand->setImageFile($image);
                // Si un nouveau MediaObject a été créé, le persister
                if (!$hadImage && $managedBrand->getImage()) {
                    $this->entityManager->persist($managedBrand->getImage());
                }
            }
            // Si aucune nouvelle image n'est fournie et pas de suppression, l'image existante est préservée

            $this->brandService->updateBrand($managedBrand);

            $this->addSuccessToast(
                'Brand updated!',
                "The brand has been successfully updated."
            );

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/brand/edit.html.twig", 'stream_success', [
                    'brand' => $managedBrand,
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_brand', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/brand/edit.html.twig", [
            'brand' => $brand,
            'form' => $form
        ]);
    }
}
