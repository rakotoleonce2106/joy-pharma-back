<?php

declare(strict_types=1);

namespace App\DataTable\Type;

use App\Entity\User;
use Kreyu\Bundle\DataTableBundle\Action\Type\FormActionType;
use Kreyu\Bundle\DataTableBundle\Action\Type\LinkActionType;
use Kreyu\Bundle\DataTableBundle\Bridge\Doctrine\Orm\Query\DoctrineOrmProxyQuery;
use Kreyu\Bundle\DataTableBundle\Bridge\Doctrine\Orm\Filter\Type\StringFilterType;
use Kreyu\Bundle\DataTableBundle\Column\Type\TextColumnType;
use Kreyu\Bundle\DataTableBundle\DataTableBuilderInterface;
use Kreyu\Bundle\DataTableBundle\Pagination\PaginationData;
use Kreyu\Bundle\DataTableBundle\Type\AbstractDataTableType;
use Kreyu\Bundle\DataTableBundle\Column\Type\DateColumnType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserDataTableType extends AbstractDataTableType
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function buildDataTable(DataTableBuilderInterface $builder, array $options): void
    {
        $builder->setDefaultPaginationData(new PaginationData(page: 1, perPage: 15));
        $builder->setSearchHandler($this->handleSearchFilter(...));
        
        // Filters
        $builder
            ->addFilter('id', StringFilterType::class, ['label' => 'user.datatable.id'])
            ->addFilter('firstName', StringFilterType::class, ['label' => 'user.datatable.first_name'])
            ->addFilter('email', StringFilterType::class, ['label' => 'user.datatable.email']);

        // Columns
        $builder
            ->addColumn('image', TextColumnType::class, [
                'label' => '',
                'sort' => false,
                'getter' => fn (User $user) => $user,
                'value_attr' => [
                    'class' => 'w-16'
                ],
                'header_attr' => [
                    'class' => 'w-16'
                ]
            ])
            ->addColumn('id', TextColumnType::class, [
                'label' => 'user.datatable.id',
                'sort' => true,
                'value_attr' => [
                    'class' => 'w-20 text-center'
                ],
                'header_attr' => [
                    'class' => 'text-center'
                ]
            ])
            ->addColumn('fullName', TextColumnType::class, [
                'label' => 'user.datatable.name',
                'sort' => false,
                'getter' => fn (User $user) => $user->getFullName(),
                'value_attr' => [
                    'class' => 'px-4 font-medium'
                ]
            ])
            ->addColumn('email', TextColumnType::class, [
                'label' => 'user.datatable.email',
                'sort' => true,
                'value_attr' => [
                    'class' => 'px-4'
                ]
            ])
            ->addColumn('phone', TextColumnType::class, [
                'label' => 'user.datatable.phone',
                'sort' => true,
                'value_attr' => [
                    'class' => 'px-4'
                ]
            ])
            ->addColumn('active', TextColumnType::class, [
                'label' => 'user.datatable.status',
                'sort' => true,
                'getter' => fn (User $user) => $user,
                'value_attr' => [
                    'class' => 'px-4 text-center'
                ],
                'header_attr' => [
                    'class' => 'text-center'
                ]
            ])
            ->addColumn('deliveryOnline', TextColumnType::class, [
                'label' => 'user.datatable.delivery_online',
                'sort' => false,
                'getter' => fn (User $user) => $user,
                'value_attr' => [
                    'class' => 'px-4 text-center'
                ],
                'header_attr' => [
                    'class' => 'text-center'
                ]
            ])
            ->addColumn('createdAt', DateColumnType::class, [
                'label' => 'user.datatable.created_at',
                'sort' => true,
                'format' => 'd/m/Y',
                'value_attr' => [
                    'class' => 'w-[100px]'
                ]
            ]);

        // Row actions
        $builder
            ->addRowAction('edit', LinkActionType::class, [
                'label' => 'edit_datatable.edit',
                'href' => fn (User $user) => $this->urlGenerator->generate('admin_user_edit', ['id' => $user->getId()]),
                'attr' => [
                    'size' => 'sm',
                    'variant' => 'outline',
                    'data-turbo-frame' => 'dialog',
                    'target' => 'dialog',
                    'class' => 'whitespace-nowrap'
                ]
            ])
            ->addRowAction('delete', FormActionType::class, [
                'label' => 'delete_datatable.delete',
                'action' => fn (User $user) => $this->urlGenerator->generate('admin_user_delete', ['id' => $user->getId()]),
                'confirmation' => [
                    'type' => 'danger',
                    'label_title' => 'delete_datatable.delete_confirmation_title',
                    'label_description' => 'delete_datatable.delete_confirmation_description',
                    'label_confirm' => 'delete_datatable.delete_confirmation_confirm',
                    'label_cancel' => 'delete_datatable.delete_confirmation_cancel',
                    'translation_domain' => 'messages',
                ],
                'method' => 'POST',
                'button_attr' => [
                    'variant' => 'ghost',
                    'size' => 'sm',
                    'class' => 'px-2 text-destructive hover:text-destructive hover:bg-red-100 hover:rounded-md whitespace-nowrap'
                ]
            ]);

 }

    private function handleSearchFilter(DoctrineOrmProxyQuery $query, string $search): void
    {
        $alias = current($query->getRootAliases());
        $parameter = $query->getUniqueParameterId();

        $criteria = $query->expr()->orX(
            $query->expr()->like("LOWER($alias.email)", ":$alias$parameter"),
            $query->expr()->like("LOWER($alias.firstName)", ":$alias$parameter"),
            $query->expr()->like("LOWER($alias.lastName)", ":$alias$parameter"),
            $query->expr()->like("LOWER($alias.phone)", ":$alias$parameter"),
        );

        $query
            ->andWhere($criteria)
            ->setParameter("$alias$parameter", '%' . strtolower($search) . '%');
    }
}