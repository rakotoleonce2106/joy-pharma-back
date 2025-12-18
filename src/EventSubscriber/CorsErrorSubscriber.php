<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Ensures CORS headers are always present on API responses, even on errors (401, 403, etc.)
 * This subscriber has a very low priority to run after all other exception handlers
 */
class CorsErrorSubscriber implements EventSubscriberInterface
{
    private const ALLOWED_ORIGINS = [
        'http://localhost:3000',
        'https://www.joy-pharma.com',
        'https://joy-pharma.com',
        'https://admin.joy-pharma.com',
        'https://back-preprod.joy-pharma.com',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            // Very low priority to run after all other handlers (including NelmioCorsBundle)
            KernelEvents::RESPONSE => ['onKernelResponse', -1024],
            KernelEvents::EXCEPTION => ['onKernelException', -1024],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Only handle API routes
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $this->addCorsHeaders($request, $response);
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Only handle API routes
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        // Get the response if it exists
        $response = $event->getResponse();
        if ($response) {
            $this->addCorsHeaders($request, $response);
        }
    }

    private function addCorsHeaders($request, $response): void
    {
        $origin = $request->headers->get('Origin');

        // If no Origin header, nothing to do
        if (!$origin) {
            return;
        }

        // Check if CORS headers are already set by NelmioCorsBundle
        if ($response->headers->has('Access-Control-Allow-Origin')) {
            return;
        }

        // Determine if origin is allowed
        $allowedOrigin = $this->getAllowedOrigin($origin);
        if (!$allowedOrigin) {
            return;
        }

        // Add CORS headers
        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
        $response->headers->set('Access-Control-Expose-Headers', 'Link');
        
        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            $response->headers->set('Access-Control-Max-Age', '3600');
        }
    }

    private function getAllowedOrigin(string $origin): ?string
    {
        // Check exact matches
        if (in_array($origin, self::ALLOWED_ORIGINS, true)) {
            return $origin;
        }

        // Check regex patterns for joy-pharma.com subdomains
        if (preg_match('/^https?:\/\/(.*\.)?joy-pharma\.com$/', $origin)) {
            return $origin;
        }

        // Check localhost with any port
        if (preg_match('/^https?:\/\/(localhost|127\.0\.0\.1)(:[0-9]+)?$/', $origin)) {
            return $origin;
        }

        return null;
    }
}

