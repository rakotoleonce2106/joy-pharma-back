<?php

namespace App\Serializer;

use App\Entity\StoreProduct;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;

final class StoreProductDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED = 'STORE_PRODUCT_DENORMALIZER_ALREADY_CALLED';

    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        $context[self::ALREADY_CALLED] = true;

        // Transform productId to product IRI
        if (isset($data['productId']) && !isset($data['product'])) {
            // Handle both integer and string productId
            $productId = $data['productId'];
            if (is_numeric($productId)) {
                $data['product'] = '/api/products/' . (int) $productId;
            }
            unset($data['productId']);
        }

        // Transform totalPrice to price
        if (isset($data['totalPrice']) && !isset($data['price'])) {
            $data['price'] = $data['totalPrice'];
            unset($data['totalPrice']);
        }

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        // Skip if already called to prevent infinite recursion
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        // Only handle StoreProduct entities
        if ($type !== StoreProduct::class) {
            return false;
        }

        // Only handle if productId or totalPrice is present
        return is_array($data) && (isset($data['productId']) || isset($data['totalPrice']));
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            StoreProduct::class => true,
        ];
    }
}

