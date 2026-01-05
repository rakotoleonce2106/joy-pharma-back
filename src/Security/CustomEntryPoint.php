<?php
namespace App\Security;

use App\Exception\ApiException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class CustomEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, ?\Throwable $authException = null): Response
    {
        // Check if this is an API request
        if (str_starts_with($request->getPathInfo(), '/api')) {
            $message = $authException?->getMessage() ?: 'Invalid credentials.';
            
            $response = new JsonResponse([
                'code' => Response::HTTP_UNAUTHORIZED,
                'status' => ApiException::INVALID_CREDENTIALS,
                'message' => $message,
            ], Response::HTTP_UNAUTHORIZED);
            
            // Add CORS headers for API requests
            $origin = $request->headers->get('Origin');
            if ($origin) {
                // Check if origin is allowed (same logic as CorsErrorSubscriber)
                $allowedOrigins = [
                    'http://localhost:3000',
                    'http://localhost:3001',
                    'https://www.joy-pharma.com',
                    'https://joy-pharma.com',
                    'https://admin.joy-pharma.com',
                    'https://back.joy-pharma.com',
                    'https://back-preprod.joy-pharma.com',
                ];
                
                $isAllowed = in_array($origin, $allowedOrigins, true) ||
                    preg_match('/^https?:\/\/(.*\.)?joy-pharma\.com$/', $origin) ||
                    preg_match('/^https?:\/\/(localhost|127\.0\.0\.1)(:[0-9]+)?$/', $origin);
                
                if ($isAllowed) {
                    $response->headers->set('Access-Control-Allow-Origin', $origin);
                    $response->headers->set('Access-Control-Allow-Credentials', 'true');
                    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
                    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
                }
            }
            
            return $response;
        }
        
        // For non-API requests, return plain text response
        return new Response('Unauthorized Access', Response::HTTP_UNAUTHORIZED);
    }
}
