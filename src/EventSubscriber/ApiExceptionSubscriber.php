<?php

namespace App\EventSubscriber;

use App\Exception\ApiException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.exception' => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        
        // Only handle API routes
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $response = $this->handleException($exception);
        
        if ($response) {
            $event->setResponse($response);
        }
    }

    private function handleException(\Throwable $exception): ?JsonResponse
    {
        // Handle our custom ApiException
        if ($exception instanceof ApiException) {
            return new JsonResponse(
                $exception->toArray(),
                $exception->getStatusCode() // numeric HTTP code from HttpException
            );
        }

        // Handle CustomUserMessageAuthenticationException (for backward compatibility)
        if ($exception instanceof CustomUserMessageAuthenticationException) {
            $message = $exception->getMessage();
            if (str_contains(strtolower($message), 'activation') || str_contains(strtolower($message), 'awaiting')) {
                return new JsonResponse([
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'status' => ApiException::REQUEST_ACTIVATION,
                    'message' => $message,
                ], Response::HTTP_UNAUTHORIZED);
            }
            
            return new JsonResponse([
                'code' => Response::HTTP_UNAUTHORIZED,
                'status' => ApiException::INVALID_CREDENTIALS,
                'message' => $message ?: 'Authentication failed.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Handle AuthenticationException
        if ($exception instanceof AuthenticationException) {
            return new JsonResponse([
                'code' => Response::HTTP_UNAUTHORIZED,
                'status' => ApiException::INVALID_CREDENTIALS,
                'message' => $exception->getMessage() ?: 'Authentication failed.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Handle HttpException types
        if ($exception instanceof HttpException) {
            $status = $this->mapHttpExceptionToStatus($exception);
            $httpCode = $exception->getStatusCode();
            
            return new JsonResponse([
                'code' => $httpCode,
                'status' => $status,
                'message' => $exception->getMessage() ?: 'An error occurred.',
            ], $httpCode);
        }

        // Handle AuthenticationCredentialsNotFoundException
        if ($exception instanceof AuthenticationCredentialsNotFoundException) {
            return new JsonResponse([
                'code' => Response::HTTP_FORBIDDEN,
                'status' => ApiException::FORBIDDEN,
                'message' => 'Authentication required.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Handle AccessDeniedHttpException
        if ($exception instanceof AccessDeniedHttpException) {
            return new JsonResponse([
                'code' => Response::HTTP_FORBIDDEN,
                'status' => ApiException::FORBIDDEN,
                'message' => $exception->getMessage() ?: 'Access denied.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Handle NotFoundHttpException
        if ($exception instanceof NotFoundHttpException) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'status' => ApiException::NOT_FOUND,
                'message' => $exception->getMessage() ?: 'Resource not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Handle ConflictHttpException
        if ($exception instanceof ConflictHttpException) {
            return new JsonResponse([
                'code' => Response::HTTP_CONFLICT,
                'status' => ApiException::CONFLICT,
                'message' => $exception->getMessage() ?: 'Conflict occurred.',
            ], Response::HTTP_CONFLICT);
        }

        // Handle BadRequestHttpException
        if ($exception instanceof BadRequestHttpException) {
            return new JsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'status' => ApiException::VALIDATION_ERROR,
                'message' => $exception->getMessage() ?: 'Invalid request.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Default: server error for unhandled exceptions
        return new JsonResponse([
            'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'status' => ApiException::SERVER_ERROR,
            'message' => 'An internal server error occurred.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function mapHttpExceptionToStatus(HttpException $exception): string
    {
        $statusCode = $exception->getStatusCode();

        return match ($statusCode) {
            401 => ApiException::INVALID_CREDENTIALS,
            403 => ApiException::FORBIDDEN,
            404 => ApiException::NOT_FOUND,
            409 => ApiException::CONFLICT,
            400 => ApiException::VALIDATION_ERROR,
            default => ApiException::SERVER_ERROR,
        };
    }
}

