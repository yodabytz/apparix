<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Shipping\ShippingCalculator;
use App\Models\Cart;

class ShippingController extends Controller
{
    private ShippingCalculator $calculator;
    private Cart $cartModel;

    public function __construct()
    {
        parent::__construct();
        $this->calculator = new ShippingCalculator();
        $this->cartModel = new Cart();
    }

    /**
     * Get shipping rates (AJAX endpoint)
     */
    public function getRates(): void
    {
        // Clear any buffered output to ensure clean JSON
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');

        try {
            $countryCode = $this->post('country', '') ?: $this->get('country', '');
            $stateCode = $this->post('state', '') ?: $this->get('state', '');

            if (empty($countryCode)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Country is required'
                ]);
                return;
            }

            // Get cart items
            $sessionId = session_id();
            $userId = auth() ? auth()['id'] : null;
            $cartItems = $this->cartModel->getItems($sessionId, $userId);
            $items = [];
            $subtotal = 0;

            if (!empty($cartItems)) {
                foreach ($cartItems as $item) {
                    $itemPrice = $item['sale_price'] ?? $item['price'];
                    if (!empty($item['price_adjustment'])) {
                        $itemPrice += $item['price_adjustment'];
                    }
                    $lineTotal = $itemPrice * $item['quantity'];

                    $items[] = [
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'weight_oz' => $item['weight_oz'] ?? 0,
                        'ships_free' => $item['ships_free'] ?? 0,
                        'ships_free_us' => $item['ships_free_us'] ?? 0,
                        'shipping_price' => $item['shipping_price'] ?? null,
                        'handling_fee' => $item['handling_fee'] ?? 0
                    ];
                    $subtotal += $lineTotal;
                }
            }

            $result = $this->calculator->getShippingOptions(
                $countryCode,
                $stateCode ?: null,
                $subtotal,
                $items
            );

            echo json_encode($result);
        } catch (\Throwable $e) {
            error_log('Shipping getRates error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            echo json_encode([
                'success' => false,
                'error' => 'Error calculating shipping rates'
            ]);
        }
    }

    /**
     * Get rate for specific method (AJAX endpoint)
     */
    public function getMethodRate(): void
    {
        header('Content-Type: application/json');

        $methodId = (int) ($this->post('method_id', 0) ?: $this->get('method_id', 0));

        if (!$methodId) {
            echo json_encode([
                'success' => false,
                'error' => 'Method ID is required'
            ]);
            return;
        }

        // Get cart items
        $sessionId = session_id();
        $userId = auth() ? auth()['id'] : null;
        $cartItems = $this->cartModel->getItems($sessionId, $userId);
        $items = [];
        $subtotal = 0;

        if (!empty($cartItems)) {
            foreach ($cartItems as $item) {
                $itemPrice = $item['sale_price'] ?? $item['price'];
                if (!empty($item['price_adjustment'])) {
                    $itemPrice += $item['price_adjustment'];
                }
                $lineTotal = $itemPrice * $item['quantity'];

                $items[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'weight_oz' => $item['weight_oz'] ?? 0,
                    'ships_free' => $item['ships_free'] ?? 0,
                    'ships_free_us' => $item['ships_free_us'] ?? 0,
                    'shipping_price' => $item['shipping_price'] ?? null,
                    'handling_fee' => $item['handling_fee'] ?? 0
                ];
                $subtotal += $lineTotal;
            }
        }

        $rate = $this->calculator->getRate($methodId, $subtotal, $items);

        if ($rate) {
            echo json_encode([
                'success' => true,
                'rate' => $rate
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Method not found'
            ]);
        }
    }

    /**
     * Validate shipping method for destination (AJAX endpoint)
     */
    public function validateMethod(): void
    {
        header('Content-Type: application/json');

        $methodId = (int) ($this->post('method_id', 0) ?: $this->get('method_id', 0));
        $countryCode = $this->post('country', '') ?: $this->get('country', '');
        $stateCode = $this->post('state', '') ?: $this->get('state', '');

        if (!$methodId || !$countryCode) {
            echo json_encode([
                'success' => false,
                'valid' => false,
                'error' => 'Method ID and country are required'
            ]);
            return;
        }

        $valid = $this->calculator->validateMethod($methodId, $countryCode, $stateCode ?: null);

        echo json_encode([
            'success' => true,
            'valid' => $valid
        ]);
    }
}
