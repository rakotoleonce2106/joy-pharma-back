<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Exception for validation failures with violations
 */
class ValidationFailedException extends BadRequestHttpException
{
    private ConstraintViolationListInterface $violations;

    public function __construct(
        ConstraintViolationListInterface $violations,
        string $message = 'Validation failed',
        ?\Throwable $previous = null,
        int $code = 0,
        array $headers = []
    ) {
        parent::__construct($message, $previous, $code, $headers);
        $this->violations = $violations;
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }
}

