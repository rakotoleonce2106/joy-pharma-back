<?php

namespace App\Form;

use App\Entity\Brand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class BrandType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'brand.form.name',
                'attr' => [
                    'placeholder' => 'brand.form.name_placeholder',
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'category.form.image',
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
            ])
            ;
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Brand::class,
        ]);
    }
}