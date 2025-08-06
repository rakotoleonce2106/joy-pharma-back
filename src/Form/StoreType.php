<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Store;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

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
                    return $repository->createQueryBuilder('c');
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
            ->add('image', FileType::class, [
                'label' => 'store.form.image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, or WebP)',
                    ])
                ],
                'attr' => [
                    'accept' => '.jpg,.jpeg,.png,.webp',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Store::class,
        ]);
    }
}
