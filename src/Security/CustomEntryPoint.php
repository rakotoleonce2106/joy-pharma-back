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
            
            return new JsonResponse([
                'code' => Response::HTTP_UNAUTHORIZED,
                'status' => ApiException::INVALID_CREDENTIALS,
                'message' => $message,
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        // For non-API requests, return plain text response
        return new Response('Unauthorized Access', Response::HTTP_UNAUTHORIZED);
    }
}
