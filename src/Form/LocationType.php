<?php

namespace App\Form;

use App\Entity\Location;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('address', TextType::class, [
                'attr' => [
                    'placeholder' => 'location.form.address_placeholder',
                ],
            ])
            ->add('latitude', NumberType::class, [
                'required' => false, 
                'scale' => 6,
                'attr' => [
                    'placeholder' => 'location.form.latitude_placeholder',
                ]
            ])
            ->add('longitude', NumberType::class, [
                'required' => false,
                'scale' => 6,
                'attr' => [
                    'placeholder' => 'location.form.longitude_placeholder',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
        ]);
    }
}
