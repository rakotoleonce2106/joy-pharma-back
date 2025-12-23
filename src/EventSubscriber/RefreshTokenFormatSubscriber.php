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
        $pathInfo = $request->getPathInfo();
        
        // Force JSON format for specific endpoints
        $endpointsRequiringJson = [
            '/api/token/refresh',
            '/api/media_objects',
        ];
        
        foreach ($endpointsRequiringJson as $endpoint) {
            if ($pathInfo === $endpoint || str_starts_with($pathInfo, $endpoint)) {
                // Remove HTML from accepted formats
                $request->setRequestFormat('json');
                
                // Force Accept header to JSON (but keep multipart for POST media_objects)
                if ($pathInfo === '/api/media_objects' && $request->getMethod() === 'POST') {
                    // For POST requests, we accept multipart/form-data for input
                    // but force JSON for output
                    if ($request->headers->has('Accept') && 
                        str_contains($request->headers->get('Accept', ''), 'text/html')) {
                        $request->headers->set('Accept', 'application/json');
                    }
                } else {
                    // For other endpoints, force JSON
                    if (!$request->headers->has('Accept') || 
                        str_contains($request->headers->get('Accept', ''), 'text/html')) {
                        $request->headers->set('Accept', 'application/json');
                    }
                }
                break;
            }
        }
    }
}

