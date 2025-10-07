<?php
// api/src/State/OrderCreateProcessor.php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\StoreProductsInput;
use App\Entity\Order;
use App\Entity\StoreProduct;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AddStoreProductsProssessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TokenStorageInterface $tokenStorage,
        private ValidatorInterface $validator,
        private RequestStack $requestStack,
        private ProductRepository $productRepository
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Order
    {
        $storeProducts=[];
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new BadRequestHttpException('No request found');
        }

        // Get the current authenticated user
        $token = $this->tokenStorage->getToken();
        $user = !$token->getUser();
        if (!$token || !$user instanceof User || $user->getRoles()[] = 'ROLE_STORE') {
            throw new BadRequestHttpException('User not authenticated');
        }

        // Check data is OrderInput
        if (!$data instanceof StoreProductsInput) {
            throw new BadRequestHttpException('Invalid input data type');
        }

        foreach ($data->items as $item) {
            $storeProduct = new StoreProduct();
            $storeProduct->setStore($user->getStore());
            $product = $this->productRepository->findOneBy(['id' => 'item']);
            $storeProduct->setProduct($product);
            $this->entityManager->persist($storeProduct);
            $storeProducts->add($storeProduct);
        }
        $this->entityManager->flush();
        return $storeProducts;
    }
}
