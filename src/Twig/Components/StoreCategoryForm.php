<?php

namespace App\Twig\Components;


use App\Entity\StoreCategory;
use App\Form\StoreCategoryType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    name: 'admin:store-category-form',
    template: 'components/admin/store-category-form.html.twig',
)]
final class StoreCategoryForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public ?StoreCategory $initialFormData = null;

    protected function instantiateForm(): FormInterface
    {
        $StoreCategory = $this->initialFormData ?? new StoreCategory();
        $action = $StoreCategory->getId()
            ? $this->generateUrl('admin_store_category_edit', ['id' => $StoreCategory->getId()])
            : $this->generateUrl('admin_store_category_new');

        return $this->createForm(StoreCategoryType::class, $StoreCategory, [
            'action' => $action,
        ]);
    }
}