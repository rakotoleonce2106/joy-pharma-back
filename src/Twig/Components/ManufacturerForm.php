<?php

namespace App\Twig\Components;


use App\Entity\Manufacturer;
use App\Form\ManufacturerType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    name: 'admin:manufacturer-form',
    template: 'components/admin/manufacturer-form.html.twig',
)]
final class ManufacturerForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public ?Manufacturer $initialFormData = null;

    protected function instantiateForm(): FormInterface
    {
        $manufacturer = $this->initialFormData ?? new Manufacturer();
        $action = $manufacturer->getId()
            ? $this->generateUrl('admin_manufacturer_edit', ['id' => $manufacturer->getId()])
            : $this->generateUrl('admin_manufacturer_new');

        return $this->createForm(ManufacturerType::class, $manufacturer, [
            'action' => $action,
        ]);
    }
}