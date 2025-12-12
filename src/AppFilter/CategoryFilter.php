<?php

namespace App\AppFilter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class CategoryFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($property !== 'category') {
            return;
        }

        $categoryIds = is_array($value) ? $value : [$value];
        $rootAlias = $queryBuilder->getRootAliases()[0];

        // Pour chaque ID de catégorie, créer une jointure dédiée
        foreach ($categoryIds as $key => $id) {
            $joinAlias = $queryNameGenerator->generateJoinAlias('category');
            $queryBuilder
                ->join("$rootAlias.category", $joinAlias)
                ->andWhere("$joinAlias.id = :category_$key")
                ->setParameter("category_$key", (int) $id);
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'category' => [
                'property' => 'category',
                'type' => 'array',
                'required' => false,
                'description' => 'Filter products that have ALL specified category IDs (AND logic)',
                'openapi' => [
                    'style' => 'form',
                    'explode' => false,
                ],
            ],
        ];
    }
}