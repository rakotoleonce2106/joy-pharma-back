<?php

namespace App\Serializer;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;

class OrderNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    private const CONTEXT_KEY = 'order_normalizer_skip';
    
    private NormalizerInterface $normalizer;

    public function __construct(
        private readonly Security $security
    ) {
    }

    public function setNormalizer(NormalizerInterface $normalizer): void
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($object, ?string $format = null, array $context = []): array
    {
        if (!$object instanceof Order) {
            return [];
        }

        // CRITICAL: Check if we're already processing (prevent infinite recursion)
        if (isset($context[self::CONTEXT_KEY])) {
            // This should never happen if supportsNormalization works correctly
            // But if it does, we need to skip our processing and delegate
            throw new \RuntimeException('OrderNormalizer: Recursion detected. Context flag should prevent this.');
        }

        // Mark that we're processing this order to prevent recursion
        // IMPORTANT: Use array_merge to create a new array reference
        $newContext = array_merge($context, [self::CONTEXT_KEY => true]);
        
        // Get current user BEFORE normalizing
        $user = $this->security->getUser();
        
        // Normalize the order first using the normalizer chain
        // The context flag in supportsNormalization will prevent this normalizer from being called again
        try {
            $data = $this->normalizer->normalize($object, $format, $newContext);
        } catch (\Exception $e) {
            // If normalization fails, remove the context flag and rethrow
            unset($newContext[self::CONTEXT_KEY]);
            throw $e;
        }

        if (!$user instanceof User) {
            return $data;
        }

        $roles = $user->getRoles();

        // For ROLE_USER: exclude storePrice from order items and ensure QR code is included
        if (in_array('ROLE_USER', $roles, true) && !in_array('ROLE_STORE', $roles, true)) {
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as &$item) {
                    if (is_array($item) && isset($item['storePrice'])) {
                        unset($item['storePrice']);
                    }
                }
                unset($item); // Clear reference
            }
            
            // Ensure QR code is included for delivery verification
            // This QR code is used by delivery person to confirm successful delivery
            if ($object->getQrCode()) {
                $data['qrCode'] = $object->getQrCode();
            }
        }

        // For ROLE_STORE: filter items, replace totalAmount, exclude location
        if (in_array('ROLE_STORE', $roles, true)) {
            $store = $user->getStore();
            
            if ($store) {
                $storeId = $store->getId();
                
                // Filter items to only include items belonging to this store
                if (isset($data['items']) && is_array($data['items'])) {
                    $filteredItems = [];
                    
                    // Create a map of normalized items by ID for reliable matching
                    $normalizedItemsMap = [];
                    foreach ($data['items'] as $item) {
                        if (is_array($item) && isset($item['id'])) {
                            $normalizedItemsMap[$item['id']] = $item;
                        }
                    }
                    
                    // Check each item's store - use entity as source of truth
                    foreach ($object->getItems() as $orderItem) {
                        if ($orderItem instanceof OrderItem) {
                            $itemStore = $orderItem->getStore();
                            $itemId = $orderItem->getId();
                            
                            // Check if this item belongs to the user's store
                            if ($itemStore && $itemStore->getId() === $storeId && isset($normalizedItemsMap[$itemId])) {
                                // Include the normalized item data
                                $filteredItems[] = $normalizedItemsMap[$itemId];
                            }
                        }
                    }
                    
                    // Replace items in normalized data
                    $data['items'] = $filteredItems;
                }

                // Replace totalAmount with storeTotalAmount
                if (isset($data['storeTotalAmount'])) {
                    $data['totalAmount'] = $data['storeTotalAmount'];
                }

                // Ensure QR code is included for store users
                if ($object->getQrCode()) {
                    $data['qrCode'] = $object->getQrCode();
                }

                // Remove location
                if (isset($data['location'])) {
                    unset($data['location']);
                }
            }
        }

        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        // Skip if we're already processing this order (prevent recursion)
        if (isset($context[self::CONTEXT_KEY])) {
            return false;
        }
        
        return $data instanceof Order;
    }

    public function getSupportedTypes(?string $format): array
    {
        // Return false so supportsNormalization() is always called
        // This allows us to check the context flag to prevent recursion
        return [
            Order::class => false,
        ];
    }
}

