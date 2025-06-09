<?php

// src/Form/PriceType.php
namespace App\Form;

use App\Entity\Price;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PriceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('unitPrice', MoneyType::class, [
                'label' => 'Unit Price',
                'required' => false,
                'currency' => '€',
                'attr' => [
                    'placeholder' => 'Enter unit price',
                ],
            ])
            ->add('currency'); // Optional: Add if needed
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Price::class,
        ]);
    }
}
