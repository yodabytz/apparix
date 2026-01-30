<?php

namespace App\Core\Shipping;

use App\Models\ShippingZone;
use App\Models\ShippingMethod;
use App\Models\ShippingOrigin;

class ShippingCalculator
{
    private ShippingZone $zoneModel;
    private ShippingMethod $methodModel;
    private ShippingOrigin $originModel;

    public function __construct()
    {
        $this->zoneModel = new ShippingZone();
        $this->methodModel = new ShippingMethod();
        $this->originModel = new ShippingOrigin();
    }

    /**
     * Calculate available shipping options for a destination
     */
    public function getShippingOptions(
        string $countryCode,
        ?string $stateCode,
        float $subtotal,
        array $items = []
    ): array {
        // Find the shipping zone for this destination
        $zone = $this->zoneModel->findByLocation($countryCode, $stateCode);

        if (!$zone) {
            return [
                'success' => false,
                'error' => 'No shipping available to this location',
                'options' => []
            ];
        }

        // Get all methods for this zone
        $methods = $this->methodModel->getByZone($zone['id']);

        if (empty($methods)) {
            return [
                'success' => false,
                'error' => 'No shipping methods available for this zone',
                'options' => []
            ];
        }

        // Calculate totals from items
        $totalWeight = 0;
        $totalQuantity = 0;
        $allShipFree = true;
        $fixedShippingTotal = 0;
        $hasFixedShipping = false;
        $originShippingTotal = 0;
        $hasOriginShipping = false;
        $handlingFees = 0;
        $isUS = ($countryCode === 'US');

        foreach ($items as $item) {
            $weight = $item['weight_oz'] ?? 0;
            $quantity = $item['quantity'] ?? 1;
            $totalWeight += $weight * $quantity;
            $totalQuantity += $quantity;

            // Check if item ships free (globally or US-specific)
            $itemShipsFree = !empty($item['ships_free']) || ($isUS && !empty($item['ships_free_us']));

            if (!$itemShipsFree) {
                $allShipFree = false;

                // Check for product fixed shipping price (highest priority override)
                if (!empty($item['shipping_price'])) {
                    $fixedShippingTotal += (float)$item['shipping_price'] * $quantity;
                    $hasFixedShipping = true;
                }
                // Check for warehouse/origin-based shipping cost (fallback before zone rates)
                elseif (($originShippingCost = $this->getOriginShippingCost($item, $countryCode)) !== null) {
                    $originShippingTotal += $originShippingCost * $quantity;
                    $hasOriginShipping = true;
                }
            }

            // Add handling fees from shipping class
            if (!empty($item['handling_fee'])) {
                $handlingFees += (float)$item['handling_fee'] * $quantity;
            }
        }

        // Calculate rates for each method
        $options = [];
        foreach ($methods as $method) {
            $rateInfo = $this->methodModel->calculateRate(
                $method['id'],
                $subtotal,
                $totalWeight,
                $totalQuantity
            );

            if ($rateInfo) {
                // Check if this is express shipping (never free)
                $isExpress = stripos($method['name'], 'express') !== false ||
                             stripos($method['method_code'] ?? '', 'express') !== false;

                // Priority order: 1. Free shipping, 2. Product fixed price, 3. Origin price, 4. Zone rate
                if ($allShipFree && !empty($items) && !$isExpress) {
                    $rateInfo['rate'] = 0;
                    $rateInfo['is_free'] = true;
                } elseif ($hasFixedShipping) {
                    // Use product fixed shipping prices (highest priority override)
                    $rateInfo['rate'] = round($fixedShippingTotal + $handlingFees, 2);
                    $rateInfo['is_free'] = false;
                } elseif ($hasOriginShipping) {
                    // Use warehouse/origin-based shipping costs (fallback before zone rates)
                    $rateInfo['rate'] = round($originShippingTotal + $handlingFees, 2);
                    $rateInfo['is_free'] = false;
                } else {
                    // Add handling fees to standard zone rate
                    $rateInfo['rate'] = round($rateInfo['rate'] + $handlingFees, 2);
                }

                $options[] = $rateInfo;
            }
        }

        // Sort by rate (cheapest first, free first)
        usort($options, function ($a, $b) {
            if ($a['is_free'] && !$b['is_free']) return -1;
            if (!$a['is_free'] && $b['is_free']) return 1;
            return $a['rate'] <=> $b['rate'];
        });

        return [
            'success' => true,
            'zone' => [
                'id' => $zone['id'],
                'name' => $zone['name']
            ],
            'options' => $options,
            'free_shipping_threshold' => $this->getFreeShippingThreshold($zone['id']),
            'amount_until_free' => $this->getAmountUntilFree($zone['id'], $subtotal)
        ];
    }

    /**
     * Get the free shipping threshold for a zone
     */
    public function getFreeShippingThreshold(int $zoneId): ?float
    {
        $methods = $this->methodModel->getByZone($zoneId);

        foreach ($methods as $method) {
            if ($method['min_order_free']) {
                return (float) $method['min_order_free'];
            }
        }

        return null;
    }

    /**
     * Get amount needed for free shipping
     */
    public function getAmountUntilFree(int $zoneId, float $currentSubtotal): ?float
    {
        $threshold = $this->getFreeShippingThreshold($zoneId);

        if ($threshold === null) {
            return null;
        }

        $remaining = $threshold - $currentSubtotal;

        return $remaining > 0 ? round($remaining, 2) : 0;
    }

    /**
     * Get specific rate by method ID
     */
    public function getRate(int $methodId, float $subtotal, array $items = [], string $countryCode = 'US'): ?array
    {
        $totalWeight = 0;
        $totalQuantity = 0;
        $allShipFree = true;
        $fixedShippingTotal = 0;
        $hasFixedShipping = false;
        $originShippingTotal = 0;
        $hasOriginShipping = false;
        $handlingFees = 0;
        $isUS = ($countryCode === 'US');

        foreach ($items as $item) {
            $weight = $item['weight_oz'] ?? 0;
            $quantity = $item['quantity'] ?? 1;
            $totalWeight += $weight * $quantity;
            $totalQuantity += $quantity;

            // Check if item ships free (globally or US-specific)
            $itemShipsFree = !empty($item['ships_free']) || ($isUS && !empty($item['ships_free_us']));

            if (!$itemShipsFree) {
                $allShipFree = false;

                // Check for product fixed shipping price (highest priority override)
                if (!empty($item['shipping_price'])) {
                    $fixedShippingTotal += (float)$item['shipping_price'] * $quantity;
                    $hasFixedShipping = true;
                }
                // Check for warehouse/origin-based shipping cost (fallback before zone rates)
                elseif (($originShippingCost = $this->getOriginShippingCost($item, $countryCode)) !== null) {
                    $originShippingTotal += $originShippingCost * $quantity;
                    $hasOriginShipping = true;
                }
            }

            // Add handling fees from shipping class
            if (!empty($item['handling_fee'])) {
                $handlingFees += (float)$item['handling_fee'] * $quantity;
            }
        }

        $rateInfo = $this->methodModel->calculateRate(
            $methodId,
            $subtotal,
            $totalWeight,
            $totalQuantity
        );

        if ($rateInfo) {
            // Check if this is express shipping (never free)
            $isExpress = stripos($rateInfo['name'] ?? '', 'express') !== false;

            // Priority order: 1. Free shipping, 2. Product fixed price, 3. Origin price, 4. Zone rate
            if ($allShipFree && !empty($items) && !$isExpress) {
                $rateInfo['rate'] = 0;
                $rateInfo['is_free'] = true;
            } elseif ($hasFixedShipping) {
                // Use product fixed shipping prices (highest priority override)
                $rateInfo['rate'] = round($fixedShippingTotal + $handlingFees, 2);
                $rateInfo['is_free'] = false;
            } elseif ($hasOriginShipping) {
                // Use warehouse/origin-based shipping costs (fallback before zone rates)
                $rateInfo['rate'] = round($originShippingTotal + $handlingFees, 2);
                $rateInfo['is_free'] = false;
            } else {
                // Add handling fees to standard zone rate
                $rateInfo['rate'] = round($rateInfo['rate'] + $handlingFees, 2);
            }
        }

        return $rateInfo;
    }

    /**
     * Validate shipping method for destination
     */
    public function validateMethod(int $methodId, string $countryCode, ?string $stateCode): bool
    {
        $method = $this->methodModel->findById($methodId);

        if (!$method) {
            return false;
        }

        $zone = $this->zoneModel->findByLocation($countryCode, $stateCode);

        if (!$zone) {
            return false;
        }

        return $method['zone_id'] == $zone['id'];
    }

    /**
     * Get all zones for admin
     */
    public function getAllZones(): array
    {
        return $this->zoneModel->getAll();
    }

    /**
     * Get all methods for admin
     */
    public function getAllMethods(): array
    {
        return $this->methodModel->getAll();
    }

    /**
     * Get all origins for admin
     */
    public function getAllOrigins(): array
    {
        return $this->originModel->getAllAdmin();
    }

    /**
     * Get default origin
     */
    public function getDefaultOrigin(): ?array
    {
        return $this->originModel->getDefault();
    }

    /**
     * Get origin-based shipping cost for an item based on destination country
     */
    private function getOriginShippingCost(array $item, string $countryCode): ?float
    {
        if ($countryCode === 'US' && isset($item['origin_shipping_usa']) && $item['origin_shipping_usa'] !== null) {
            return (float)$item['origin_shipping_usa'];
        }

        if ($countryCode === 'CA' && isset($item['origin_shipping_canada']) && $item['origin_shipping_canada'] !== null) {
            return (float)$item['origin_shipping_canada'];
        }

        // All other countries use overseas rate
        if ($countryCode !== 'US' && $countryCode !== 'CA' && isset($item['origin_shipping_overseas']) && $item['origin_shipping_overseas'] !== null) {
            return (float)$item['origin_shipping_overseas'];
        }

        return null;
    }
}
