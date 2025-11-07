<?php

namespace App\EventSubscriber;

use App\Exception\ApiException;
use App\Exception\ValidationFailedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.exception' => ['onKernelException', -10], // Lower priority to run after API Platform/Lexik error handlers
            'kernel.response' => ['onKernelResponse', -10], // Also catch responses after they're set
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        
        // Only handle API routes
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        // Check if a response is already set (e.g., by API Platform or Lexik)
        $existingResponse = $event->getResponse();
        if ($existingResponse) {
            $content = $existingResponse->getContent();
            $contentType = $existingResponse->headers->get('Content-Type', '');
            
            // Check if response is JSON (either JsonResponse or Response with JSON content)
            if (str_contains($contentType, 'application/json') || $existingResponse instanceof JsonResponse) {
                $data = json_decode($content, true);
                
                // If response has 'code' and 'message' but no 'status', add it
                if (is_array($data) && isset($data['code']) && isset($data['message']) && !isset($data['status'])) {
                    $status = $this->mapStatusCodeToStatus($data['code']);
                    $data['status'] = $status;
                    $event->setResponse(new JsonResponse($data, $data['code']));
                    return;
                }
                // If response already has status, don't modify it
                if (is_array($data) && isset($data['status'])) {
                    return;
                }
            }
        }

        // If no response is set yet, handle the exception
        $exception = $event->getThrowable();
        $response = $this->handleException($exception);
        
        if ($response) {
            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        
        // Only handle API routes
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $response = $event->getResponse();
        if (!$response) {
            return;
        }

        $content = $response->getContent();
        if (empty($content)) {
            return;
        }

        $contentType = $response->headers->get('Content-Type', '');
        
        // Check if response is JSON (either JsonResponse or Response with JSON content)
        if (str_contains($contentType, 'application/json') || $response instanceof JsonResponse) {
            $data = json_decode($content, true);
            
            // If response has 'code' and 'message' but no 'status', add it
            if (is_array($data) && isset($data['code']) && isset($data['message']) && !isset($data['status'])) {
                $status = $this->mapStatusCodeToStatus($data['code']);
                $data['status'] = $status;
                $event->setResponse(new JsonResponse($data, $data['code']));
            }
        }
    }

    private function handleException(\Throwable $exception): ?JsonResponse
    {
        // Handle ValidationFailedException first (our custom exception with violations)
        if ($exception instanceof ValidationFailedException) {
            return $this->createValidationErrorResponse($exception->getViolations());
        }

        // Check if exception has violations in previous exception
        $violations = $this->extractViolations($exception);
        if ($violations !== null) {
            return $this->createValidationErrorResponse($violations);
        }

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

        // Handle UnauthorizedHttpException (used by Lexik JWT for expired tokens)
        if ($exception instanceof UnauthorizedHttpException) {
            $message = $exception->getMessage() ?: 'Invalid credentials.';
            
            return new JsonResponse([
                'code' => Response::HTTP_UNAUTHORIZED,
                'status' => ApiException::INVALID_CREDENTIALS,
                'message' => $message,
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
            // Check if message contains validation info or if previous exception has violations
            $violations = $this->extractViolations($exception);
            if ($violations !== null) {
                return $this->createValidationErrorResponse($violations);
            }

            // Try to parse validation errors from message
            $message = $exception->getMessage();
            if (str_contains(strtolower($message), 'validation') || str_contains(strtolower($message), 'violation')) {
                return $this->createValidationErrorResponse(null, $message);
            }

            return new JsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'status' => ApiException::VALIDATION_ERROR,
                'message' => $message ?: 'Invalid request.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Default: server error for unhandled exceptions
        // Log the actual exception for debugging but return a user-friendly error
        $message = 'An internal server error occurred.';
        
        // In dev mode, include more details
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev') {
            $message = $exception->getMessage() ?: $message;
            $message .= ' (' . get_class($exception) . ')';
        }
        
        return new JsonResponse([
            'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'status' => ApiException::SERVER_ERROR,
            'message' => $message,
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Extract ConstraintViolationListInterface from exception chain
     */
    private function extractViolations(\Throwable $exception): ?ConstraintViolationListInterface
    {
        // Check if exception has a getViolations method
        if (method_exists($exception, 'getViolations')) {
            /** @var mixed $violations */
            $violations = $exception->{'getViolations'}();
            if ($violations instanceof ConstraintViolationListInterface) {
                return $violations;
            }
        }

        // Check previous exception
        $previous = $exception->getPrevious();
        if ($previous !== null) {
            return $this->extractViolations($previous);
        }

        return null;
    }

    /**
     * Create a validation error response with violations
     */
    private function createValidationErrorResponse(
        ?ConstraintViolationListInterface $violations = null,
        ?string $fallbackMessage = null
    ): JsonResponse {
        $violationsArray = [];
        $messages = [];

        if ($violations !== null && $violations->count() > 0) {
            foreach ($violations as $violation) {
                $propertyPath = $violation->getPropertyPath();
                $message = $violation->getMessage();

                // Format property path (remove brackets if present)
                $field = str_replace(['[', ']'], '', $propertyPath);
                if (empty($field)) {
                    $field = 'root';
                }

                $violationsArray[] = [
                    'propertyPath' => $field,
                    'message' => $message,
                ];

                $messages[] = $message;
            }
        }

        $mainMessage = !empty($messages) 
            ? implode('. ', array_unique($messages))
            : ($fallbackMessage ?: 'Validation failed.');

        return new JsonResponse([
            'code' => Response::HTTP_BAD_REQUEST,
            'status' => ApiException::VALIDATION_ERROR,
            'message' => $mainMessage,
            'violations' => $violationsArray,
        ], Response::HTTP_BAD_REQUEST);
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

    private function mapStatusCodeToStatus(int $statusCode): string
    {
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

