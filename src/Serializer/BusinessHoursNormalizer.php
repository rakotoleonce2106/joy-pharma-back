<?php

namespace App\Serializer;

use App\Entity\BusinessHours;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

final class BusinessHoursNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'BUSINESS_HOURS_NORMALIZER_ALREADY_CALLED';

    public function normalize($object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context[self::ALREADY_CALLED] = true;

        $data = $this->normalizer->normalize($object, $format, $context);

        if (!is_array($data)) {
            return $data;
        }

        // Convert openTime to HH:mm format
        if (isset($data['openTime'])) {
            if ($data['openTime'] instanceof \DateTimeInterface) {
                $data['openTime'] = $data['openTime']->format('H:i');
            } elseif (is_string($data['openTime']) && preg_match('/^\d{4}-\d{2}-\d{2}T(\d{2}:\d{2})/', $data['openTime'], $matches)) {
                // Handle ISO datetime string format
                $data['openTime'] = $matches[1];
            } elseif (is_string($data['openTime']) && preg_match('/^(\d{2}:\d{2})/', $data['openTime'], $matches)) {
                // Already in HH:mm format
                $data['openTime'] = $matches[1];
            }
        }

        // Convert closeTime to HH:mm format
        if (isset($data['closeTime'])) {
            if ($data['closeTime'] instanceof \DateTimeInterface) {
                $data['closeTime'] = $data['closeTime']->format('H:i');
            } elseif (is_string($data['closeTime']) && preg_match('/^\d{4}-\d{2}-\d{2}T(\d{2}:\d{2})/', $data['closeTime'], $matches)) {
                // Handle ISO datetime string format
                $data['closeTime'] = $matches[1];
            } elseif (is_string($data['closeTime']) && preg_match('/^(\d{2}:\d{2})/', $data['closeTime'], $matches)) {
                // Already in HH:mm format
                $data['closeTime'] = $matches[1];
            }
        }

        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof BusinessHours;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            BusinessHours::class => true,
        ];
    }
}

