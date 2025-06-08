<?php

declare(strict_types=1);

namespace App\DataTable\Type;

use App\Entity\Manufacturer;
use Kreyu\Bundle\DataTableBundle\Action\Type\FormActionType;
use Kreyu\Bundle\DataTableBundle\Action\Type\LinkActionType;
use Kreyu\Bundle\DataTableBundle\Bridge\Doctrine\Orm\Query\DoctrineOrmProxyQuery;
use Kreyu\Bundle\DataTableBundle\Bridge\Doctrine\Orm\Filter\Type\StringFilterType;
use Kreyu\Bundle\DataTableBundle\Column\Type\TextColumnType;
use Kreyu\Bundle\DataTableBundle\DataTableBuilderInterface;
use Kreyu\Bundle\DataTableBundle\Pagination\PaginationData;
use Kreyu\Bundle\DataTableBundle\Type\AbstractDataTableType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ManufacturerDataTableType extends AbstractDataTableType
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function buildDataTable(DataTableBuilderInterface $builder, array $options): void
    {
        $builder->setDefaultPaginationData(new PaginationData(page: 1, perPage: 13));
        $builder->setSearchHandler($this->handleSearchFilter(...));
        
        // Create action
        $builder->addAction('create', LinkActionType::class, [
            'label' => 'manufacturer.datatable.create_new',
            'href' => $this->urlGenerator->generate('admin_manufacturer_new'),
            'attr' => [
                'variant' => 'default',
                'data-turbo-frame' => 'dialog',
                'target' => 'dialog',
            ],
            'icon_attr' => [
                'name' => 'radix-icons:plus',
                'class' => 'w-5 h-5 mr-2'
            ]
        ]);

        // Filters
        $builder
            ->addFilter('id', StringFilterType::class, ['label' => 'manufacturer.datatable.id'])
            ->addFilter('name', StringFilterType::class, ['label' => 'manufacturer.datatable.name']);

        // Columns
        $builder
            ->addColumn('id', TextColumnType::class, [
                'label' => 'manufacturer.datatable.id',
                'sort' => true,
                'value_attr' => [
                    'class' => 'w-20 text-center'
                ],
                'header_attr' => [
                    'class' => 'text-center'
                ]
            ])

            ->addColumn('name', TextColumnType::class, [
                'label' => 'manufacturer.datatable.name',
                'sort' => true,
                'value_attr' => [
                    'class' => 'px-4'
                ]
            ]);

        // Row actions
        $builder
            ->addRowAction('edit', LinkActionType::class, [
                'label' => 'edit_datatable.edit',
                'href' => fn (Manufacturer $manufacturer) => $this->urlGenerator->generate('admin_manufacturer_edit', ['id' => $manufacturer->getId()]),
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
                'action' => fn (Manufacturer $manufacturer) => $this->urlGenerator->generate('admin_manufacturer_delete', ['id' => $manufacturer->getId()]),
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

        // Batch actions
        $builder
            ->addBatchAction('delete', FormActionType::class, [
                'label' => 'delete_datatable.delete_selected',
                'action' => $this->urlGenerator->generate('admin_manufacturer_batch_delete'),
                'confirmation' => [
                    'type' => 'danger',
                    'label_title' => 'delete_datatable.delete_selected_confirmation_title',
                    'label_description' => 'delete_datatable.delete_selected_confirmation_description',
                    'label_confirm' => 'delete_datatable.delete_selected_confirmation_confirm',
                    'label_cancel' => 'delete_datatable.delete_selected_confirmation_cancel',
                    'translation_domain' => 'messages',
                ],
                'method' => 'POST',
                'button_attr' => [
                    'variant' => 'destructive',
                    'size' => 'sm'
                ]
            ]);
    }

    private function handleSearchFilter(DoctrineOrmProxyQuery $query, string $search): void
    {
        $alias = current($query->getRootAliases());
        $parameter = $query->getUniqueParameterId();

        $criteria = $query->expr()->orX(
            $query->expr()->like("LOWER($alias.name)", ":$alias$parameter"),
        );

        $query
            ->andWhere($criteria)
            ->setParameter("$alias$parameter", '%' . strtolower($search) . '%');
    }
}