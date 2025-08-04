<?php

namespace App\Form;

use App\Entity\StoreCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoreCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'storeCategory.form.label',
                'attr' => [
                    'placeholder' => 'storeCategory.form.label_placeholder',
                ],
            ])
            ->add('description', TextType::class, [
                'label' => 'storeCategory.form.description',
                'attr' => [
                    'placeholder' => 'storeCategory.form.description_placeholder',
                ],
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StoreCategory::class,
        ]);
    }
}