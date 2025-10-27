<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\OwnerInput;
use App\Entity\Cart;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

readonly class CartProcessor implements ProcessorInterface
{
    public function __construct(
        private Security          $security,
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Cart
    {
        if (!$data instanceof OwnerInput) {
            throw new \LogicException('Invalid input data');
        }

        if (!$this->security->getUser()) {
            throw new AccessDeniedException('User not authenticated.');
        }


        $user = $this->security->getUser();
        // check if product is alreay in favorite
        $cart = $this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user, 'product' => $data->productId]);
        if ($cart) {
            throw new \InvalidArgumentException('Product already in cart.');
        }

        $product = $this->productRepository->find($data->productId);
        if (!$product) {
            throw new \InvalidArgumentException('Product not found.');
        }
        $cart = new Cart();
        $cart->setUser($user);
        $cart->setQuantity($data->quantity);
        $cart->setCreatedAtValue(new \DateTimeImmutable());
        $cart->setProduct($product);
        $this->entityManager->persist($cart);
        $this->entityManager->flush();
        return $cart;
    }
}
