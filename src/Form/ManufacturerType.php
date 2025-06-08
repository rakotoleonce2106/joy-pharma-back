<?php

namespace App\Form;

use App\Entity\Manufacturer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ManufacturerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'manufacturer.form.name',
                'attr' => [
                    'placeholder' => 'manufacturer.form.name_placeholder',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'manufacturer.form.description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'manufacturer.form.description_placeholder',
                    'rows' => 3,
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'manufacturer.form.image',
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
            'data_class' => Manufacturer::class,
        ]);
    }
}