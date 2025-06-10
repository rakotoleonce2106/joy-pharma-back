<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'category.form.name',
                'attr' => [
                    'placeholder' => 'category.form.name_placeholder',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'category.form.description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'category.form.description_placeholder',
                    'rows' => 3,
                ],
            ])
            ->add('color', TextType::class, [
                'label' => 'category.form.color',
                'attr' => [
                    'placeholder' => 'category.form.color_placeholder',
                ],
            ])
            ->add('parent', EntityType::class, [
                'class' => Category::class,
                'required' => false,
                'choice_label' => 'name',
                'label' => 'category.form.parent',
                'placeholder' => 'category.form.parent_placeholder',
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');
                },
            ])
            ->add('svg', FileType::class, [
                'label' => 'category.form.svg',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/svg+xml',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid SVG file',
                    ])
                ],
                'attr' => [
                    'accept' => '.svg',
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'category.form.image',
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
            'data_class' => Category::class,
        ]);
    }
}