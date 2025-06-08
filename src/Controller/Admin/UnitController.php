<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\UnitDataTableType;
use App\Entity\Unit;
use App\Form\UnitType;
use App\Repository\UnitRepository;
use App\Service\UnitService;
use App\Service\MediaFileService;
use App\Traits\ToastTrait;
use Kreyu\Bundle\DataTableBundle\DataTableFactoryAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UnitController extends AbstractController
{
    use DataTableFactoryAwareTrait;
    use ToastTrait;

    public  function __construct(
        private  readonly UnitRepository $unitRepository,
        private readonly UnitService $unitService,
        private readonly MediaFileService $mediaFileService
    ) {}
    #[Route('/unit', name: 'admin_unit')]
    public function index(Request $request): Response
    {
        $query = $this->unitRepository->createQueryBuilder('unit');

        $datatable = $this->createNamedDataTable('units', UnitDataTableType::class, $query);
        $datatable->handleRequest($request);

        return $this->render('admin/unit/index.html.twig', [
            'datatable' => $datatable->createView()
        ]);
    }

    #[Route('/unit/new', name: 'admin_unit_new', defaults: ['title' => 'Create unit'])]
    public function createAction(Request $request): Response
    {
        $unit = new Unit();
        $form = $this->createForm(UnitType::class, $unit, ['action' => $this->generateUrl('admin_unit_new')]);
        return $this->handleUnitForm($request, $form, $unit, 'create');
    }

    #[Route('/unit/{id}/edit', name: 'admin_unit_edit', defaults: ['title' => 'Edit unit'])]
    public function editAction(Request $request, Unit $unit): Response
    {

        $form = $this->createForm(UnitType::class, $unit, [
            'action' => $this->generateUrl('admin_unit_edit', ['id' => $unit->getId()])
        ]);
        return $this->handleUnitForm($request, $form, $unit, 'edit');
    }

    #[Route('/unit/{id}/delete', name: 'admin_unit_delete', methods: ['POST'])]
    public  function deleteAction(Unit $unit): Response
    {
        $this->unitService->deleteUnit($unit);
        $this->addSuccessToast('Unit deleted!', 'The unit has been successfully deleted.');
        return $this->redirectToRoute('admin_unit');
    }

    #[Route('/unit/batch-delete', name: 'admin_unit_batch_delete', methods: ['POST'])]
    public function batchDeleteAction(Request $request): Response
    {
        $unitIds = $request->request->all('id');
        $this->unitService->batchDeleteUnits(
            $unitIds
        );

        $this->addSuccessToast("Units deleted!", "The units have been successfully deleted.");
        return $this->redirectToRoute('admin_unit');
    }


    private function handleUnitForm(Request $request, $form, $unit, string $action): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($action === 'create') {
                $this->unitService->createUnit($unit);
            } else {
                $this->unitService->updateUnit($unit);
            }


            $this->addSuccessToast(
                $action === 'create' ? 'Unit created!' : 'Unit updated!',
                "The unit has been successfully {$action}d."
            );

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/unit/{$action}.html.twig", 'stream_success', [
                    'unit' => $unit
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_unit', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/unit/{$action}.html.twig", [
            'unit' => $unit,
            'form' => $form
        ]);
    }
}
