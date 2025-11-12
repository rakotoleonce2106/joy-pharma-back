<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\PromotionDataTableType;
use App\Entity\Promotion;
use App\Form\PromotionType;
use App\Repository\PromotionRepository;
use App\Traits\ToastTrait;
use Doctrine\ORM\EntityManagerInterface;
use Kreyu\Bundle\DataTableBundle\DataTableFactoryAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PromotionController extends AbstractController
{
    use DataTableFactoryAwareTrait;
    use ToastTrait;

    public function __construct(
        private readonly PromotionRepository $promotionRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    #[Route('/promotion', name: 'admin_promotion', defaults: ['title' => 'Promotions'])]
    public function index(Request $request): Response
    {
        $query = $this->promotionRepository->createQueryBuilder('promotion');

        $datatable = $this->createNamedDataTable('promotions', PromotionDataTableType::class, $query);
        $datatable->handleRequest($request);

        return $this->render('admin/promotion/index.html.twig', [
            'datatable' => $datatable->createView()
        ]);
    }

    #[Route('/promotion/new', name: 'admin_promotion_new', defaults: ['title' => 'Create promotion'])]
    public function createAction(Request $request): Response
    {
        $promotion = new Promotion();
        $form = $this->createForm(PromotionType::class, $promotion, [
            'action' => $this->generateUrl('admin_promotion_new')
        ]);
        return $this->handleCreate($request, $form, $promotion);
    }

    #[Route('/promotion/{id}/edit', name: 'admin_promotion_edit', defaults: ['title' => 'Edit promotion'])]
    public function editAction(Request $request, Promotion $promotion): Response
    {
        $form = $this->createForm(PromotionType::class, $promotion, [
            'action' => $this->generateUrl('admin_promotion_edit', ['id' => $promotion->getId()])
        ]);
        return $this->handleUpdate($request, $form, $promotion);
    }

    #[Route('/promotion/{id}/delete', name: 'admin_promotion_delete', methods: ['POST'])]
    public function deleteAction(Promotion $promotion): Response
    {
        try {
            // Check if promotion is used in orders
            if ($promotion->getOrders()->count() > 0) {
                $this->addWarningToast(
                    'Cannot delete promotion!',
                    'This promotion has been used in orders and cannot be deleted.'
                );
                return $this->redirectToRoute('admin_promotion', [], Response::HTTP_SEE_OTHER);
            }

            $this->entityManager->remove($promotion);
            $this->entityManager->flush();

            $this->addSuccessToast('Promotion deleted!', 'The promotion has been successfully deleted.');
        } catch (\Exception $e) {
            $this->addErrorToast('Delete failed!', 'An error occurred while deleting the promotion: ' . $e->getMessage());
        }
        return $this->redirectToRoute('admin_promotion', [], Response::HTTP_SEE_OTHER);
    }

    private function handleCreate(Request $request, $form, Promotion $promotion): Response
    {
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addErrorToast(
                    'Validation failed!',
                    'Please check the form for errors: ' . implode(', ', $errors)
                );
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if code already exists
            $existingPromotion = $this->promotionRepository->findByCode($promotion->getCode());
            if ($existingPromotion) {
                $this->addErrorToast(
                    'Code already exists!',
                    'A promotion with this code already exists. Please use a different code.'
                );
                return $this->render("admin/promotion/create.html.twig", [
                    'promotion' => $promotion,
                    'form' => $form
                ]);
            }

            $this->entityManager->persist($promotion);
            $this->entityManager->flush();

            $this->addSuccessToast(
                'Promotion created!',
                "The promotion has been successfully created."
            );

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/promotion/create.html.twig", 'stream_success', [
                    'promotion' => $promotion
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_promotion', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/promotion/create.html.twig", [
            'promotion' => $promotion,
            'form' => $form
        ]);
    }

    private function handleUpdate(Request $request, $form, Promotion $promotion): Response
    {
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addErrorToast(
                    'Validation failed!',
                    'Please check the form for errors: ' . implode(', ', $errors)
                );
            }
        }

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render("admin/promotion/edit.html.twig", [
                'promotion' => $promotion,
                'form' => $form
            ]);
        }

        // Check if code already exists (excluding current promotion)
        $existingPromotion = $this->promotionRepository->findByCode($promotion->getCode());
        if ($existingPromotion && $existingPromotion->getId() !== $promotion->getId()) {
            $this->addErrorToast(
                'Code already exists!',
                'A promotion with this code already exists. Please use a different code.'
            );
            return $this->render("admin/promotion/edit.html.twig", [
                'promotion' => $promotion,
                'form' => $form
            ]);
        }

        $this->entityManager->flush();

        $this->addSuccessToast(
            'Promotion updated!',
            "The promotion has been successfully updated."
        );

        if ($request->headers->has('turbo-frame')) {
            $stream = $this->renderBlockView("admin/promotion/edit.html.twig", 'stream_success', [
                'promotion' => $promotion
            ]);
            $this->addFlash('stream', $stream);
        }

        return $this->redirectToRoute('admin_promotion', status: Response::HTTP_SEE_OTHER);
    }
}

