<?php

namespace App\Serializer;

use App\Entity\BusinessHours;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;

final class BusinessHoursDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED = 'BUSINESS_HOURS_DENORMALIZER_ALREADY_CALLED';

    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        $context[self::ALREADY_CALLED] = true;

        // Only process if data is an array (JSON deserialization)
        if (!is_array($data)) {
            return $this->denormalizer->denormalize($data, $type, $format, $context);
        }

        // Convert string times to DateTime objects ONLY if they are non-empty strings
        // Leave null values as null - they should not be converted
        if (isset($data['openTime']) && $data['openTime'] !== null && $data['openTime'] !== '') {
            if (is_string($data['openTime'])) {
                try {
                    $parsedTime = \DateTime::createFromFormat('H:i', $data['openTime']);
                    if ($parsedTime === false) {
                        $parsedTime = \DateTime::createFromFormat('H:i:s', $data['openTime']);
                    }
                    if ($parsedTime !== false) {
                        $data['openTime'] = $parsedTime;
                    }
                } catch (\Exception $e) {
                    // Keep as string if parsing fails
                }
            }
        }

        if (isset($data['closeTime']) && $data['closeTime'] !== null && $data['closeTime'] !== '') {
            if (is_string($data['closeTime'])) {
                try {
                    $parsedTime = \DateTime::createFromFormat('H:i', $data['closeTime']);
                    if ($parsedTime === false) {
                        $parsedTime = \DateTime::createFromFormat('H:i:s', $data['closeTime']);
                    }
                    if ($parsedTime !== false) {
                        $data['closeTime'] = $parsedTime;
                    }
                } catch (\Exception $e) {
                    // Keep as string if parsing fails
                }
            }
        }

        // Ensure null values are explicitly set to null (not unset)
        // This prevents API Platform from trying to convert null to DateTime
        if (array_key_exists('openTime', $data) && $data['openTime'] === null) {
            $data['openTime'] = null;
        }
        if (array_key_exists('closeTime', $data) && $data['closeTime'] === null) {
            $data['closeTime'] = null;
        }

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        // Skip if already called to prevent infinite recursion
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        // Only handle BusinessHours entities
        if ($type !== BusinessHours::class) {
            return false;
        }

        // Only handle if data is an array (JSON deserialization)
        return is_array($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            BusinessHours::class => true,
        ];
    }
}

