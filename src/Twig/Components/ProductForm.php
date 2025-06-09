<?php

namespace App\Twig\Components;


use App\Entity\Product;
use App\Form\ProductType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    name: 'admin:product-form',
    template: 'components/admin/product-form.html.twig',
)]
final class ProductForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public ?Product $initialFormData = null;

    protected function instantiateForm(): FormInterface
    {
        $product = $this->initialFormData ?? new Product();
        $action = $product->getId()
            ? $this->generateUrl('admin_product_edit', ['id' => $product->getId()])
            : $this->generateUrl('admin_product_new');

        return $this->createForm(ProductType::class, $product, [
            'action' => $action,
        ]);
    }
}