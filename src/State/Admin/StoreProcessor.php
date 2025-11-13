<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\StoreInput;
use App\Entity\Location;
use App\Entity\Store;
use App\Entity\User;
use App\Repository\StoreRepository;
use App\Repository\UserRepository;
use App\Service\StoreService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly StoreService $storeService,
        private readonly StoreRepository $storeRepository,
        private readonly UserService $userService,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Store
    {
        if (!$data instanceof StoreInput) {
            throw new BadRequestHttpException('Invalid input data');
        }

        $isUpdate = isset($uriVariables['id']);
        
        if ($isUpdate) {
            $store = $this->storeRepository->find($uriVariables['id']);
            if (!$store) {
                throw new NotFoundHttpException('Store not found');
            }
        } else {
            $store = new Store();
        }

        $store->setName($data->name);
        $store->setDescription($data->description);

        // Handle location
        if ($data->latitude && $data->longitude && $data->address) {
            $location = $store->getLocation();
            if (!$location) {
                $location = new Location();
            }
            $location->setLatitude($data->latitude);
            $location->setLongitude($data->longitude);
            $location->setAddress($data->address);
            $store->setLocation($location);
        }

        // Handle owner
        $owner = $this->userRepository->findOneBy(['email' => $data->ownerEmail]);
        if (!$owner) {
            $owner = new User();
            $owner->setEmail($data->ownerEmail);
            $owner->setFirstName($store->getName());
            $owner->setLastName('Store Owner');
            $owner->setRoles(['ROLE_STORE']);
            $this->userService->hashPassword($owner, '!Joy2025Pharam!');
            $this->userService->persistUser($owner);
        }
        $store->setOwner($owner);
        $owner->setStore($store);

        if ($isUpdate) {
            $this->storeService->updateStore($store);
        } else {
            $this->storeService->createStore($store);
        }

        return $store;
    }
}

