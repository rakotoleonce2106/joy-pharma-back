<?php

namespace App\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * EventSubscriber that transforms User entity response to JWT token format for /register endpoint
 * This replaces the need for RegisterCustomerProcessor
 */
class RegisterCustomerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuthenticationSuccessHandler $authenticationSuccessHandler
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => [
                ['transformRegisterResponse', EventPriorities::POST_WRITE],
            ],
        ];
    }

    public function transformRegisterResponse(ViewEvent $event): void
    {
        $request = $event->getRequest();
        
        // Only handle POST /api/register endpoint
        if ($request->getMethod() !== 'POST' || $request->getPathInfo() !== '/api/register') {
            return;
        }

        $result = $event->getControllerResult();
        
        // Only transform if result is a User entity
        if (!$result instanceof User) {
            return;
        }

        // Generate JWT token
        $jwtResponse = $this->authenticationSuccessHandler->handleAuthenticationSuccess($result);
        
        // Extract token data from the response
        $responseData = json_decode($jwtResponse->getContent(), true);

        // Transform response to include token and user data
        $transformedResponse = [
            'token' => $responseData['token'] ?? null,
            'refresh_token' => $responseData['refresh_token'] ?? null,
            'user' => $responseData['user'] ?? null,
        ];

        // Set the transformed response
        $event->setControllerResult($transformedResponse);
    }
}

