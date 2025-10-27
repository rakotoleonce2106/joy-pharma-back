<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\OrderDataTableType;
use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Service\OrderService;
use App\Service\MediaFileService;
use App\Traits\ToastTrait;
use Kreyu\Bundle\DataTableBundle\DataTableFactoryAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    use DataTableFactoryAwareTrait;
    use ToastTrait;

    public  function __construct(
        private  readonly OrderRepository $orderRepository,
        private readonly OrderService $orderService,
        private readonly MediaFileService $mediaFileService
    ) {}
    #[Route('/order', name: 'admin_order')]
    public function index(Request $request): Response
    {
        $query = $this->orderRepository->createQueryBuilder('o');

        $datatable = $this->createNamedDataTable('orders', OrderDataTableType::class, $query);
        $datatable->handleRequest($request);

        return $this->render('admin/order/index.html.twig', [
            'datatable' => $datatable->createView()
        ]);
    }

    #[Route('/order/new', name: 'admin_order_new', defaults: ['title' => 'Create order'])]
    public function createAction(Request $request): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order, ['action' => $this->generateUrl('admin_order_new')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->orderService->createorder($order);
            $this->addSuccessToast('Order created!', "The order has been successfully created.");
            return $this->redirectToRoute('admin_order', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/order/create.html.twig", [
            'order' => $order,
            'form' => $form
        ]);
    }

    #[Route('/order/{id}', name: 'admin_order_view', defaults: ['title' => 'View order'])]
    public function viewAction(Order $order): Response
    {
        return $this->render('admin/order/view.html.twig', [
            'order' => $order
        ]);
    }

    #[Route('/order/{id}/edit', name: 'admin_order_edit', defaults: ['title' => 'Edit order'])]
    public function editAction(Request $request, Order $order): Response
    {

        $form = $this->createForm(OrderType::class, $order, [
            'action' => $this->generateUrl('admin_order_edit', ['id' => $order->getId()])
        ]);
        return $this->handleorderForm($request, $form, $order, 'edit');
    }

    #[Route('/order/{id}/delete', name: 'admin_order_delete', methods: ['POST'])]
    public  function deleteAction(Order $order): Response
    {
        $this->orderService->deleteOrder($order);
        $this->addSuccessToast('order deleted!', 'The order has been successfully deleted.');
        return $this->redirectToRoute('admin_order');
    }

    #[Route('/order/batch-delete', name: 'admin_order_batch_delete', methods: ['POST'])]
    public function batchDeleteAction(Request $request): Response
    {
        $orderIds = $request->request->all('id');
        $this->orderService->batchDeleteOrders(
            $orderIds
        );

        $this->addSuccessToast("orders deleted!", "The orders have been successfully deleted.");
        return $this->redirectToRoute('admin_order');
    }


    private function handleorderForm(Request $request, $form, $order, string $action): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($action === 'create') {

                $this->orderService->createorder($order);
            } else {

                $this->orderService->updateorder($order);
            }


            $this->addSuccessToast(
                $action === 'create' ? 'Order created!' : 'Order updated!',
                "The order has been successfully {$action}d."
            );

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/order/{$action}.html.twig", 'stream_success', [
                    'order' => $order
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_order', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/order/{$action}.html.twig", [
            'order' => $order,
            'form' => $form
        ]);
    }
}
