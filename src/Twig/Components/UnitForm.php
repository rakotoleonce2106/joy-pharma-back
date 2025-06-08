<?php

namespace App\Twig\Components;


use App\Entity\Unit;
use App\Form\UnitType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    name: 'admin:unit-form',
    template: 'components/admin/unit-form.html.twig',
)]
final class UnitForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public ?Unit $initialFormData = null;

    protected function instantiateForm(): FormInterface
    {
        $unit = $this->initialFormData ?? new Unit();
        $action = $unit->getId()
            ? $this->generateUrl('admin_unit_edit', ['id' => $unit->getId()])
            : $this->generateUrl('admin_unit_new');

        return $this->createForm(UnitType::class, $unit, [
            'action' => $action,
        ]);
    }
}