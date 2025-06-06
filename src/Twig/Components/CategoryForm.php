<?php

namespace App\Twig\Components;


use App\Entity\Category;
use App\Form\CategoryType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    name: 'admin:category-form',
    template: 'components/admin/category-form.html.twig',
)]
final class CategoryForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public ?Category $initialFormData = null;

    protected function instantiateForm(): FormInterface
    {
        $category = $this->initialFormData ?? new Category();
        $action = $category->getId()
            ? $this->generateUrl('admin_category_edit', ['id' => $category->getId()])
            : $this->generateUrl('admin_category_new');

        return $this->createForm(CategoryType::class, $category, [
            'action' => $action,
        ]);
    }
}