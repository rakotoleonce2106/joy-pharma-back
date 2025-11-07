<?php

namespace App\Security;

use App\Exception\ApiException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class AuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $message = $exception->getMessage() ?: 'Invalid credentials.';
        
        // Check for special cases
        if ($exception instanceof CustomUserMessageAuthenticationException) {
            $message = $exception->getMessage();
            if (str_contains(strtolower($message), 'activation') || str_contains(strtolower($message), 'awaiting')) {
                return new JsonResponse([
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'status' => ApiException::REQUEST_ACTIVATION,
                    'message' => $message,
                ], Response::HTTP_UNAUTHORIZED);
            }
        }
        
        return new JsonResponse([
            'code' => Response::HTTP_UNAUTHORIZED,
            'status' => ApiException::INVALID_CREDENTIALS,
            'message' => $message,
        ], Response::HTTP_UNAUTHORIZED);
    }
}

