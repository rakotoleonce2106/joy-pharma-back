<?php

namespace App\Serializer;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

final class MultipartDecoder implements DecoderInterface
{
    public const FORMAT = 'multipart';

    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    public function decode(string $data, string $format, array $context = []): ?array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        $decoded = [];
        
        // Process form data values
        foreach ($request->request->all() as $key => $value) {
            // Try to decode as JSON first (for complex values)
            if (is_string($value) && !empty($value)) {
                $decodedValue = json_decode($value, true);
                // If JSON decode succeeded and result is different from original, use decoded value
                if (json_last_error() === JSON_ERROR_NONE && $decodedValue !== null) {
                    $decoded[$key] = $decodedValue;
                } else {
                    // If not JSON or decode failed, use value as-is
                    $decoded[$key] = $value;
                }
            } else {
                // For non-string values or empty strings, use as-is
                $decoded[$key] = $value;
            }
        }
        
        // Add files
        return $decoded + $request->files->all();
    }

    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format;
    }
}

