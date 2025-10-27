<?php

declare(strict_types=1);

namespace App\DataTable\Type;

use App\Entity\StoreProduct;
use Kreyu\Bundle\DataTableBundle\Type\AbstractDataTableType;
use Kreyu\Bundle\DataTableBundle\DataTableBuilderInterface;
use Kreyu\Bundle\DataTableBundle\Column\Type\TextColumnType;
use Kreyu\Bundle\DataTableBundle\Action\Type\ButtonActionType;
use Kreyu\Bundle\DataTableBundle\Bridge\Doctrine\Orm\Filter\Type\StringFilterType;
use Kreyu\Bundle\DataTableBundle\Pagination\PaginationData;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoreProductDataTableType extends AbstractDataTableType
{
    public function buildDataTable(DataTableBuilderInterface $builder, array $options): void
    {
        $builder->setDefaultPaginationData(new PaginationData(page: 1, perPage: 10));

        $builder
            ->addFilter('product', StringFilterType::class, [
                'label' => 'Product Name',
                'query_path' => 'product.name',
                'form_options' => [
                    'attr' => [
                        'placeholder' => 'Search products...',
                    ],
                ],
            ])
            ->addFilter('brand', StringFilterType::class, [
                'label' => 'Brand',
                'query_path' => 'product.brand.name',
                'form_options' => [
                    'attr' => [
                        'placeholder' => 'Filter by brand...',
                    ],
                ],
            ]);

        $builder
            ->addColumn('product', TextColumnType::class, [
                'label' => 'Product',
                'sort' => true,
                'getter' => function (StoreProduct $storeProduct) {
                    return $storeProduct->getProduct()->getName();
                }
            ])
            ->addColumn('brand', TextColumnType::class, [
                'label' => 'Brand',
                'sort' => true,
                'getter' => function (StoreProduct $storeProduct) {
                    return $storeProduct->getProduct()->getBrand()?->getName() ?? 'No brand';
                }
            ])
            ->addColumn('unitPrice', TextColumnType::class, [
                'label' => 'Unit Price',
                'sort' => true,
                'getter' => function (StoreProduct $storeProduct) {
                    $value = $storeProduct->getUnitPrice();
                    return $value ? number_format($value, 2) . ' Ar' : 'N/A';
                }
            ])
            ->addColumn('price', TextColumnType::class, [
                'label' => 'Total Price',
                'sort' => true,
                'getter' => function (StoreProduct $storeProduct) {
                    return number_format($storeProduct->getPrice(), 2) . ' Ar';
                }
            ])
            ->addColumn('stock', TextColumnType::class, [
                'label' => 'Stock',
                'sort' => true,
                'getter' => function (StoreProduct $storeProduct) {
                    return $storeProduct->getStock() . ' units';
                }
            ])
            ->addColumn('active', TextColumnType::class, [
                'label' => 'Status',
                'sort' => true,
                'getter' => function (StoreProduct $storeProduct) {
                    return $storeProduct->isActive() ? 'Active' : 'Inactive';
                }
            ])
            ->addRowAction('edit', ButtonActionType::class, [
                'href' => function (StoreProduct $storeProduct) use ($options) {
                    return $options['edit_route']([
                        'storeId' => $storeProduct->getStore()->getId(),
                        'id' => $storeProduct->getId()
                    ]);
                },
                'label' => 'Edit',
                'attr' => [
                    'data-turbo-frame' => '_top',
                ],
            ])
            ->addRowAction('delete', ButtonActionType::class, [
                'href' => function (StoreProduct $storeProduct) use ($options) {
                    return $options['delete_route']([
                        'storeId' => $storeProduct->getStore()->getId(),
                        'id' => $storeProduct->getId()
                    ]);
                },
                'label' => 'Delete',
                'confirmation' => true,
                'attr' => [
                    'data-turbo-method' => 'post',
                    'data-turbo-confirm' => 'Are you sure you want to remove this product from the store?',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'edit_route' => null,
            'delete_route' => null,
        ]);

        $resolver->setRequired(['edit_route', 'delete_route']);
        $resolver->setAllowedTypes('edit_route', 'callable');
        $resolver->setAllowedTypes('delete_route', 'callable');
    }
}

