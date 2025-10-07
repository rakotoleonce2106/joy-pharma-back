<?php

namespace App\Twig\Components;


use App\Entity\Order;
use App\Form\orderType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    name: 'admin:order-form',
    template: 'components/admin/order-form.html.twig',
)]
final class OrderForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public ?Order $initialFormData = null;

    protected function instantiateForm(): FormInterface
    {
        $order = $this->initialFormData ?? new Order();
        $action = $order->getId()
            ? $this->generateUrl('admin_order_edit', ['id' => $order->getId()])
            : $this->generateUrl('admin_order_new');

        return $this->createForm(OrderType::class, $order, [
            'action' => $action,
        ]);
    }
}