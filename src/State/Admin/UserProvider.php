<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserProvider implements ProviderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $type = $context['filters']['type'] ?? 'all';
        $role = $context['filters']['role'] ?? null;

        $queryBuilder = $this->userRepository->createQueryBuilder('u')
            ->leftJoin('u.delivery', 'delivery')
            ->addSelect('delivery');

        // Apply role filter if provided
        if ($role) {
            if ($role === 'ROLE_USER') {
                $users = $this->userRepository->findCustomersForDataTable();
            } else {
                $users = $this->userRepository->findByRole($role);
            }
            $ids = array_map(fn($u) => $u->getId(), $users);
            if (!empty($ids)) {
                $queryBuilder->where('u.id IN (:roleIds)')
                    ->setParameter('roleIds', $ids);
            } else {
                return [];
            }
        } else {
            // Apply type filter only if role filter is not provided
            switch ($type) {
                case 'delivers':
                    $users = $this->userRepository->findByRole('ROLE_DELIVER');
                    $ids = array_map(fn($u) => $u->getId(), $users);
                    if (!empty($ids)) {
                        $queryBuilder->where('u.id IN (:ids)')
                            ->setParameter('ids', $ids);
                    } else {
                        return [];
                    }
                    break;
                case 'stores':
                    $users = $this->userRepository->findByRole('ROLE_STORE');
                    $ids = array_map(fn($u) => $u->getId(), $users);
                    if (!empty($ids)) {
                        $queryBuilder->where('u.id IN (:ids)')
                            ->setParameter('ids', $ids);
                    } else {
                        return [];
                    }
                    break;
                case 'customers':
                    $users = $this->userRepository->findCustomersForDataTable();
                    $ids = array_map(fn($u) => $u->getId(), $users);
                    if (!empty($ids)) {
                        $queryBuilder->where('u.id IN (:ids)')
                            ->setParameter('ids', $ids);
                    } else {
                        return [];
                    }
                    break;
                case 'all':
                default:
                    // No filter
                    break;
            }
        }

        return $queryBuilder->getQuery()->getResult();
    }
}

