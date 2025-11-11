<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\ManufacturerDataTableType;
use App\Entity\Manufacturer;
use App\Form\ManufacturerType;
use App\Repository\ManufacturerRepository;
use App\Service\ManufacturerService;
use App\Traits\ToastTrait;
use Doctrine\ORM\EntityManagerInterface;
use Kreyu\Bundle\DataTableBundle\DataTableFactoryAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ManufacturerController extends AbstractController
{
    use DataTableFactoryAwareTrait;
    use ToastTrait;

    public  function __construct(
        private  readonly ManufacturerRepository $manufacturerRepository,
        private readonly ManufacturerService $manufacturerService,
        private readonly EntityManagerInterface $entityManager
    ) {}
    #[Route('/manufacturer', name: 'admin_manufacturer', defaults: ['title' => 'Manufacturer'])]
    public function index(Request $request): Response
    {
        $query = $this->manufacturerRepository->createQueryBuilder('manufacturer');

        $datatable = $this->createNamedDataTable('manufacturers', ManufacturerDataTableType::class, $query);
        $datatable->handleRequest($request);

        return $this->render('admin/manufacturer/index.html.twig', [
            'datatable' => $datatable->createView()
        ]);
    }

    #[Route('/manufacturer/new', name: 'admin_manufacturer_new', defaults: ['title' => 'Create manufacturer'])]
    public function createAction(Request $request): Response
    {
        $manufacturer = new Manufacturer();
        $form = $this->createForm(ManufacturerType::class, $manufacturer, ['action' => $this->generateUrl('admin_manufacturer_new')]);
        return $this->handleCreate($request, $form, $manufacturer);
    }

    #[Route('/manufacturer/{id}/edit', name: 'admin_manufacturer_edit', defaults: ['title' => 'Edit manufacturer'])]
    public function editAction(Request $request, Manufacturer $manufacturer): Response
    {
        // Recharger le fabricant avec sa relation image pour éviter les problèmes de lazy loading
        $manufacturer = $this->manufacturerRepository->createQueryBuilder('m')
            ->leftJoin('m.image', 'img')
            ->addSelect('img')
            ->where('m.id = :id')
            ->setParameter('id', $manufacturer->getId())
            ->getQuery()
            ->getOneOrNullResult();
        
        if (!$manufacturer) {
            throw $this->createNotFoundException('Manufacturer not found');
        }

        $form = $this->createForm(ManufacturerType::class, $manufacturer, [
            'action' => $this->generateUrl('admin_manufacturer_edit', ['id' => $manufacturer->getId()])
        ]);
        return $this->handleUpdate($request, $form, $manufacturer);
    }

    #[Route('/manufacturer/{id}/delete', name: 'admin_manufacturer_delete', methods: ['POST'])]
    public  function deleteAction(Manufacturer $manufacturer): Response
    {
        try {
            $this->manufacturerService->deleteManufacturer($manufacturer);
            $this->addSuccessToast('Manufacturer deleted!', 'The manufacturer has been successfully deleted.');
        } catch (\Exception $e) {
            $this->addErrorToast('Delete failed!', 'An error occurred while deleting the manufacturer: ' . $e->getMessage());
        }
        return $this->redirectToRoute('admin_manufacturer', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/manufacturer/batch-delete', name: 'admin_manufacturer_batch_delete', methods: ['POST'])]
    public function batchDeleteAction(Request $request): Response
    {
        try {
            $manufacturerIds = $request->request->all('id');
            
            if (empty($manufacturerIds)) {
                $this->addWarningToast('No manufacturers selected', 'Please select at least one manufacturer to delete.');
                return $this->redirectToRoute('admin_manufacturer', [], Response::HTTP_SEE_OTHER);
            }

            $result = $this->manufacturerService->batchDeleteManufacturers($manufacturerIds);

            if ($result['failure_count'] > 0) {
                $this->addWarningToast(
                    'Partial deletion!',
                    "{$result['success_count']} manufacturer(s) deleted successfully. {$result['failure_count']} manufacturer(s) could not be deleted."
                );
            } else {
                $this->addSuccessToast(
                    "Manufacturers deleted!",
                    "{$result['success_count']} manufacturer(s) have been successfully deleted."
                );
            }
        } catch (\Exception $e) {
            $this->addErrorToast('Delete failed!', 'An error occurred while deleting manufacturers: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_manufacturer', [], Response::HTTP_SEE_OTHER);
    }


    private function handleCreate(Request $request, $form, Manufacturer $manufacturer): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image
            /** @var UploadedFile|null $image */
            $image = $form->get('image')->getData();
            if ($image) {
                // Nouvelle image fournie
                $manufacturer->setImageFile($image);
                // Si un nouveau MediaObject a été créé, le persister
                if ($manufacturer->getImage()) {
                    $this->entityManager->persist($manufacturer->getImage());
                }
            }

            $this->manufacturerService->createManufacturer($manufacturer);

            $this->addSuccessToast(
                'Manufacturer created!',
                "The manufacturer has been successfully created."
            );

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/manufacturer/create.html.twig", 'stream_success', [
                    'manufacturer' => $manufacturer
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_manufacturer', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/manufacturer/create.html.twig", [
            'manufacturer' => $manufacturer,
            'form' => $form
        ]);
    }

    private function handleUpdate(Request $request, $form, Manufacturer $manufacturer): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // IMPORTANT: Recharger le fabricant depuis la base de données pour s'assurer qu'il est géré par Doctrine
            // Cela évite de créer un nouveau fabricant au lieu de mettre à jour l'existant
            $manufacturerId = $manufacturer->getId();
            if (!$manufacturerId) {
                throw new \RuntimeException('Cannot update manufacturer without ID');
            }

            $managedManufacturer = $this->manufacturerRepository->createQueryBuilder('m')
                ->leftJoin('m.image', 'img')
                ->addSelect('img')
                ->where('m.id = :id')
                ->setParameter('id', $manufacturerId)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$managedManufacturer) {
                throw $this->createNotFoundException('Manufacturer not found');
            }

            // Mettre à jour les propriétés de base
            $managedManufacturer->setName($manufacturer->getName());
            $managedManufacturer->setDescription($manufacturer->getDescription());

            // Gestion de l'image
            /** @var UploadedFile|null $image */
            $image = $form->get('image')->getData();
            $removeImage = $request->request->get('remove_image') === '1' || $request->request->get('remove_image') === 1 || $request->request->get('remove_image') === 'true';
            
            if ($removeImage) {
                // Supprimer l'image existante
                $managedManufacturer->setImage(null);
            } elseif ($image) {
                // Nouvelle image fournie - remplacer l'ancienne
                $hadImage = $managedManufacturer->getImage() !== null;
                $managedManufacturer->setImageFile($image);
                // Si un nouveau MediaObject a été créé, le persister
                if (!$hadImage && $managedManufacturer->getImage()) {
                    $this->entityManager->persist($managedManufacturer->getImage());
                }
            }
            // Si aucune nouvelle image n'est fournie et pas de suppression, l'image existante est préservée

            $this->manufacturerService->updateManufacturer($managedManufacturer);

            $this->addSuccessToast(
                'Manufacturer updated!',
                "The manufacturer has been successfully updated."
            );

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/manufacturer/edit.html.twig", 'stream_success', [
                    'manufacturer' => $managedManufacturer
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_manufacturer', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/manufacturer/edit.html.twig", [
            'manufacturer' => $manufacturer,
            'form' => $form
        ]);
    }
}
