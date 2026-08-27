<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\GoogleMerchantItemId;
use App\Models\AbandonedCart;
use App\Models\Bundle;
use App\Models\Cart;
use App\Models\Newsletter;
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
     * Display cart page (handles recovery via ?recover=session_id)
     */
    public function index()
    {
        $merchantItemId = $this->get('productId');
        if ($merchantItemId !== null) {
            $this->addGoogleMerchantItem((string)$merchantItemId);
            return;
        }

        $sessionId = session_id();
        $userId = auth() ? auth()['id'] : null;

        // Handle cart recovery from abandoned cart email (HMAC-signed URL)
        $recoverSession = $this->get('recover');
        $sig = $this->get('sig');
        if ($recoverSession && $recoverSession !== $sessionId) {
            $expectedSig = hash_hmac('sha256', $recoverSession, $_ENV['APP_SECRET'] ?? '');
            if (!$sig || !hash_equals($expectedSig, $sig)) {
                setFlash('error', 'Invalid recovery link.');
                $this->redirect('/cart');
                return;
            }
            $abandonedCart = new AbandonedCart();
            $oldItems = $abandonedCart->getCartItems($recoverSession);

            if (!empty($oldItems)) {
                foreach ($oldItems as $item) {
                    try {
                        $this->cartModel->addItem(
                            $item['product_id'],
                            $item['quantity'],
                            $sessionId,
                            $userId,
                            $item['variant_id']
                        );
                    } catch (\Exception $e) {
                        // Skip items that can't be added (out of stock, etc.)
                    }
                }
                $abandonedCart->markRecovered($recoverSession);
                setFlash('success', 'Welcome back! Your cart items have been restored.');
            }

            $this->redirect('/cart');
            return;
        }

        $items = $this->cartModel->getItems($sessionId, $userId);
        $cartTotal = $this->cartModel->getTotal($sessionId, $userId);

        // Calculate auto-discounts (quantity tiers + bundles)
        $bundleModel = new Bundle();
        $autoDiscounts = $bundleModel->calculateCartDiscounts($items);
        $autoDiscountTotal = array_sum(array_column($autoDiscounts, 'amount'));

        $this->render('cart/index', [
            'items' => $items,
            'cartTotal' => $cartTotal,
            'itemCount' => $this->cartModel->getCount($sessionId, $userId),
            'autoDiscounts' => $autoDiscounts,
            'autoDiscountTotal' => $autoDiscountTotal
        ]);
    }

    /**
     * Add one exact catalog item from Google's account-level cart URL template.
     */
    private function addGoogleMerchantItem(string $itemId): void
    {
        $parsed = GoogleMerchantItemId::parse($itemId);
        if ($parsed === null) {
            setFlash('error', 'The requested product link is invalid.');
            $this->redirect('/cart');
            return;
        }

        $product = $this->productModel->find($parsed['product_id']);
        if (!$product || empty($product['is_active']) || !empty($product['disabled'])) {
            setFlash('error', 'That product is no longer available.');
            $this->redirect('/cart');
            return;
        }

        $productUrl = '/products/' . rawurlencode((string)$product['slug']);
        if (!empty($this->productModel->getCustomizationFields($parsed['product_id']))) {
            setFlash('info', 'Choose your personalization before adding this product.');
            $this->redirect($productUrl);
            return;
        }

        $variantId = $parsed['variant_id'];
        if ($variantId !== null) {
            $variant = $this->productModel->queryOne(
                'SELECT id, inventory_count FROM product_variants WHERE id = ? AND product_id = ? AND is_active = 1',
                [$variantId, $parsed['product_id']]
            );
            if (!$variant || (int)$variant['inventory_count'] < 1) {
                setFlash('error', 'That product option is currently unavailable.');
                $this->redirect($productUrl);
                return;
            }
        } else {
            $variant = $this->productModel->queryOne(
                'SELECT id FROM product_variants WHERE product_id = ? AND is_active = 1 LIMIT 1',
                [$parsed['product_id']]
            );
            if ($variant) {
                setFlash('info', 'Choose your product options before adding this item.');
                $this->redirect($productUrl);
                return;
            }
            if ((int)($product['inventory_count'] ?? 0) < 1) {
                setFlash('error', 'That product is currently out of stock.');
                $this->redirect($productUrl);
                return;
            }
        }

        try {
            $sessionId = session_id();
            $userId = auth() ? auth()['id'] : null;
            $this->cartModel->addItem($parsed['product_id'], 1, $sessionId, $userId, $variantId);
            setFlash('success', 'Item added to cart!');
        } catch (\Throwable $e) {
            error_log('Google Merchant cart add failed: ' . $e->getMessage());
            setFlash('error', 'That item could not be added to your cart.');
        }

        $this->redirect('/cart');
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
        $isBackorder = $this->post('is_backorder') ? 1 : 0;

        // Validate product exists
        $product = $this->productModel->find($productId);
        if (!$product) {
            http_response_code(404);
            return $this->json(['error' => 'Product not found']);
        }

        // Validate quantity
        if ($quantity < 1 || $quantity > 9999) {
            http_response_code(400);
            return $this->json(['error' => 'Invalid quantity']);
        }

        $customizationsResult = $this->collectCustomizations((int)$productId);
        if (!$customizationsResult['success']) {
            http_response_code(400);
            return $this->json(['error' => $customizationsResult['error']]);
        }
        $customizations = $customizationsResult['customizations'];

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

        // Server-side backorder validation: only allow if product has allow_backorder enabled AND item is actually out of stock
        if ($isBackorder) {
            if (empty($product['allow_backorder'])) {
                http_response_code(400);
                return $this->json(['error' => 'This product does not allow backorders']);
            }
            // Don't allow backorder on in-stock items — treat as normal order
            if ($inventoryCount >= $quantity) {
                $isBackorder = 0;
            } else {
                // Cap backorder qty at 10
                if ($quantity > 10) {
                    http_response_code(400);
                    return $this->json(['error' => 'Maximum backorder quantity is 10']);
                }
            }
        } elseif ($inventoryCount < $quantity) {
            http_response_code(400);
            return $this->json(['error' => 'Insufficient inventory']);
        }

        $sessionId = session_id();
        $userId = auth() ? auth()['id'] : null;

        try {
            $this->cartModel->addItem($productId, $quantity, $sessionId, $userId, $variantId, $isBackorder, $customizations);

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

        if (!$cartItemId || $quantity < 1 || $quantity > 9999) {
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

        // Check available inventory (skip for backorder items, cap at 10)
        if (!empty($cartItem['is_backorder'])) {
            if ($quantity > 10) {
                http_response_code(400);
                return $this->json([
                    'error' => 'Maximum backorder quantity is 10',
                    'available' => 10
                ]);
            }
        } else {
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
     * Capture email from checkout for abandoned cart tracking
     */
    public function captureEmail()
    {
        $email = trim($this->post('email', ''));

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email'], 400);
        }

        // Layer 1: Block disposable/bot email domains
        $emailDomain = strtolower(substr($email, strpos($email, '@') + 1));
        $blockedDomains = [
            'storebotmail.joonix.net', 'joonix.net',
            'mailinator.com', 'mailnator.com', 'mailnesia.com', 'mailsac.com',
            'guerrillamail.com', 'guerrillamail.info', 'guerrillamail.net', 'guerrillamail.org', 'guerrillamailblock.com', 'grr.la',
            'tempmail.com', 'temp-mail.org', 'temp-mail.com', 'temp-mail.de', 'temp-mail.lol', 'tempmail.us', 'tempmail.ninja', 'tempmail.plus', 'tempmail.io.vn', 'tempmail.world', 'tempmail.eu',
            'yopmail.com', 'yopmail.net',
            'throwaway.email', 'throwawaymail.com',
            'sharklasers.com', 'dispostable.com', 'discard.email',
            '10minutemail.com', '20minutemail.com', '30minutemail.com',
            'maildrop.cc', 'trashmail.com', 'trashmail.de', 'trash-mail.com', 'mytrashmail.com',
            'fakeinbox.com', 'spam4.me', 'spam.la', 'spam.ceo',
            'mail.tm', 'mailbox.zip', 'emailnator.com', 'tempomail.org',
            'temporary-mail.net', 'disposable.cf',
        ];
        if (in_array($emailDomain, $blockedDomains)) {
            return $this->json(['success' => true]);
        }

        // Layer 2: reCAPTCHA v3 verification
        $recaptchaToken = trim($this->post('recaptcha_token', ''));
        if ($recaptchaToken) {
            $recaptchaResult = \App\Core\ReCaptcha::verify($recaptchaToken, 'cart_email_capture');
            if (!$recaptchaResult['success'] || ($recaptchaResult['score'] ?? 1.0) < 0.3) {
                return $this->json(['success' => true]);
            }
        }

        $sessionId = session_id();
        $db = \App\Core\Database::getInstance();

        // Layer 3: One email per session — block bots cycling through emails
        $existingEmail = $db->selectOne(
            "SELECT email FROM cart WHERE session_id = ? AND email IS NOT NULL LIMIT 1",
            [$sessionId]
        );
        if ($existingEmail && $existingEmail['email'] !== $email) {
            return $this->json(['success' => true]);
        }

        // Stamp email on all cart rows for this session
        $abandonedCart = new AbandonedCart();
        $abandonedCart->captureEmail($sessionId, $email);

        // Auto-subscribe to newsletter (no welcome email)
        try {
            $newsletter = new Newsletter();
            $newsletter->subscribe($email, null, auth() ? auth()['id'] : null, 'abandoned_cart');
        } catch (\Exception $e) {
            // Don't fail if newsletter subscription fails
        }

        return $this->json(['success' => true]);
    }

    private function collectCustomizations(int $productId): array
    {
        $fields = $this->productModel->getCustomizationFields($productId);
        $posted = $this->post('customizations', []);
        $posted = is_array($posted) ? $posted : [];
        $customizations = [];

        foreach ($fields as $field) {
            $key = (string)$field['field_key'];
            $label = (string)$field['label'];
            $maxLength = max(1, min(500, (int)($field['max_length'] ?? 100)));
            $value = trim((string)($posted[$key] ?? ''));
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
            $value = trim($value);

            if ($value === '') {
                if (!empty($field['is_required'])) {
                    return ['success' => false, 'error' => $label . ' is required'];
                }
                continue;
            }
            if (mb_strlen($value) > $maxLength) {
                return ['success' => false, 'error' => $label . ' must be ' . $maxLength . ' characters or fewer'];
            }

            $customizations[] = [
                'key' => $key,
                'label' => $label,
                'value' => $value,
                'printify_position' => (string)($field['printify_position'] ?? ''),
            ];
        }

        return ['success' => true, 'customizations' => $customizations];
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
