<?php

namespace App\Form;

use App\Entity\Location;
use App\Form\DataTransformer\NullLocationTransformer;
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
                'required' => false,
                'attr' => [
                    'placeholder' => 'Click on map to select location or enter address manually',
                    'id' => 'location_address',
                    'data-location-map-target' => 'address',
                ],
            ])
            ->add('latitude', NumberType::class, [
                'required' => false, 
                'scale' => 6,
                'attr' => [
                    'placeholder' => 'Latitude',
                    'id' => 'location_latitude',
                    'data-location-map-target' => 'latitude',
                    'readonly' => true,
                ]
            ])
            ->add('longitude', NumberType::class, [
                'required' => false,
                'scale' => 6,
                'attr' => [
                    'placeholder' => 'Longitude',
                    'id' => 'location_longitude',
                    'data-location-map-target' => 'longitude',
                    'readonly' => true,
                ]
            ])
        ;
        
        // Add transformer to convert empty Location to null
        $builder->addModelTransformer(new NullLocationTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
            'empty_data' => function () {
                // Create a new Location instance for form binding
                // The transformer will convert empty locations to null
                return new Location();
            },
            'required' => false,
        ]);
    }
}
