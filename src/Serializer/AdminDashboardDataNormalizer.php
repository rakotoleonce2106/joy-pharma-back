<?php

namespace App\Serializer;

use App\Dto\Admin\AdminDashboardData;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AdminDashboardDataNormalizer implements NormalizerInterface
{
    public function normalize($object, ?string $format = null, array $context = []): array
    {
        /** @var AdminDashboardData $object */
        return [
            'counters' => $object->counters,
            'financials' => $object->financials,
            'map' => $object->map,
            'lists' => $object->lists,
        ];
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof AdminDashboardData;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            AdminDashboardData::class => true,
        ];
    }
}

