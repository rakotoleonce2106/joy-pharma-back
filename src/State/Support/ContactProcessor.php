<?php

namespace App\State\Support;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\SupportTicketInput;
use App\Entity\SupportTicket;
use App\Entity\TicketPriority;
use App\Entity\User;
use App\Repository\SupportTicketRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ContactProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly SupportTicketRepository $ticketRepository,
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

        /** @var SupportTicketInput $input */
        $input = $data;

        // Map string priority to enum
        $priority = match ($input->priority) {
            'low' => TicketPriority::LOW,
            'normal' => TicketPriority::NORMAL,
            'high' => TicketPriority::HIGH,
            'urgent' => TicketPriority::URGENT,
            default => TicketPriority::NORMAL
        };

        // Create support ticket
        $ticket = new SupportTicket();
        $ticket->setUser($user);
        $ticket->setSubject($input->subject);
        $ticket->setMessage($input->message);
        $ticket->setPriority($priority);

        $this->ticketRepository->save($ticket, true);

        return [
            'success' => true,
            'message' => 'Support ticket created successfully',
            'data' => [
                'ticketId' => $ticket->getId(),
                'subject' => $ticket->getSubject(),
                'priority' => $input->priority,
                'status' => 'open',
                'expectedResponse' => $this->getExpectedResponseTime($priority)
            ]
        ];
    }

    private function getExpectedResponseTime(TicketPriority $priority): string
    {
        return match ($priority) {
            TicketPriority::URGENT => 'Within 1 hour',
            TicketPriority::HIGH => 'Within 4 hours',
            TicketPriority::NORMAL => 'Within 24 hours',
            TicketPriority::LOW => 'Within 48 hours',
        };
    }
}


