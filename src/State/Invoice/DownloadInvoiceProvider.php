<?php

namespace App\State\Invoice;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\InvoiceRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DownloadInvoiceProvider implements ProviderInterface
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
            throw new AccessDeniedHttpException('User not found');
        }

        $invoiceId = $uriVariables['id'] ?? null;
        if (!$invoiceId) {
            throw new NotFoundHttpException('Invoice ID not provided');
        }

        $invoice = $this->invoiceRepository->find($invoiceId);

        if (!$invoice) {
            throw new NotFoundHttpException('Invoice not found');
        }

        // Check if invoice belongs to user
        if ($invoice->getDeliveryPerson() !== $user) {
            throw new AccessDeniedHttpException('You are not authorized to download this invoice');
        }

        // TODO: Implement actual PDF generation
        return [
            'success' => true,
            'message' => 'PDF generation not implemented yet',
            'data' => [
                'reference' => $invoice->getReference(),
                'downloadUrl' => '/invoices/' . $invoice->getId() . '.pdf',
                'note' => 'PDF generation will be implemented in production'
            ]
        ];
    }
}





