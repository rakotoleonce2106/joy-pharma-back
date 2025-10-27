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
            ->add('owner', EntityType::class, [
                'class' => User::class,
                'label' => 'order.form.customer',
                'required' => false,
                'placeholder' => 'Select a customer (optional)',
                'choices' => $this->getCustomers(),
                'choice_label' => function(User $user) {
                    return $user->getFullName() . ' - ' . $user->getEmail();
                },
                'help' => 'Customer who placed this order',
            ])
            ->add('totalAmount', TextType::class, [
                'label' => 'order.form.total_amount',
                'required' => false,
                'disabled' => true,
                'attr' => [
                    'readonly' => true,
                    'placeholder' => 'Calculated automatically from items',
                ],
                'help' => 'Auto-calculated from order items',
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
                'choices' => $this->getDeliveryPersons(),
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

    /**
     * Get customers (users without ROLE_ADMIN or ROLE_DELIVERY)
     */
    private function getCustomers(): array
    {
        // Get all users and filter in PHP to avoid JSON LIKE issues with PostgreSQL
        $allUsers = $this->userRepository->findAll();
        
        $customers = array_filter($allUsers, function(User $user) {
            $roles = $user->getRoles();
            // Exclude admins and delivery persons
            // Include only users with ROLE_USER or ROLE_CUSTOMER (or both)
            return !in_array('ROLE_ADMIN', $roles) 
                && !in_array('ROLE_DELIVERY', $roles);
        });
        
        // Sort by first name, then last name
        usort($customers, function(User $a, User $b) {
            $firstNameCompare = strcasecmp($a->getFirstName() ?? '', $b->getFirstName() ?? '');
            if ($firstNameCompare !== 0) {
                return $firstNameCompare;
            }
            return strcasecmp($a->getLastName() ?? '', $b->getLastName() ?? '');
        });
        
        return $customers;
    }

    /**
     * Get delivery persons (users with ROLE_DELIVERY)
     */
    private function getDeliveryPersons(): array
    {
        // Get all users and filter in PHP to avoid JSON LIKE issues with PostgreSQL
        $allUsers = $this->userRepository->findAll();
        
        $deliveryPersons = array_filter($allUsers, function(User $user) {
            $roles = $user->getRoles();
            // Include only users with ROLE_DELIVERY
            return in_array('ROLE_DELIVERY', $roles);
        });
        
        // Sort by first name, then last name
        usort($deliveryPersons, function(User $a, User $b) {
            $firstNameCompare = strcasecmp($a->getFirstName() ?? '', $b->getFirstName() ?? '');
            if ($firstNameCompare !== 0) {
                return $firstNameCompare;
            }
            return strcasecmp($a->getLastName() ?? '', $b->getLastName() ?? '');
        });
        
        return $deliveryPersons;
    }
}