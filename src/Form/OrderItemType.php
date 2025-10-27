<?php

namespace App\Form;

use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\Store;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantity', IntegerType::class, [
                'label' => 'order.form.quantity',
                'attr' => [
                    'placeholder' => 'order.form.quantity',
                    'min' => 1,
                ],
            ])
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
                'label' => 'order.form.product',
                'placeholder' => 'order.form.product_placeholder',
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('p')
                        ->orderBy('p.name', 'ASC');
                },
                'choice_attr' => function(Product $product) {
                    // Add data-price attribute for frontend calculation
                    $price = $product->getTotalPrice() ?? $product->getUnitPrice() ?? 0;
                    return ['data-price' => $price];
                },
            ])
            ->add('store', EntityType::class, [
                'class' => Store::class,
                'choice_label' => 'name',
                'label' => 'order.form.store',
                'placeholder' => 'Select a store (optional)',
                'required' => false,
                'help' => 'Optional: Select a specific pharmacy',
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('f')
                        ->orderBy('f.name', 'ASC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderItem::class,
        ]);
    }
}
