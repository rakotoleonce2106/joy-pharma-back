<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderStatus;
use App\Entity\PriorityType;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference', TextType::class, [
                'label' => 'order.form.reference',
                'required' => true,
            ])
            ->add('totalAmount', TextType::class, [
                'label' => 'order.form.total_amount',
                'required' => true,
            ])
            ->add('location', LocationType::class, [
                'label' => 'order.form.location',
                'required' => false,
            ])
            ->add('priority', EnumType::class, [
                'label' => 'order.form.priority',
                'class' => PriorityType::class,
                'choice_label' => function (PriorityType $priority): string {
                    return match ($priority) {
                        PriorityType::PRIORITY_URGENT => 'Urgent',
                        PriorityType::PRIORITY_STANDARD => 'Standard',
                        PriorityType::PRIORITY_PLANIFIED => 'Planned',
                    };
                },
                'required' => true,
            ])
            ->add('status', EnumType::class, [
                'label' => 'order.form.status',
                'class' => OrderStatus::class,
                'choice_label' => function (OrderStatus $status): string {
                    return match ($status) {
                        OrderStatus::STATUS_PENDING => 'Pending',
                        OrderStatus::STATUS_CONFIRMED => 'Confirmed',
                        OrderStatus::STATUS_PROCESSING => 'Processing',
                        OrderStatus::STATUS_SHIPPED => 'Shipped',
                        OrderStatus::STATUS_DELIVERED => 'Delivered',
                        OrderStatus::STATUS_CANCELLED => 'Cancelled',
                    };
                },
                'required' => true,
            ])
            ->add('scheduledDate', DateTimeType::class, [
                'label' => 'order.form.scheduled_date',
                'required' => false,
            ])
            ->add('phone', TextType::class, [
                'label' => 'order.form.phone',
                'required' => false,
            ])
            ->add('deliver', EntityType::class, [
                'class' => User::class,
                'label' => 'order.form.delivery_person',
                'required' => false,
                'placeholder' => 'Select a delivery person',
                'choices' => $this->userRepository->findByRole('ROLE_DELIVERY'),
                'choice_label' => function(User $user) {
                    return $user->getFullName() . ' - ' . $user->getEmail();
                },
            ])
            ->add('items', CollectionType::class, [
                'entry_type' => OrderItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true, // Allow adding new items
                'allow_delete' => true, // Allow removing items
                'by_reference' => false, // Ensure setter is called
                'label' => 'order.form.items',
                'prototype' => true, // Enable dynamic addition of items
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'order.form.notes',
                'required' => false,
                'attr' => [
                    'placeholder' => 'order.form.notes_placeholder',
                    'rows' => 3,
                ]
            ])
            ->add('deliveryNotes', TextareaType::class, [
                'label' => 'order.form.delivery_notes',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Special delivery instructions',
                    'rows' => 3,
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}