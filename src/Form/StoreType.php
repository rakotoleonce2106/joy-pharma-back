<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Store;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vich\UploaderBundle\Form\Type\VichImageType;

class StoreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'store.form.name',
                'attr' => [
                    'placeholder' => 'store.form.name_placeholder',
                ],
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'required' => true,
                'multiple' => true,
                'label' => 'product.form.category',
                'placeholder' => 'product.form.category_placeholder',
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('c')
                        ->where('c.parent IS NULL');
                },
            ])
            ->add('description', TextareaType::class, [
                'label' => 'store.form.description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'store.form.description_placeholder',
                    'rows' => 3,
                ],
            ])
            ->add('contact', ContactInfoType::class, [
                'label' => 'store.form.contact',
                'required' => false,
                'attr' => [
                    'placeholder' => 'store.form.contact_placeholder',
                ]
            ])
            ->add('location', LocationType::class, [
                'label' => 'store.form.location',
                'required' => false,
                'attr' => [
                    'placeholder' => 'store.form.location_placeholder',
                ]
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'store.form.image',
                'required' => false,
                'allow_delete' => true,
                'delete_label' => 'Remove image',
                'download_label' => 'View image',
                'download_uri' => true,
                'image_uri' => true,
                'imagine_pattern' => null,
                'asset_helper' => true,
            ])
            ->add('ownerEmail', EmailType::class, [
                'label' => 'Login Email',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please enter an email address']),
                    new Email(['message' => 'Please enter a valid email address'])
                ],
                'attr' => [
                    'placeholder' => 'store@example.com',
                ],
                'help' => 'This email will be used for store owner login'
            ])
            ->add('ownerPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'first_options' => [
                    'label' => 'Password',
                    'attr' => [
                        'placeholder' => 'Enter password',
                        'autocomplete' => 'new-password',
                    ],
                    'help' => 'Leave empty to auto-generate or keep existing password'
                ],
                'second_options' => [
                    'label' => 'Confirm Password',
                    'attr' => [
                        'placeholder' => 'Confirm password',
                        'autocomplete' => 'new-password',
                    ]
                ],
                'constraints' => [
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Password must be at least {{ limit }} characters',
                    ])
                ],
                'invalid_message' => 'The password fields must match.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Store::class,
        ]);
    }
}
