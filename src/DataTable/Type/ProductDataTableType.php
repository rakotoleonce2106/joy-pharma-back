<?php

declare(strict_types=1);

namespace App\DataTable\Type;

use App\Entity\Product;
use Kreyu\Bundle\DataTableBundle\Action\Type\FormActionType;
use Kreyu\Bundle\DataTableBundle\Action\Type\LinkActionType;
use Kreyu\Bundle\DataTableBundle\Bridge\Doctrine\Orm\Query\DoctrineOrmProxyQuery;
use Kreyu\Bundle\DataTableBundle\Bridge\Doctrine\Orm\Filter\Type\StringFilterType;
use Kreyu\Bundle\DataTableBundle\Column\Type\TextColumnType;
use Kreyu\Bundle\DataTableBundle\DataTableBuilderInterface;
use Kreyu\Bundle\DataTableBundle\Pagination\PaginationData;
use Kreyu\Bundle\DataTableBundle\Type\AbstractDataTableType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductDataTableType extends AbstractDataTableType
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator) {}

    public function buildDataTable(DataTableBuilderInterface $builder, array $options): void
    {
        $builder->setDefaultPaginationData(new PaginationData(page: 1, perPage: 13));
        $builder->setSearchHandler($this->handleSearchFilter(...));

        // Create action
        $builder->addAction('create', LinkActionType::class, [
            'label' => 'product.datatable.create_new',
            'href' => $this->urlGenerator->generate('admin_product_new'),
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
            ->addFilter('id', StringFilterType::class, ['label' => 'product.datatable.id'])
            ->addFilter('name', StringFilterType::class, ['label' => 'product.datatable.name']);

        // Columns
        $builder
            ->addColumn('id', TextColumnType::class, [
                'label' => 'product.datatable.id',
                'sort' => true,
                'value_attr' => [
                    'class' => 'w-20 text-center'
                ],
                'header_attr' => [
                    'class' => 'text-center'
                ]
            ])
            ->addColumn('name', TextColumnType::class, [
                'label' => 'product.datatable.name',
                'sort' => true,
                'value_attr' => [
                    'class' => 'px-4'
                ]
            ])
            ->addColumn('code', TextColumnType::class, [
                'label' => 'product.datatable.code',
                'sort' => true,
                'value_attr' => [
                    'class' => 'px-4'
                ]
            ])
            // ->addColumn('brand', TextColumnType::class, [
            //     'label' => 'product.datatable.brand',
            //     'sort' => true,
            //     'property_path' => 'brand.name',
            //     'value_attr' => [
            //         'class' => 'px-4'
            //     ]
            // ])
            ->addColumn('category', TextColumnType::class, [
                'label' => 'product.datatable.category',
                'sort' => false,
                'formatter' => fn($value, Product $product) => implode(', ', $product->getCategory()->map(
                    fn($category) => $category->getName()
                )->toArray()),
                'value_attr' => [
                    'class' => 'px-4 whitespace-nowrap'
                ]
            ])
             ->addColumn('unitPrice', TextColumnType::class, [
                'label' => 'product.datatable.price',
                'sort' => true,
                'value_attr' => [
                    'class' => 'px-4'
                ]
            ])


            ->addColumn('totalPrice', TextColumnType::class, [
                'label' => 'product.datatable.price',
                'sort' => true,
                'value_attr' => [
                    'class' => 'px-4'
                ]
            ])

            ->addColumn('form', TextColumnType::class, [
                'label' => 'product.datatable.form',
                'sort' => true,
                'property_path' => 'form.label',
                'value_attr' => [
                    'class' => 'px-4'
                ]
            ])
            ->addColumn('manufacturer', TextColumnType::class, [
                'label' => 'product.datatable.manufacturer',
                'sort' => true,
                'property_path' => 'manufacturer.name',
                'value_attr' => [
                    'class' => 'px-4'
                ]
            ])

            // ->addColumn('restricted', TextColumnType::class, [
            //     'label' => 'Restrictions',
            //     'data' => fn(Product $product) => implode(', ', $product->getRestricted()->map(
            //         fn($res) => $res->getLabel()
            //     )->toArray()),
            // ])
        ;


        // Row actions
        $builder
            ->addRowAction('edit', LinkActionType::class, [
                'label' => 'edit_datatable.edit',
                'href' => fn(Product $product) => $this->urlGenerator->generate('admin_product_edit', ['id' => $product->getId()]),
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
                'action' => fn(Product $product) => $this->urlGenerator->generate('admin_product_delete', ['id' => $product->getId()]),
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
                'action' => $this->urlGenerator->generate('admin_product_batch_delete'),
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
