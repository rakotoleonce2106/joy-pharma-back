<?php

declare(strict_types=1);

namespace App\DataTable\Type;

use App\Entity\Promotion;
use Kreyu\Bundle\DataTableBundle\Action\Type\LinkActionType;
use Kreyu\Bundle\DataTableBundle\Bridge\Doctrine\Orm\Query\DoctrineOrmProxyQuery;
use Kreyu\Bundle\DataTableBundle\Bridge\Doctrine\Orm\Filter\Type\StringFilterType;
use Kreyu\Bundle\DataTableBundle\Column\Type\TextColumnType;
use Kreyu\Bundle\DataTableBundle\DataTableBuilderInterface;
use Kreyu\Bundle\DataTableBundle\Pagination\PaginationData;
use Kreyu\Bundle\DataTableBundle\Type\AbstractDataTableType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PromotionDataTableType extends AbstractDataTableType
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator) {}

    public function buildDataTable(DataTableBuilderInterface $builder, array $options): void
    {
        $builder->setDefaultPaginationData(new PaginationData(page: 1, perPage: 13));
        $builder->setSearchHandler($this->handleSearchFilter(...));

        // Create action
        $builder->addAction('create', LinkActionType::class, [
            'label' => 'promotion.datatable.create_new',
            'href' => $this->urlGenerator->generate('admin_promotion_new'),
            'attr' => [
                'variant' => 'default',
                'data-turbo-frame' => '_top',
            ],
            'icon_attr' => [
                'name' => 'radix-icons:plus',
                'class' => 'w-5 h-5 mr-2'
            ]
        ]);

        // Filters
        $builder
            ->addFilter('id', StringFilterType::class, ['label' => 'promotion.datatable.id'])
            ->addFilter('code', StringFilterType::class, ['label' => 'promotion.datatable.code'])
            ->addFilter('name', StringFilterType::class, ['label' => 'promotion.datatable.name']);

        // Columns
        $builder
            ->addColumn('id', TextColumnType::class, [
                'label' => 'promotion.datatable.id',
                'sort' => true,
                'value_attr' => [
                    'class' => 'w-20 text-center'
                ],
                'header_attr' => [
                    'class' => 'text-center'
                ]
            ])
            ->addColumn('code', TextColumnType::class, [
                'label' => 'promotion.datatable.code',
                'sort' => true,
                'value_attr' => [
                    'class' => 'px-4 font-mono font-semibold'
                ]
            ])
            ->addColumn('name', TextColumnType::class, [
                'label' => 'promotion.datatable.name',
                'sort' => true,
                'value_attr' => [
                    'class' => 'px-4'
                ]
            ])
            ->addColumn('discountType', TextColumnType::class, [
                'label' => 'promotion.datatable.discount_type',
                'sort' => true,
                'formatter' => fn($value, Promotion $promotion) => $promotion->getDiscountType() === \App\Entity\DiscountType::PERCENTAGE 
                    ? 'Percentage' 
                    : 'Fixed Amount',
                'value_attr' => [
                    'class' => 'px-4'
                ]
            ])
            ->addColumn('discountValue', TextColumnType::class, [
                'label' => 'promotion.datatable.discount_value',
                'sort' => true,
                'formatter' => fn($value, Promotion $promotion) => $promotion->getDiscountType() === \App\Entity\DiscountType::PERCENTAGE 
                    ? number_format($promotion->getDiscountValue(), 2) . '%'
                    : number_format($promotion->getDiscountValue(), 2) . ' Ar',
                'value_attr' => [
                    'class' => 'px-4'
                ]
            ])
            ->addColumn('usageCount', TextColumnType::class, [
                'label' => 'promotion.datatable.usage_count',
                'sort' => true,
                'formatter' => fn($value, Promotion $promotion) => $promotion->getUsageLimit() 
                    ? $promotion->getUsageCount() . ' / ' . $promotion->getUsageLimit()
                    : $promotion->getUsageCount(),
                'value_attr' => [
                    'class' => 'px-4 text-center'
                ],
                'header_attr' => [
                    'class' => 'text-center'
                ]
            ])
            ->addColumn('isActive', TextColumnType::class, [
                'label' => 'promotion.datatable.is_active',
                'sort' => true,
                'formatter' => fn($value, Promotion $promotion) => $promotion->isActive() 
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-danger">Inactive</span>',
                'value_attr' => [
                    'class' => 'px-4 text-center'
                ],
                'header_attr' => [
                    'class' => 'text-center'
                ]
            ])
            ->addColumn('actions', TextColumnType::class, [
                'label' => 'promotion.datatable.actions',
                'sort' => false,
                'formatter' => fn($value, Promotion $promotion) => sprintf(
                    '<a href="%s" class="btn btn-sm btn-primary" data-turbo-frame="_top">Edit</a> ' .
                    '<form method="post" action="%s" class="inline-block" onsubmit="return confirm(\'Are you sure?\');">' .
                    '<input type="hidden" name="_token" value="%s">' .
                    '<button type="submit" class="btn btn-sm btn-danger">Delete</button>' .
                    '</form>',
                    $this->urlGenerator->generate('admin_promotion_edit', ['id' => $promotion->getId()]),
                    $this->urlGenerator->generate('admin_promotion_delete', ['id' => $promotion->getId()]),
                    '' // CSRF token would go here
                ),
                'value_attr' => [
                    'class' => 'px-4'
                ]
            ]);
    }

    private function handleSearchFilter(DoctrineOrmProxyQuery $query, string $search): void
    {
        $query->andWhere(
            $query->expr()->orX(
                $query->expr()->like('promotion.code', ':search'),
                $query->expr()->like('promotion.name', ':search'),
                $query->expr()->like('promotion.description', ':search')
            )
        );
        $query->setParameter('search', '%' . $search . '%');
    }
}

