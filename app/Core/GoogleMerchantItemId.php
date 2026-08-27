<?php

namespace App\Core;

/**
 * Stable product identifiers shared by Google Merchant feeds and cart links.
 */
final class GoogleMerchantItemId
{
    public static function encode(int $productId, ?int $variantId = null): string
    {
        if ($productId < 1 || ($variantId !== null && $variantId < 1)) {
            throw new \InvalidArgumentException('Google Merchant IDs must be positive integers.');
        }

        return $variantId === null ? (string)$productId : "{$productId}-{$variantId}";
    }

    /**
     * @return array{product_id: int, variant_id: int|null}|null
     */
    public static function parse(string $itemId): ?array
    {
        if (!preg_match('/^([1-9][0-9]*)(?:-([1-9][0-9]*))?$/D', $itemId, $matches)) {
            return null;
        }

        return [
            'product_id' => (int)$matches[1],
            'variant_id' => isset($matches[2]) ? (int)$matches[2] : null,
        ];
    }
}
