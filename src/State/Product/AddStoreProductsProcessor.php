<?php
// api/src/State/Product/AddStoreProductsProcessor.php
namespace App\State\Product;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\StoreProductsInput;
use App\Entity\StoreProduct;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AddStoreProductsProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TokenStorageInterface $tokenStorage,
        private ValidatorInterface $validator,
        private RequestStack $requestStack,
        private ProductRepository $productRepository
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $storeProducts = [];
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new BadRequestHttpException('No request found');
        }

        // Get the current authenticated user
        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser() instanceof User) {
            throw new BadRequestHttpException('User not authenticated');
        }

        /** @var User $user */
        $user = $token->getUser();
        
        // Check user is a store owner
        if (!in_array('ROLE_STORE', $user->getRoles())) {
            throw new BadRequestHttpException('User must be a store owner');
        }
        
        if (!$user->getStore()) {
            throw new BadRequestHttpException('User does not have a store');
        }

        // Check data is StoreProductsInput
        if (!$data instanceof StoreProductsInput) {
            throw new BadRequestHttpException('Invalid input data type');
        }

        foreach ($data->items as $item) {
            $storeProduct = new StoreProduct();
            $storeProduct->setStore($user->getStore());
            $product = $this->productRepository->findOneBy(['id' => $item]);
            
            if (!$product) {
                throw new BadRequestHttpException('Product not found: ' . $item);
            }
            
            $storeProduct->setProduct($product);
            $this->entityManager->persist($storeProduct);
            $storeProducts[] = $storeProduct;
        }
        
        $this->entityManager->flush();
        
        return $storeProducts;
    }
}
