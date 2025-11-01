<?php

namespace App\Form;

use App\Entity\StoreSetting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoreSettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mondayHours', BusinessHoursType::class, [
                'label' => 'Monday',
            ])
            ->add('tuesdayHours', BusinessHoursType::class, [
                'label' => 'Tuesday',
            ])
            ->add('wednesdayHours', BusinessHoursType::class, [
                'label' => 'Wednesday',
            ])
            ->add('thursdayHours', BusinessHoursType::class, [
                'label' => 'Thursday',
            ])
            ->add('fridayHours', BusinessHoursType::class, [
                'label' => 'Friday',
            ])
            ->add('saturdayHours', BusinessHoursType::class, [
                'label' => 'Saturday',
            ])
            ->add('sundayHours', BusinessHoursType::class, [
                'label' => 'Sunday',
            ]);
        
        // Ensure all BusinessHours are initialized before form processing
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $storeSetting = $event->getData();
            if ($storeSetting instanceof StoreSetting) {
                // Ensure all BusinessHours are initialized
                $storeSetting->initializeDefaults();
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StoreSetting::class,
        ]);
    }
}
