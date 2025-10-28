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
        if (!$data instanceof OwnerInput) {
            throw new \LogicException('Invalid input data');
        }

        if (!$this->security->getUser()) {
            throw new AccessDeniedException('User not authenticated.');
        }


        $user = $this->security->getUser();
        // check if product is alreay in favorite
        $favorite = $this->entityManager->getRepository(Favorite::class)->findOneBy(['user' => $user, 'product' => $data->productId]);
        if ($favorite) {
            throw new \InvalidArgumentException('Product already in favorite.');
        }

        $product = $this->productRepository->find($data->productId);
        if (!$product) {
            throw new \InvalidArgumentException('Product not found.');
        }
        $favorite = new Favorite();
        $favorite->setUser($user);
        $favorite->setCreatedAt(new \DateTimeImmutable());
        $favorite->setProduct($product);
        $this->entityManager->persist($favorite);
        $this->entityManager->flush();
        return $favorite;
    }
}
