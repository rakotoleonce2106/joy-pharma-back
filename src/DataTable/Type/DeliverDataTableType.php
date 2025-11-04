<?php

declare(strict_types=1);

namespace App\DataTable\Type;

use App\Entity\User;
use Kreyu\Bundle\DataTableBundle\Action\Type\FormActionType;
use Kreyu\Bundle\DataTableBundle\Action\Type\LinkActionType;
use Kreyu\Bundle\DataTableBundle\Bridge\Doctrine\Orm\Filter\Type\StringFilterType;
use Kreyu\Bundle\DataTableBundle\Bridge\Doctrine\Orm\Query\DoctrineOrmProxyQuery;
use Kreyu\Bundle\DataTableBundle\Column\Type\DateColumnType;
use Kreyu\Bundle\DataTableBundle\Column\Type\TextColumnType;
use Kreyu\Bundle\DataTableBundle\DataTableBuilderInterface;
use Kreyu\Bundle\DataTableBundle\Pagination\PaginationData;
use Kreyu\Bundle\DataTableBundle\Type\AbstractDataTableType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DeliverDataTableType extends AbstractDataTableType
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function buildDataTable(DataTableBuilderInterface $builder, array $options): void
    {
        $builder->setDefaultPaginationData(new PaginationData(page: 1, perPage: 13));
        $builder->setSearchHandler($this->handleSearchFilter(...));

        $builder
            ->addFilter('id', StringFilterType::class, ['label' => 'user.datatable.id'])
            ->addFilter('firstName', StringFilterType::class, ['label' => 'user.datatable.first_name']);

        $builder
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
            ->addColumn('email', TextColumnType::class, [
                'label' => 'user.datatable.email',
                'sort' => true,
                'value_attr' => [
                    'class' => 'px-4'
                ]
            ])
            ->addColumn('firstName', TextColumnType::class, [
                'label' => 'user.datatable.first_name',
                'sort' => true,
                'value_attr' => [
                    'class' => 'px-4'
                ]
            ])
            ->addColumn('lastName', TextColumnType::class, [
                'label' => 'user.datatable.last_name',
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
                'label' => 'Active',
                'sort' => true,
                'value_attr' => [
                    'class' => 'px-4'
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
        );

        $query
            ->andWhere($criteria)
            ->setParameter("$alias$parameter", '%' . strtolower($search) . '%');
    }

}


