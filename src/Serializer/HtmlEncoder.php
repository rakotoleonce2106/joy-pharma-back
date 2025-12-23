<?php

namespace App\Serializer;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * Minimal HTML encoder to prevent "format html is not supported" errors
 * This encoder will reject HTML encoding requests and suggest JSON instead
 * But allows HTML for documentation routes (/docs)
 */
class HtmlEncoder implements EncoderInterface, DecoderInterface
{
    public const FORMAT = 'html';

    public function __construct(
        private readonly ?RequestStack $requestStack = null
    ) {
    }

    public function encode(mixed $data, string $format, array $context = []): string
    {
        // Allow HTML for documentation routes
        if ($this->isDocumentationRoute()) {
            // Return empty string to let API Platform handle it natively
            return '';
        }

        // For API endpoints, return JSON instead of HTML
        // This prevents serialization errors when HTML format is requested
        return json_encode([
            'error' => 'HTML format is not supported for API endpoints. Please use application/json.',
            'message' => 'This API endpoint only supports JSON format. Please set Accept header to application/json.',
        ], JSON_THROW_ON_ERROR);
    }

    public function decode(string $data, string $format, array $context = []): mixed
    {
        // Allow HTML for documentation routes
        if ($this->isDocumentationRoute()) {
            return $data;
        }

        // HTML decoding is not supported for API
        throw new \RuntimeException('HTML format is not supported for API endpoints. Please use application/json.');
    }

    public function supportsEncoding(string $format): bool
    {
        if (self::FORMAT !== $format) {
            return false;
        }

        // Don't intercept documentation routes - let API Platform handle them
        if ($this->isDocumentationRoute()) {
            return false;
        }

        return true;
    }

    public function supportsDecoding(string $format): bool
    {
        if (self::FORMAT !== $format) {
            return false;
        }

        // Don't intercept documentation routes - let API Platform handle them
        if ($this->isDocumentationRoute()) {
            return false;
        }

        return true;
    }

    private function isDocumentationRoute(): bool
    {
        if (!$this->requestStack) {
            return false;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }

        $pathInfo = $request->getPathInfo();
        
        // Allow HTML for Swagger UI and ReDoc documentation routes
        return str_starts_with($pathInfo, '/docs') || 
               str_starts_with($pathInfo, '/api/docs');
    }
}

