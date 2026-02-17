<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cart;
use App\Models\Product;

class CartController extends Controller
{
    protected $cartModel;
    protected $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->cartModel = new Cart();
        $this->productModel = new Product();
    }

    /**
     * Display cart page
     */
    public function index()
    {
        $sessionId = session_id();
        $userId = auth() ? auth()['id'] : null;

        $items = $this->cartModel->getItems($sessionId, $userId);
        $cartTotal = $this->cartModel->getTotal($sessionId, $userId);

        $this->render('cart/index', [
            'items' => $items,
            'cartTotal' => $cartTotal,
            'itemCount' => $this->cartModel->getCount($sessionId, $userId)
        ]);
    }

    /**
     * Add item to cart (POST)
     */
    public function add()
    {
        $this->requireValidCSRF();

        $productId = $this->post('product_id');
        $quantity = intval($this->post('quantity', 1));
        $variantId = $this->post('variant_id') ?: null;

        // Validate product exists
        $product = $this->productModel->find($productId);
        if (!$product) {
            http_response_code(404);
            return $this->json(['error' => 'Product not found']);
        }

        // Validate quantity
        if ($quantity < 1) {
            http_response_code(400);
            return $this->json(['error' => 'Invalid quantity']);
        }

        // Check inventory (use variant inventory if variant is specified)
        $inventoryCount = $product['inventory_count'];
        if ($variantId) {
            $variant = $this->productModel->queryOne(
                "SELECT inventory_count FROM product_variants WHERE id = ? AND product_id = ?",
                [$variantId, $productId]
            );
            if (!$variant) {
                http_response_code(400);
                return $this->json(['error' => 'Invalid product variant']);
            }
            $inventoryCount = $variant['inventory_count'];
        }

        if ($inventoryCount < $quantity) {
            http_response_code(400);
            return $this->json(['error' => 'Insufficient inventory']);
        }

        $sessionId = session_id();
        $userId = auth() ? auth()['id'] : null;

        try {
            $this->cartModel->addItem($productId, $quantity, $sessionId, $userId, $variantId);

            $cartCount = $this->cartModel->getCount($sessionId, $userId);
            $cartTotal = $this->cartModel->getTotal($sessionId, $userId);

            // Check if request is AJAX
            if ($this->isAjaxRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Item added to cart',
                    'cartCount' => $cartCount,
                    'cartTotal' => $cartTotal
                ]);
            } else {
                setFlash('success', 'Item added to cart!');
                return $this->redirect('/cart');
            }
        } catch (\Exception $e) {
            http_response_code(500);
            return $this->json(['error' => 'Failed to add item to cart']);
        }
    }

    /**
     * Update item quantity (POST)
     */
    public function update()
    {
        $this->requireValidCSRF();

        $cartItemId = $this->post('cart_item_id');
        $quantity = intval($this->post('quantity', 1));

        if (!$cartItemId || $quantity < 1) {
            http_response_code(400);
            return $this->json(['error' => 'Invalid request']);
        }

        $sessionId = session_id();
        $userId = auth() ? auth()['id'] : null;

        // Validate inventory before updating quantity
        $cartItem = $this->cartModel->getItemById($cartItemId, $sessionId, $userId);
        if (!$cartItem) {
            http_response_code(404);
            return $this->json(['error' => 'Cart item not found']);
        }

        // Check available inventory
        $availableStock = $cartItem['variant_id']
            ? ($cartItem['variant_inventory'] ?? 0)
            : ($cartItem['product_inventory'] ?? 0);

        if ($quantity > $availableStock) {
            http_response_code(400);
            return $this->json([
                'error' => "Only {$availableStock} items available in stock",
                'available' => $availableStock
            ]);
        }

        try {
            $this->cartModel->updateQuantity($cartItemId, $quantity, $sessionId, $userId);

            if ($this->isAjaxRequest()) {
                $cartTotal = $this->cartModel->getTotal($sessionId, $userId);
                $cartCount = $this->cartModel->getCount($sessionId, $userId);

                return $this->json([
                    'success' => true,
                    'cartTotal' => $cartTotal,
                    'cartCount' => $cartCount
                ]);
            } else {
                setFlash('success', 'Cart updated!');
                return $this->redirect('/cart');
            }
        } catch (\Exception $e) {
            http_response_code(500);
            return $this->json(['error' => 'Failed to update cart']);
        }
    }

    /**
     * Remove item from cart (POST)
     */
    public function remove()
    {
        $this->requireValidCSRF();

        $cartItemId = $this->post('cart_item_id');

        if (!$cartItemId) {
            http_response_code(400);
            return $this->json(['error' => 'Invalid request']);
        }

        $sessionId = session_id();
        $userId = auth() ? auth()['id'] : null;

        try {
            $this->cartModel->removeItem($cartItemId, $sessionId, $userId);

            if ($this->isAjaxRequest()) {
                $cartTotal = $this->cartModel->getTotal($sessionId, $userId);
                $cartCount = $this->cartModel->getCount($sessionId, $userId);

                return $this->json([
                    'success' => true,
                    'cartTotal' => $cartTotal,
                    'cartCount' => $cartCount
                ]);
            } else {
                setFlash('success', 'Item removed from cart');
                return $this->redirect('/cart');
            }
        } catch (\Exception $e) {
            http_response_code(500);
            return $this->json(['error' => 'Failed to remove item']);
        }
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
