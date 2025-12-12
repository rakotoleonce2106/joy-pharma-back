<?php

namespace App\Serializer;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * Minimal HTML encoder to prevent "format html is not supported" errors
 * This encoder will reject HTML encoding requests and suggest JSON instead
 */
class HtmlEncoder implements EncoderInterface, DecoderInterface
{
    public const FORMAT = 'html';

    public function encode(mixed $data, string $format, array $context = []): string
    {
        // For API endpoints, return JSON instead of HTML
        // This prevents serialization errors when HTML format is requested
        return json_encode([
            'error' => 'HTML format is not supported for API endpoints. Please use application/json.',
            'message' => 'This API endpoint only supports JSON format. Please set Accept header to application/json.',
        ], JSON_THROW_ON_ERROR);
    }

    public function decode(string $data, string $format, array $context = []): mixed
    {
        // HTML decoding is not supported for API
        throw new \RuntimeException('HTML format is not supported for API endpoints. Please use application/json.');
    }

    public function supportsEncoding(string $format): bool
    {
        return self::FORMAT === $format;
    }

    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format;
    }
}

