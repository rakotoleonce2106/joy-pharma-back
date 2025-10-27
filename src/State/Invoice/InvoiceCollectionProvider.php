<?php

namespace App\State\Invoice;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\InvoiceRepository;
use Symfony\Bundle\SecurityBundle\Security;

class InvoiceCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
        private readonly Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user) {
            return [];
        }

        $page = max(1, (int) ($context['filters']['page'] ?? 1));
        $limit = min(50, max(1, (int) ($context['filters']['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        return $this->invoiceRepository->findByDeliveryPerson($user, $limit, $offset);
    }
}


