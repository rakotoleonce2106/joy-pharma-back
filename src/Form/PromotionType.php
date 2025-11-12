<?php

namespace App\Form;

use App\Entity\Promotion;
use App\Entity\DiscountType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromotionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'promotion.form.code',
                'attr' => [
                    'placeholder' => 'promotion.form.code_placeholder',
                    'class' => 'uppercase',
                ],
                'help' => 'promotion.form.code_help',
            ])
            ->add('name', TextType::class, [
                'label' => 'promotion.form.name',
                'attr' => [
                    'placeholder' => 'promotion.form.name_placeholder',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'promotion.form.description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'promotion.form.description_placeholder',
                    'rows' => 3,
                ],
            ])
            ->add('discountType', ChoiceType::class, [
                'label' => 'promotion.form.discount_type',
                'choices' => [
                    'promotion.form.discount_type_percentage' => DiscountType::PERCENTAGE,
                    'promotion.form.discount_type_fixed' => DiscountType::FIXED_AMOUNT,
                ],
                'attr' => [
                    'class' => 'discount-type-select',
                ],
            ])
            ->add('discountValue', NumberType::class, [
                'label' => 'promotion.form.discount_value',
                'scale' => 2,
                'attr' => [
                    'placeholder' => 'promotion.form.discount_value_placeholder',
                    'step' => '0.01',
                    'min' => '0.01',
                ],
                'help' => 'promotion.form.discount_value_help',
            ])
            ->add('minimumOrderAmount', NumberType::class, [
                'label' => 'promotion.form.minimum_order_amount',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'placeholder' => 'promotion.form.minimum_order_amount_placeholder',
                    'step' => '0.01',
                    'min' => '0',
                ],
                'help' => 'promotion.form.minimum_order_amount_help',
            ])
            ->add('maximumDiscountAmount', NumberType::class, [
                'label' => 'promotion.form.maximum_discount_amount',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'placeholder' => 'promotion.form.maximum_discount_amount_placeholder',
                    'step' => '0.01',
                    'min' => '0',
                ],
                'help' => 'promotion.form.maximum_discount_amount_help',
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'promotion.form.start_date',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'datetime-picker',
                ],
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'promotion.form.end_date',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'datetime-picker',
                ],
            ])
            ->add('usageLimit', IntegerType::class, [
                'label' => 'promotion.form.usage_limit',
                'required' => false,
                'attr' => [
                    'placeholder' => 'promotion.form.usage_limit_placeholder',
                    'min' => '1',
                ],
                'help' => 'promotion.form.usage_limit_help',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'promotion.form.is_active',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Promotion::class,
        ]);
    }
}

