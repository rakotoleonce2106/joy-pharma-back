<?php

namespace App\State\Product;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\ProductRepository;
use Symfony\Bundle\SecurityBundle\Security;

class SuggestionProductsProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private ProductRepository $productRepository
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        // $user = $this->security->getUser();

        // if (!$user instanceof User) {
        //     return [];
        // }
        
        // find all product randomly
        return $this->productRepository->findTopSells();
    }
}
