<?php

namespace App\Form;

use App\Entity\BusinessHours;
use App\Form\DataTransformer\NullBusinessHoursTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessHoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('isClosed', CheckboxType::class, [
                'label' => 'Closed',
                'required' => false,
                'false_values' => [false, '0', null],
                'attr' => [
                    'class' => 'is-closed-checkbox',
                ],
            ])
            ->add('openTime', TimeType::class, [
                'label' => 'Open Time',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'open-time-input',
                    'type' => 'time',
                ],
            ])
            ->add('closeTime', TimeType::class, [
                'label' => 'Close Time',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'close-time-input',
                    'type' => 'time',
                ],
            ]);
        
        // Add transformer to convert null BusinessHours to empty BusinessHours objects
        // This prevents ReflectionObject errors when BusinessHours properties are null
        $builder->addModelTransformer(new NullBusinessHoursTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BusinessHours::class,
            'empty_data' => function () {
                // Create a new BusinessHours object when form field is null
                // This ensures we always have an object to bind to, even if empty
                return new BusinessHours(null, null, false);
            },
            'required' => false,
        ]);
    }
}
