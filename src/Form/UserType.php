<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('email', TextType::class, [
                'label' => 'user.form.email',
                'attr' => [
                    'placeholder' => 'user.form.email_placeholder',
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'user.form.firstName',
                'attr' => [
                    'placeholder' => 'user.form.firstName_placeholder',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'user.form.lastName',
                'attr' => [
                    'placeholder' => 'user.form.lastName_placeholder',
                ],
            ])
            ->add('roles', EntityType::class, [
                'label' => 'user.form.roles',
                'class' => 'App\Entity\Role',
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'user.form.phone',
                'required' => false,
                'attr' => [
                    'placeholder' => 'user.form.phone_placeholder',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'user.form.image',
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, GIF).',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}