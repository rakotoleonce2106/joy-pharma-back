<?php

namespace App\State\Availability;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OnlineStatusProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
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

        $request = $context['request'] ?? null;
        if ($request instanceof Request) {
            $payload = json_decode($request->getContent(), true);
            $isOnline = $payload['isOnline'] ?? null;

            if ($isOnline === null) {
                throw new BadRequestHttpException('isOnline field is required');
            }

            $user->setIsOnline((bool) $isOnline);
            $this->em->flush();
        }

        return [
            'success' => true,
            'isOnline' => $user->isOnline(),
            'message' => $user->isOnline() ? 'You are now online' : 'You are now offline'
        ];
    }
}





