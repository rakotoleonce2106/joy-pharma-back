<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ReportIssueInput;
use App\Entity\Issue;
use App\Entity\IssueType;
use App\Entity\User;
use App\Repository\IssueRepository;
use App\Repository\OrderRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReportIssueProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly IssueRepository $issueRepository,
        private readonly Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException('User not found');
        }

        $orderId = $uriVariables['id'] ?? null;
        if (!$orderId) {
            throw new NotFoundHttpException('Order ID not provided');
        }

        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            throw new NotFoundHttpException('Order not found');
        }

        // Check if user is related to the order (either customer or delivery person)
        if ($order->getOwner() !== $user && $order->getDeliver() !== $user) {
            throw new AccessDeniedHttpException('You are not authorized to report issues for this order');
        }

        /** @var ReportIssueInput $input */
        $input = $data;

        // Map string type to enum
        $issueType = match ($input->type) {
            'damaged_product' => IssueType::DAMAGED_PRODUCT,
            'wrong_address' => IssueType::WRONG_ADDRESS,
            'customer_unavailable' => IssueType::CUSTOMER_UNAVAILABLE,
            'other' => IssueType::OTHER,
            default => IssueType::OTHER
        };

        // Create issue
        $issue = new Issue();
        $issue->setOrderRef($order);
        $issue->setReportedBy($user);
        $issue->setType($issueType);
        $issue->setDescription($input->description);

        $this->issueRepository->save($issue, true);

        return [
            'success' => true,
            'message' => 'Issue reported successfully',
            'data' => [
                'issueId' => $issue->getId(),
                'type' => $input->type,
                'status' => 'open'
            ]
        ];
    }
}





