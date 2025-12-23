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

        // Convert string times to DateTime objects if needed
        if (is_array($data)) {
            if (isset($data['openTime']) && is_string($data['openTime'])) {
                try {
                    $parsedTime = \DateTime::createFromFormat('H:i', $data['openTime']);
                    if ($parsedTime === false) {
                        $parsedTime = \DateTime::createFromFormat('H:i:s', $data['openTime']);
                    }
                    if ($parsedTime !== false) {
                        $data['openTime'] = $parsedTime;
                    }
                } catch (\Exception $e) {
                    // Keep as string if parsing fails, let API Platform handle it
                }
            }

            if (isset($data['closeTime']) && is_string($data['closeTime'])) {
                try {
                    $parsedTime = \DateTime::createFromFormat('H:i', $data['closeTime']);
                    if ($parsedTime === false) {
                        $parsedTime = \DateTime::createFromFormat('H:i:s', $data['closeTime']);
                    }
                    if ($parsedTime !== false) {
                        $data['closeTime'] = $parsedTime;
                    }
                } catch (\Exception $e) {
                    // Keep as string if parsing fails, let API Platform handle it
                }
            }
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

