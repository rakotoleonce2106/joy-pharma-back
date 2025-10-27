<?php

namespace App\Form;

use App\Entity\Brand;
use App\Entity\Category;
use App\Entity\Currency;
use App\Entity\Form;
use App\Entity\Manufacturer;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'product.form.name',
                'attr' => [
                    'placeholder' => 'product.form.name_placeholder',
                ],
            ])
            ->add('code', TextType::class, [
                'label' => 'product.form.code',
                'required' => false,
                'attr' => [
                    'placeholder' => 'product.form.code_placeholder',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'product.form.description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'product.form.description_placeholder',
                    'rows' => 3,
                ],
            ])
            ->add('images', FileType::class, [
                'label' => 'product.form.images',
                'mapped' => false,
                'multiple' => true,
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
                        'mimeTypesMessage' => 'product.form.images_placeholder',
                    ])
                ],
            ])
            ->add('brand', EntityType::class, [
                'class' => Brand::class,
                'required' => false,
                'choice_label' => 'name',
                'label' => 'product.form.brand',
                'placeholder' => 'product.form.brand_placeholder',
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');
                },
            ])
            ->add('manufacturer', EntityType::class, [
                'class' => Manufacturer::class,
                'required' => false,
                'choice_label' => 'name',
                'label' => 'product.form.manufacturer',
                'placeholder' => 'product.form.manufacturer_placeholder',
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');
                },
            ])
            ->add('unitPrice', TextType::class, [
                'label' => 'product.form.unit_price',
                'required' => false,
                'attr' => [
                    'placeholder' => 'product.form.unit_price_placeholder',
                ],
            ])
            ->add('totalPrice', TextType::class, [
                'label' => 'product.form.code',
                'required' => false,
                'attr' => [
                    'placeholder' => 'product.form.code_placeholder',
                ],
            ])
            ->add('currency', EntityType::class, [
                'class' => Currency::class,
                'choice_label' => 'label',
                'label' => 'product.form.currency',
                'placeholder' => 'product.form.currency_placeholder',
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('c')
                        ->orderBy('c.label', 'ASC');
                },
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'product.form.quantity',
                'required' => false,
                'attr' => [
                    'placeholder' => 'product.form.code_placeholder',
                ],
            ])
            ->add(
                'unit',
                UnitType::class,
                [
                    'label' => 'product.form.unit',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'product.form.code_placeholder',
                    ],
                ]
            )
            
            ->add('form', EntityType::class, [
                'class' => Form::class,
                'choice_label' => 'label',
                'label' => 'product.form.form',
                'placeholder' => 'product.form.form_placeholder',
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('f')
                        ->orderBy('f.label', 'ASC');
                },
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'required' => true,
                'multiple' => true,
                'label' => 'product.form.category',
                'placeholder' => 'product.form.category_placeholder',
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');
                },
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'product.form.is_active',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
