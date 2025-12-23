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

        // Create BusinessHours object directly to avoid DateTimeNormalizer issues with null values
        $businessHours = new BusinessHours();
        
        // Handle isClosed
        $isClosed = $data['isClosed'] ?? false;
        if (isset($data['isClosed'])) {
            $businessHours->setIsClosed((bool) $data['isClosed']);
        } else {
            $businessHours->setIsClosed(false);
        }

        // Convert string times to DateTime objects ONLY if they are non-empty strings
        // Leave null values as null - they should not be converted
        $openTime = null;
        if (isset($data['openTime']) && $data['openTime'] !== null && $data['openTime'] !== '') {
            if (is_string($data['openTime'])) {
                try {
                    $parsedTime = \DateTime::createFromFormat('H:i', $data['openTime']);
                    if ($parsedTime === false) {
                        $parsedTime = \DateTime::createFromFormat('H:i:s', $data['openTime']);
                    }
                    if ($parsedTime !== false) {
                        $openTime = $parsedTime;
                    }
                } catch (\Exception $e) {
                    // Keep as null if parsing fails
                }
            } elseif ($data['openTime'] instanceof \DateTimeInterface) {
                $openTime = $data['openTime'];
            }
        }
        $businessHours->setOpenTime($openTime);

        $closeTime = null;
        if (isset($data['closeTime']) && $data['closeTime'] !== null && $data['closeTime'] !== '') {
            if (is_string($data['closeTime'])) {
                try {
                    $parsedTime = \DateTime::createFromFormat('H:i', $data['closeTime']);
                    if ($parsedTime === false) {
                        $parsedTime = \DateTime::createFromFormat('H:i:s', $data['closeTime']);
                    }
                    if ($parsedTime !== false) {
                        $closeTime = $parsedTime;
                    }
                } catch (\Exception $e) {
                    // Keep as null if parsing fails
                }
            } elseif ($data['closeTime'] instanceof \DateTimeInterface) {
                $closeTime = $data['closeTime'];
            }
        }
        $businessHours->setCloseTime($closeTime);

        return $businessHours;
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


