<?php

namespace App\Twig\Components;


use App\Entity\User;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    name: 'admin:user-form',
    template: 'components/admin/user-form.html.twig',
)]
final class UserForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public ?User $initialFormData = null;

    protected function instantiateForm(): FormInterface
    {
        $user = $this->initialFormData ?? new User();
        $action = $user->getId()
            ? $this->generateUrl('admin_user_edit', ['id' => $user->getId()])
            : $this->generateUrl('admin_user_new');

        return $this->createForm(UserType::class, $user, [
            'action' => $action,
        ]);
    }
}