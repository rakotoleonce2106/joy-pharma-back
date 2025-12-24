<?php

namespace App\State\Favorite;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\OwnerInput;
use App\Entity\Favorite;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

readonly class FavoriteProcessor implements ProcessorInterface
{
    public function __construct(
        private Security          $security,
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Favorite
    {
        if (!$data instanceof Favorite) {
            throw new \LogicException('Invalid input data');
        }

        if (!$this->security->getUser()) {
            throw new AccessDeniedException('User not authenticated.');
        }

        $user = $this->security->getUser();
        
        $product = $data->getProduct();
        if (!$product) {
            throw new \InvalidArgumentException('Product is required.');
        }

        // check if product is already in favorite
        $existingFavorite = $this->entityManager->getRepository(Favorite::class)->findOneBy([
            'user' => $user, 
            'product' => $product
        ]);
        
        if ($existingFavorite) {
            throw new \InvalidArgumentException('Product already in favorite.');
        }

        $data->setUser($user);
        $data->setCreatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($data);
        $this->entityManager->flush();
        
        return $data;
    }
}
