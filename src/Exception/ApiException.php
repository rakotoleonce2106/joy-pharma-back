<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Custom API Exception with status code support
 */
class ApiException extends HttpException
{
    // Error status codes
    public const REQUEST_ACTIVATION = 'REQUEST_ACTIVATION';
    public const INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    public const ACCOUNT_LOCKED = 'ACCOUNT_LOCKED';
    public const VALIDATION_ERROR = 'VALIDATION_ERROR';
    public const NOT_FOUND = 'NOT_FOUND';
    public const FORBIDDEN = 'FORBIDDEN';
    public const CONFLICT = 'CONFLICT';
    public const SERVER_ERROR = 'SERVER_ERROR';

    private string $statusCode;

    public function __construct(
        string $message,
        string $statusCode = self::SERVER_ERROR,
        int $httpStatusCode = 400,
        ?\Throwable $previous = null,
        array $headers = [],
        ?int $code = 0
    ) {
        parent::__construct($httpStatusCode, $message, $previous, $headers, $code);
        $this->statusCode = $statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->statusCode;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->getStatusCode(), // numeric HTTP status code
            'status' => $this->getErrorCode(), // custom machine-readable status string
            'message' => $this->getMessage(),
        ];
    }
}

