<?php

namespace App\Twig\Components;


use App\Entity\Form;
use App\Form\FormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    name: 'admin:form-form',
    template: 'components/admin/form-form.html.twig',
)]
final class FormForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public ?Form $initialFormData = null;

    protected function instantiateForm(): FormInterface
    {
        $form = $this->initialFormData ?? new Form();
        $action = $form->getId()
            ? $this->generateUrl('admin_form_edit', ['id' => $form->getId()])
            : $this->generateUrl('admin_form_new');

        return $this->createForm(FormType::class, $form, [
            'action' => $action,
        ]);
    }
}