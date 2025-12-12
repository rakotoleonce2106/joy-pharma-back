<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RefreshTokenFormatSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Force JSON format for refresh token endpoint
        if ($request->getPathInfo() === '/api/token/refresh') {
            // Remove HTML from accepted formats
            $request->setRequestFormat('json');
            
            // Force Accept header to JSON
            if (!$request->headers->has('Accept') || 
                str_contains($request->headers->get('Accept', ''), 'text/html')) {
                $request->headers->set('Accept', 'application/json');
            }
        }
    }
}

