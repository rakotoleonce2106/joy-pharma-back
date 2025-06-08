<?php

namespace App\Twig\Components;


use App\Entity\Brand;
use App\Form\BrandType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    name: 'admin:brand-form',
    template: 'components/admin/brand-form.html.twig',
)]
final class BrandForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public ?Brand $initialFormData = null;

    protected function instantiateForm(): FormInterface
    {
        $brand = $this->initialFormData ?? new Brand();
        $action = $brand->getId()
            ? $this->generateUrl('admin_brand_edit', ['id' => $brand->getId()])
            : $this->generateUrl('admin_brand_new');

        return $this->createForm(BrandType::class, $brand, [
            'action' => $action,
        ]);
    }
}