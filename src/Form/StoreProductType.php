<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\StoreProduct;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class StoreProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
                'label' => 'Product',
                'required' => true,
                'placeholder' => 'Search and choose a product...',
                'attr' => [
                    'class' => 'w-full',
                    'data-searchable' => 'true',
                ],
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('p')
                        ->orderBy('p.name', 'ASC');
                },
                'choice_attr' => function(Product $product) {
                    return [
                        'data-unit-price' => $product->getUnitPrice() ?? '',
                        'data-total-price' => $product->getTotalPrice() ?? '',
                    ];
                },
                'help' => 'Type to search products by name',
                'constraints' => [
                    new NotBlank(['message' => 'Please select a product'])
                ]
            ])
            ->add('unitPrice', MoneyType::class, [
                'label' => 'Unit Price',
                'currency' => 'MGA',
                'required' => false,
                'attr' => [
                    'placeholder' => '0.00',
                    'step' => '0.01',
                ],
                'help' => 'Price per individual unit (optional)',
                'constraints' => [
                    new PositiveOrZero(['message' => 'Unit price must be positive or zero'])
                ]
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Total Price',
                'currency' => 'MGA',
                'required' => true,
                'attr' => [
                    'placeholder' => '0.00',
                    'step' => '0.01',
                ],
                'help' => 'Total price for the product package',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter the total price']),
                    new PositiveOrZero(['message' => 'Price must be positive or zero'])
                ]
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock Quantity',
                'required' => true,
                'attr' => [
                    'placeholder' => '0',
                    'min' => '0',
                ],
                'help' => 'Number of units available in stock',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter the stock quantity']),
                    new PositiveOrZero(['message' => 'Stock must be positive or zero'])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StoreProduct::class,
        ]);
    }
}

