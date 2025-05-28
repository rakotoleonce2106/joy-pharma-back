<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\MediaFile;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('createdAt', null, [
                'widget' => 'single_text',
            ])
            ->add('updatedAt', null, [
                'widget' => 'single_text',
            ])
            ->add('svg', EntityType::class, [
                'class' => MediaFile::class,
                'choice_label' => 'id',
            ])
            ->add('image', EntityType::class, [
                'class' => MediaFile::class,
                'choice_label' => 'id',
            ])
            ->add('parent', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'id',
            ])
            ->add('children', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
            ->add('products', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
