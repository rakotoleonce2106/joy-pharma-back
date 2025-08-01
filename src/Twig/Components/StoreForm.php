<?php

namespace App\Twig\Components;


use App\Entity\Store;
use App\Form\StoreType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    name: 'admin:store-form',
    template: 'components/admin/store-form.html.twig',
)]
final class StoreForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public ?Store $initialFormData = null;

    protected function instantiateForm(): FormInterface
    {
        $Store = $this->initialFormData ?? new Store();
        $action = $Store->getId()
            ? $this->generateUrl('admin_store_edit', ['id' => $Store->getId()])
            : $this->generateUrl('admin_store_new');

        return $this->createForm(StoreType::class, $Store, [
            'action' => $action,
        ]);
    }
}