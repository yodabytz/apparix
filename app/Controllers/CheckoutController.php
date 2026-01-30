<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Shipping\ShippingCalculator;
use App\Core\OrderNotificationService;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Coupon;
use App\Models\ShippingMethod;
use App\Models\PopupCoupon;
use App\Models\OrderLicense;
use App\Models\OrderDownload;

class CheckoutController extends Controller
{
    protected Cart $cartModel;
    protected Product $productModel;
    protected Coupon $couponModel;
    protected ShippingCalculator $shippingCalculator;
    protected ShippingMethod $shippingMethodModel;

    public function __construct()
    {
        parent::__construct();
        $this->cartModel = new Cart();
        $this->productModel = new Product();
        $this->couponModel = new Coupon();
        $this->shippingCalculator = new ShippingCalculator();
        $this->shippingMethodModel = new ShippingMethod();
    }

    /**
     * Display checkout page
     */
    public function index(): void
    {
        $sessionId = session_id();
        $userId = auth() ? auth()['id'] : null;

        // Get cart items
        $items = $this->cartModel->getItems($sessionId, $userId);

        if (empty($items)) {
            setFlash('error', 'Your cart is empty');
            $this->redirect('/cart');
            return;
        }

        $cartTotal = $this->cartModel->getTotal($sessionId, $userId);

        // Get user's saved addresses if logged in
        $savedAddresses = [];
        if ($userId) {
            $db = Database::getInstance();
            $savedAddresses = $db->select(
                "SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, type, created_at DESC",
                [$userId]
            );
        }

        // Get Stripe public key
        $stripePublicKey = $_ENV['STRIPE_PUBLIC_KEY'] ?? '';

        $this->render('checkout/index', [
            'title' => 'Checkout',
            'items' => $items,
            'cartTotal' => $cartTotal,
            'savedAddresses' => $savedAddresses,
            'stripePublicKey' => $stripePublicKey
        ]);
    }

    /**
     * Create Stripe Payment Intent (AJAX)
     */
    public function createPaymentIntent(): void
    {
        $this->requireValidCSRF();

        $sessionId = session_id();
        $userId = auth() ? auth()['id'] : null;

        $subtotal = $this->cartModel->getTotal($sessionId, $userId);

        if ($subtotal <= 0) {
            $this->json(['error' => 'Invalid cart total'], 400);
            return;
        }

        // Get cart items to check if digital-only
        $items = $this->cartModel->getItems($sessionId, $userId);

        // Check if cart is digital-only (no physical items)
        $isDigitalOnly = true;
        foreach ($items as $item) {
            if (empty($item['is_digital'])) {
                $isDigitalOnly = false;
                break;
            }
        }

        // Get shipping info from POST (sent when Continue to Payment is clicked)
        $shippingMethodId = $this->post('shipping_method_id', '');
        $shippingCountry = $this->post('shipping_country', 'US');

        // Calculate shipping cost using getRate() - skip for digital-only orders
        $shippingCost = 0;
        if (!$isDigitalOnly && $shippingMethodId && $shippingMethodId !== 'digital') {
            $rateInfo = $this->shippingCalculator->getRate((int)$shippingMethodId, $subtotal, $items, $shippingCountry);
            if ($rateInfo) {
                $shippingCost = $rateInfo['rate'] ?? 0;
            }
        }

        // Apply coupon discount if present
        $discountAmount = 0;
        $appliedCoupon = $_SESSION['applied_coupon'] ?? null;
        if ($appliedCoupon) {
            $discountAmount = $appliedCoupon['discount'] ?? 0;
        }

        // Calculate total (same formula as process())
        $total = $subtotal + $shippingCost - $discountAmount;

        // Handle free orders - no payment needed
        if ($total <= 0) {
            $this->json([
                'freeOrder' => true,
                'total' => 0
            ]);
            return;
        }

        // Convert to cents for Stripe
        $amountCents = (int) round($total * 100);

        try {
            \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $amountCents,
                'currency' => 'usd',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'session_id' => $sessionId,
                    'user_id' => $userId ?? 'guest'
                ]
            ]);

            // Store payment breakdown in session to ensure consistency at checkout
            $_SESSION['payment_intent_data'] = [
                'payment_intent_id' => $paymentIntent->id,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'shipping_method_id' => $shippingMethodId ? (int)$shippingMethodId : null,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'coupon_code' => $appliedCoupon['code'] ?? null,
                'coupon_id' => $appliedCoupon['id'] ?? null,
                'is_popup_coupon' => !empty($appliedCoupon['is_popup']),
                'created_at' => time()
            ];

            $this->json([
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id
            ]);

        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Process checkout (place order)
     */
    public function process(): void
    {
        $this->requireValidCSRF();

        $sessionId = session_id();
        $userId = auth() ? auth()['id'] : null;
        $db = Database::getInstance();

        // Get cart items
        $items = $this->cartModel->getItems($sessionId, $userId);

        if (empty($items)) {
            if ($this->isAjaxRequest()) {
                $this->json(['error' => 'Cart is empty'], 400);
            } else {
                setFlash('error', 'Your cart is empty');
                $this->redirect('/cart');
            }
            return;
        }

        // Check if cart is digital-only (no physical items)
        $isDigitalOnly = true;
        foreach ($items as $item) {
            if (empty($item['is_digital'])) {
                $isDigitalOnly = false;
                break;
            }
        }

        // Check license monthly order limit
        $monthlyOrders = $db->selectOne(
            "SELECT COUNT(*) as cnt FROM orders WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        )['cnt'] ?? 0;
        $orderLimit = \App\Core\License::getLimit('max_orders_month');
        if ($orderLimit !== -1 && $monthlyOrders >= $orderLimit) {
            $error = "Monthly order limit reached ({$orderLimit} orders). The store owner needs to upgrade their license.";
            if ($this->isAjaxRequest()) {
                $this->json(['error' => $error], 400);
            } else {
                setFlash('error', $error);
                $this->redirect('/cart');
            }
            return;
        }

        // Get stored payment intent data (prevents race conditions)
        $paymentIntentData = $_SESSION['payment_intent_data'] ?? null;
        $paymentIntentId = $this->post('payment_intent_id');

        // Validate payment intent data exists and matches (skip for free orders)
        if ($paymentIntentId && (!$paymentIntentData || $paymentIntentData['payment_intent_id'] !== $paymentIntentId)) {
            $errorMsg = 'Payment session expired. Please refresh the page and try again.';
            if ($this->isAjaxRequest()) {
                $this->json(['error' => $errorMsg], 400);
            } else {
                setFlash('error', $errorMsg);
                $this->redirect('/checkout');
            }
            return;
        }

        // Check if payment intent data is too old (30 minutes)
        if ($paymentIntentData && time() - ($paymentIntentData['created_at'] ?? 0) > 1800) {
            unset($_SESSION['payment_intent_data']);
            $errorMsg = 'Payment session expired. Please refresh the page and try again.';
            if ($this->isAjaxRequest()) {
                $this->json(['error' => $errorMsg], 400);
            } else {
                setFlash('error', $errorMsg);
                $this->redirect('/checkout');
            }
            return;
        }

        // Get form data
        $email = trim($this->post('email', ''));

        // Shipping address
        $shippingFirstName = trim($this->post('shipping_first_name', ''));
        $shippingLastName = trim($this->post('shipping_last_name', ''));
        $shippingAddress1 = trim($this->post('shipping_address1', ''));
        $shippingAddress2 = trim($this->post('shipping_address2', ''));
        $shippingCity = trim($this->post('shipping_city', ''));
        $shippingState = trim($this->post('shipping_state', ''));
        $shippingPostal = trim($this->post('shipping_postal', ''));
        $shippingCountry = trim($this->post('shipping_country', 'US'));
        $shippingPhone = trim($this->post('shipping_phone', ''));

        // Billing same as shipping?
        $billingSameAsShipping = $this->post('billing_same', '1') === '1';

        // Validate required fields (shipping fields only required for physical products)
        if (empty($email)) {
            if ($this->isAjaxRequest()) {
                $this->json(['error' => 'Please enter your email address'], 400);
            } else {
                setFlash('error', 'Please enter your email address');
                $this->redirect('/checkout');
            }
            return;
        }

        if (!$isDigitalOnly && (empty($shippingFirstName) || empty($shippingLastName) ||
            empty($shippingAddress1) || empty($shippingCity) || empty($shippingState) ||
            empty($shippingPostal))) {
            if ($this->isAjaxRequest()) {
                $this->json(['error' => 'Please fill in all required shipping fields'], 400);
            } else {
                setFlash('error', 'Please fill in all required shipping fields');
                $this->redirect('/checkout');
            }
            return;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if ($this->isAjaxRequest()) {
                $this->json(['error' => 'Please enter a valid email address'], 400);
            } else {
                setFlash('error', 'Please enter a valid email address');
                $this->redirect('/checkout');
            }
            return;
        }

        // Calculate totals - use stored values if available to prevent race conditions
        $subtotal = $this->cartModel->getTotal($sessionId, $userId);
        $tax = 0; // TODO: Calculate tax based on location

        // Verify cart hasn't changed since payment was created (if payment intent data exists)
        if ($paymentIntentData && abs($subtotal - $paymentIntentData['subtotal']) > 0.01) {
            unset($_SESSION['payment_intent_data']);
            $errorMsg = 'Your cart has changed. Please review your order and try again.';
            if ($this->isAjaxRequest()) {
                $this->json(['error' => $errorMsg, 'cart_changed' => true], 400);
            } else {
                setFlash('error', $errorMsg);
                $this->redirect('/checkout');
            }
            return;
        }

        // Get shipping method and calculate cost (skip for digital-only orders)
        $shippingMethodIdPost = $this->post('shipping_method_id', '');
        $shippingMethodId = ($shippingMethodIdPost === 'digital') ? 0 : (int) $shippingMethodIdPost;
        $shippingCost = 0;
        $shippingMethodName = null;
        $shippingCarrier = null;
        $estimatedDelivery = null;

        if (!$isDigitalOnly && $shippingMethodId) {
            $rateInfo = $this->shippingCalculator->getRate($shippingMethodId, $subtotal, $items, $shippingCountry);
            if ($rateInfo) {
                $shippingCost = $rateInfo['rate'] ?? 0;
                $shippingMethodName = $rateInfo['name'] ?? 'Standard Shipping';
                $shippingCarrier = $rateInfo['carrier'] ?? null;
                $estimatedDelivery = $rateInfo['delivery_estimate'] ?? null;
            }
        } elseif ($isDigitalOnly) {
            // Digital orders have no shipping
            $shippingMethodName = 'Digital Delivery';
            $shippingCost = 0;
        }

        // Validate shipping method is selected and valid (skip for digital-only orders)
        if (!$isDigitalOnly && (!$shippingMethodId || ($shippingMethodId && !isset($rateInfo)))) {
            $errorMsg = !$shippingMethodId ? 'Please select a shipping method' : 'Selected shipping method is no longer available';
            if ($this->isAjaxRequest()) {
                $this->json(['error' => $errorMsg], 400);
            } else {
                setFlash('error', $errorMsg);
                $this->redirect('/checkout');
            }
            return;
        }

        // Apply coupon discount if present
        // Use stored values from payment intent data if available to prevent race conditions
        $discountAmount = 0;
        $discountCodeId = null;
        $isPopupCoupon = false;

        if ($paymentIntentData) {
            // Use the stored values (prevents race conditions with coupon expiry/usage)
            $discountAmount = $paymentIntentData['discount_amount'];
            $discountCodeId = $paymentIntentData['coupon_id'];
            $isPopupCoupon = $paymentIntentData['is_popup_coupon'] ?? false;
            // Also use stored shipping cost to prevent mismatch
            $shippingCost = $paymentIntentData['shipping_cost'];
        } else {
            // No payment intent data (free orders) - calculate fresh
            $appliedCoupon = $_SESSION['applied_coupon'] ?? null;

            if ($appliedCoupon) {
                $items = $this->cartModel->getItems($sessionId, $userId);

                // Check if it's a popup coupon
                if (!empty($appliedCoupon['is_popup'])) {
                    $popupCouponModel = new PopupCoupon();
                    $validation = $popupCouponModel->validate($appliedCoupon['code']);

                    if (!empty($validation['valid']) && !empty($validation['coupon']['discount_percent'])) {
                        $discountAmount = $subtotal * ($validation['coupon']['discount_percent'] / 100);
                        $discountCodeId = $appliedCoupon['id'] ?? null;
                        $isPopupCoupon = true;
                    } else {
                        unset($_SESSION['applied_coupon']);
                    }
                } else {
                    // Re-validate regular coupon at checkout time
                    $validation = $this->couponModel->validateCoupon($appliedCoupon['code'], $userId, $subtotal, $items);

                    if ($validation['valid']) {
                        $discountAmount = $validation['discount'];
                        $discountCodeId = $appliedCoupon['id'];
                    } else {
                        // Coupon no longer valid, remove it
                        unset($_SESSION['applied_coupon']);
                    }
                }
            }
        }

        $total = $subtotal + $tax + $shippingCost - $discountAmount;

        // Check if this is a free order
        $isFreeOrder = ($total <= 0);

        // Verify payment with Stripe before creating order (skip for free orders)
        if (!$isFreeOrder) {
            if (empty($paymentIntentId)) {
                if ($this->isAjaxRequest()) {
                    $this->json(['error' => 'Payment information is missing'], 400);
                } else {
                    setFlash('error', 'Payment information is missing');
                    $this->redirect('/checkout');
                }
                return;
            }

            try {
                // Verify payment intent status and amount with Stripe
                \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
                $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

                if ($paymentIntent->status !== 'succeeded') {
                    throw new \Exception('Payment has not been completed. Status: ' . $paymentIntent->status);
                }

                // Verify payment amount matches order total (Stripe uses cents)
                $expectedAmount = (int) round($total * 100);
                if ($paymentIntent->amount !== $expectedAmount) {
                    // Log the mismatch for investigation
                    error_log("Payment amount mismatch: Stripe={$paymentIntent->amount}, Expected={$expectedAmount}, Order Total={$total}");
                    throw new \Exception('Payment amount does not match order total');
                }
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                if ($this->isAjaxRequest()) {
                    $this->json(['error' => 'Invalid payment information'], 400);
                } else {
                    setFlash('error', 'Invalid payment information. Please try again.');
                    $this->redirect('/checkout');
                }
                return;
            } catch (\Exception $e) {
                if ($this->isAjaxRequest()) {
                    $this->json(['error' => $e->getMessage()], 400);
                } else {
                    setFlash('error', $e->getMessage());
                    $this->redirect('/checkout');
                }
                return;
            }
        }

        try {
            $db->beginTransaction();

            // CRITICAL: Lock and validate inventory FIRST to prevent race conditions
            // Using SELECT ... FOR UPDATE to acquire exclusive locks on inventory rows
            foreach ($items as $item) {
                if (!empty($item['variant_id'])) {
                    // Lock variant row and check inventory
                    $lockedInventory = $db->selectOne(
                        "SELECT id, inventory_count FROM product_variants WHERE id = ? FOR UPDATE",
                        [$item['variant_id']]
                    );
                    if (!$lockedInventory || $lockedInventory['inventory_count'] < $item['quantity']) {
                        $db->rollback();
                        $available = $lockedInventory['inventory_count'] ?? 0;
                        $errorMsg = "Sorry, '{$item['name']}' only has {$available} items in stock.";
                        if ($this->isAjaxRequest()) {
                            $this->json(['error' => $errorMsg, 'inventory_error' => true], 400);
                        } else {
                            setFlash('error', $errorMsg);
                            $this->redirect('/cart');
                        }
                        return;
                    }
                } else {
                    // Lock product row and check inventory
                    $lockedInventory = $db->selectOne(
                        "SELECT id, inventory_count FROM products WHERE id = ? FOR UPDATE",
                        [$item['product_id']]
                    );
                    if (!$lockedInventory || $lockedInventory['inventory_count'] < $item['quantity']) {
                        $db->rollback();
                        $available = $lockedInventory['inventory_count'] ?? 0;
                        $errorMsg = "Sorry, '{$item['name']}' only has {$available} items in stock.";
                        if ($this->isAjaxRequest()) {
                            $this->json(['error' => $errorMsg, 'inventory_error' => true], 400);
                        } else {
                            setFlash('error', $errorMsg);
                            $this->redirect('/cart');
                        }
                        return;
                    }
                }
            }
            // Inventory is now locked and validated - safe to proceed

            // Create shipping address
            $shippingAddressId = $db->insert(
                "INSERT INTO addresses (user_id, type, first_name, last_name, address_line1, address_line2, city, state, postal_code, country, phone)
                 VALUES (?, 'shipping', ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$userId, $shippingFirstName, $shippingLastName, $shippingAddress1, $shippingAddress2, $shippingCity, $shippingState, $shippingPostal, $shippingCountry, $shippingPhone]
            );

            // Create billing address (same or different)
            if ($billingSameAsShipping) {
                $billingAddressId = $db->insert(
                    "INSERT INTO addresses (user_id, type, first_name, last_name, address_line1, address_line2, city, state, postal_code, country, phone)
                     VALUES (?, 'billing', ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$userId, $shippingFirstName, $shippingLastName, $shippingAddress1, $shippingAddress2, $shippingCity, $shippingState, $shippingPostal, $shippingCountry, $shippingPhone]
                );
            } else {
                // Get billing address from form
                $billingFirstName = trim($this->post('billing_first_name', ''));
                $billingLastName = trim($this->post('billing_last_name', ''));
                $billingAddress1 = trim($this->post('billing_address1', ''));
                $billingAddress2 = trim($this->post('billing_address2', ''));
                $billingCity = trim($this->post('billing_city', ''));
                $billingState = trim($this->post('billing_state', ''));
                $billingPostal = trim($this->post('billing_postal', ''));
                $billingCountry = trim($this->post('billing_country', 'US'));
                $billingPhone = trim($this->post('billing_phone', ''));

                $billingAddressId = $db->insert(
                    "INSERT INTO addresses (user_id, type, first_name, last_name, address_line1, address_line2, city, state, postal_code, country, phone)
                     VALUES (?, 'billing', ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$userId, $billingFirstName, $billingLastName, $billingAddress1, $billingAddress2, $billingCity, $billingState, $billingPostal, $billingCountry, $billingPhone]
                );
            }

            // Generate order number
            $orderNumber = 'LPS-' . strtoupper(substr(md5(uniqid()), 0, 8));

            // Set payment method based on order type
            $paymentMethod = $isFreeOrder ? 'free' : 'stripe';
            $stripePaymentId = $isFreeOrder ? null : $paymentIntentId;

            // Create order
            $orderId = $db->insert(
                "INSERT INTO orders (user_id, order_number, status, subtotal, tax, shipping_cost, discount_amount, discount_code_id, total, payment_method, payment_status, stripe_payment_intent_id, billing_address_id, shipping_address_id, customer_email, shipping_carrier, shipping_method, shipping_method_id, estimated_delivery)
                 VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, 'paid', ?, ?, ?, ?, ?, ?, ?, ?)",
                [$userId, $orderNumber, $subtotal, $tax, $shippingCost, $discountAmount, $discountCodeId, $total, $paymentMethod, $stripePaymentId, $billingAddressId, $shippingAddressId, $email, $shippingCarrier, $shippingMethodName, $shippingMethodId, $estimatedDelivery]
            );

            // Record coupon usage if a coupon was applied
            if ($discountCodeId) {
                if ($isPopupCoupon) {
                    // Mark popup coupon as used
                    $popupCouponModel = new PopupCoupon();
                    $popupCouponModel->markUsed($discountCodeId, $orderId);
                } elseif ($userId) {
                    $this->couponModel->recordUsage($discountCodeId, $userId, $orderId);
                }
            }

            // Create order items and decrement inventory
            foreach ($items as $item) {
                $itemPrice = $item['sale_price'] ?? $item['price'];
                if (!empty($item['price_adjustment'])) {
                    $itemPrice += $item['price_adjustment'];
                }
                $itemTotal = $itemPrice * $item['quantity'];

                $productName = $item['name'];
                if (!empty($item['variant_name'])) {
                    $productName .= ' - ' . $item['variant_name'];
                }

                $db->insert(
                    "INSERT INTO order_items (order_id, product_id, variant_id, product_name, product_sku, quantity, price, total)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$orderId, $item['product_id'], $item['variant_id'] ?? null, $productName, $item['sku'], $item['quantity'], $itemPrice, $itemTotal]
                );

                // Decrement inventory (skip for digital products, rows are already locked by FOR UPDATE above)
                $productInfo = $db->selectOne("SELECT is_digital FROM products WHERE id = ?", [$item['product_id']]);
                if (!$productInfo || !$productInfo['is_digital']) {
                    if (!empty($item['variant_id'])) {
                        $affected = $db->update(
                            "UPDATE product_variants SET inventory_count = inventory_count - ? WHERE id = ? AND inventory_count >= ?",
                            [$item['quantity'], $item['variant_id'], $item['quantity']]
                        );
                        if ($affected === 0) {
                            throw new \Exception("Inventory depleted for {$item['name']} during checkout");
                        }
                    } else {
                        $affected = $db->update(
                            "UPDATE products SET inventory_count = inventory_count - ? WHERE id = ? AND inventory_count >= ?",
                            [$item['quantity'], $item['product_id'], $item['quantity']]
                        );
                        if ($affected === 0) {
                            throw new \Exception("Inventory depleted for {$item['name']} during checkout");
                        }
                    }
                }
            }

            // Handle digital products - generate licenses and download access
            $licenseModel = new OrderLicense();
            $downloadModel = new OrderDownload();
            $generatedLicenses = [];
            $generatedDownloads = [];

            // Get order items with product details
            $orderItemsWithProducts = $db->select(
                "SELECT oi.*, p.is_digital, p.is_license_product, p.download_file, p.download_limit,
                        pv.license_edition
                 FROM order_items oi
                 JOIN products p ON oi.product_id = p.id
                 LEFT JOIN product_variants pv ON oi.variant_id = pv.id
                 WHERE oi.order_id = ?",
                [$orderId]
            );

            foreach ($orderItemsWithProducts as $orderItem) {
                // Generate license keys for license products
                if ($orderItem['is_license_product']) {
                    $editionCode = $orderItem['license_edition'] ?? 'S'; // Default to Standard

                    // Generate one license per quantity
                    for ($i = 0; $i < $orderItem['quantity']; $i++) {
                        $license = $licenseModel->generateForOrder(
                            $orderId,
                            $orderItem['id'],
                            $orderItem['product_id'],
                            $editionCode,
                            null // Domain is set later by customer
                        );
                        $generatedLicenses[] = array_merge($license, [
                            'product_name' => $orderItem['product_name']
                        ]);
                    }
                }

                // Create download access for digital products
                if ($orderItem['is_digital'] && $orderItem['download_file']) {
                    $download = $downloadModel->createDownloadAccess(
                        $orderId,
                        $orderItem['id'],
                        $orderItem['product_id'],
                        $orderItem['download_limit'] ?? 5, // Default 5 downloads
                        30 // 30 days expiration
                    );
                    $generatedDownloads[] = array_merge($download, [
                        'product_name' => $orderItem['product_name']
                    ]);
                }
            }

            // Add order status history
            $db->insert(
                "INSERT INTO order_status_history (order_id, status, notes) VALUES (?, 'pending', 'Order placed')",
                [$orderId]
            );

            // Clear cart, coupon, and payment intent data
            $this->cartModel->clear($sessionId, $userId);
            unset($_SESSION['applied_coupon']);
            unset($_SESSION['payment_intent_data']);

            $db->commit();

            // Send notifications (push + email) - non-blocking
            try {
                $notificationService = new OrderNotificationService();
                $notificationService->notifyNewOrder($orderId);
            } catch (\Exception $e) {
                // Don't let notification failures affect the order
                error_log("Order notification failed: " . $e->getMessage());
            }

            // Create review request entries for this order (only for registered users)
            if ($userId) {
                try {
                    $reviewModel = new \App\Models\Review();
                    $reviewModel->createReviewRequests($orderId);
                } catch (\Exception $e) {
                    error_log("Review request creation failed: " . $e->getMessage());
                }
            }

            // Store order number in session for confirmation page
            $_SESSION['last_order_number'] = $orderNumber;
            $_SESSION['last_order_id'] = $orderId;

            // Store digital product info for confirmation page
            if (!empty($generatedLicenses)) {
                $_SESSION['last_order_licenses'] = $generatedLicenses;
            }
            if (!empty($generatedDownloads)) {
                $_SESSION['last_order_downloads'] = $generatedDownloads;
            }

            if ($this->isAjaxRequest()) {
                $this->json([
                    'success' => true,
                    'orderNumber' => $orderNumber,
                    'redirectUrl' => '/checkout/confirm'
                ]);
            } else {
                $this->redirect('/checkout/confirm');
            }

        } catch (\Exception $e) {
            $db->rollback();

            if ($this->isAjaxRequest()) {
                $this->json(['error' => 'Failed to process order: ' . $e->getMessage()], 500);
            } else {
                setFlash('error', 'Failed to process order. Please try again.');
                $this->redirect('/checkout');
            }
        }
    }

    /**
     * Order confirmation page
     */
    public function confirm(): void
    {
        $orderNumber = $_SESSION['last_order_number'] ?? null;
        $orderId = $_SESSION['last_order_id'] ?? null;

        if (!$orderNumber || !$orderId) {
            $this->redirect('/');
            return;
        }

        $db = Database::getInstance();

        // Get order details
        $order = $db->selectOne(
            "SELECT o.*,
                    sa.first_name as ship_first_name, sa.last_name as ship_last_name,
                    sa.address_line1 as ship_address1, sa.address_line2 as ship_address2,
                    sa.city as ship_city, sa.state as ship_state, sa.postal_code as ship_postal,
                    sa.country as ship_country
             FROM orders o
             LEFT JOIN addresses sa ON o.shipping_address_id = sa.id
             WHERE o.id = ?",
            [$orderId]
        );

        // Get order items
        $orderItems = $db->select(
            "SELECT * FROM order_items WHERE order_id = ?",
            [$orderId]
        );

        // Get licenses and downloads from session
        $licenses = $_SESSION['last_order_licenses'] ?? [];
        $downloads = $_SESSION['last_order_downloads'] ?? [];

        // Clear session order data
        unset($_SESSION['last_order_number']);
        unset($_SESSION['last_order_id']);
        unset($_SESSION['last_order_licenses']);
        unset($_SESSION['last_order_downloads']);

        $this->render('checkout/confirm', [
            'title' => 'Order Confirmed',
            'order' => $order,
            'orderItems' => $orderItems,
            'licenses' => $licenses,
            'downloads' => $downloads
        ]);
    }

    /**
     * Apply coupon code (AJAX)
     */
    public function applyCoupon(): void
    {
        $this->requireValidCSRF();

        $code = trim($this->post('coupon_code', ''));
        $sessionId = session_id();
        $userId = auth() ? auth()['id'] : null;

        if (empty($code)) {
            $this->json(['success' => false, 'error' => 'Please enter a coupon code']);
            return;
        }

        // Get cart items for validation
        $items = $this->cartModel->getItems($sessionId, $userId);
        $cartTotal = $this->cartModel->getTotal($sessionId, $userId);

        // Check if this is a popup coupon (exit-intent discount)
        $popupCouponModel = new PopupCoupon();
        $isPopupCoupon = $popupCouponModel->isPopupCoupon($code);

        // Check if a popup coupon is already applied - prevent stacking
        $appliedCoupon = $_SESSION['applied_coupon'] ?? null;
        if (is_array($appliedCoupon) && !empty($appliedCoupon['is_popup'])) {
            $this->json(['success' => false, 'error' => 'Exit-intent discount cannot be combined with other codes']);
            return;
        }

        // If trying to apply a popup coupon when another coupon exists
        if ($isPopupCoupon && $appliedCoupon) {
            $this->json(['success' => false, 'error' => 'This discount code cannot be combined with other codes']);
            return;
        }

        if ($isPopupCoupon) {
            // Validate popup coupon
            $popupResult = $popupCouponModel->validate($code);

            if (!$popupResult['valid']) {
                $this->json(['success' => false, 'error' => $popupResult['error']]);
                return;
            }

            $popupCoupon = $popupResult['coupon'];
            $discount = $cartTotal * ($popupCoupon['discount_percent'] / 100);

            // Check minimum order
            if ($popupCoupon['min_order'] > 0 && $cartTotal < $popupCoupon['min_order']) {
                $this->json(['success' => false, 'error' => 'Minimum order of $' . number_format($popupCoupon['min_order'], 2) . ' required']);
                return;
            }

            // Store popup coupon in session
            $_SESSION['applied_coupon'] = [
                'id' => $popupCoupon['id'],
                'code' => $popupCoupon['code'],
                'type' => 'percentage',
                'value' => $popupCoupon['discount_percent'],
                'discount' => $discount,
                'is_popup' => true
            ];

            $newTotal = $cartTotal - $discount;

            $this->json([
                'success' => true,
                'coupon' => $popupCoupon['code'],
                'discount' => $discount,
                'discountFormatted' => '$' . number_format($discount, 2),
                'newTotal' => $newTotal,
                'newTotalFormatted' => '$' . number_format($newTotal, 2)
            ]);
            return;
        }

        // Validate regular coupon
        $result = $this->couponModel->validateCoupon($code, $userId, $cartTotal, $items);

        if (!$result['valid']) {
            $this->json(['success' => false, 'error' => $result['error']]);
            return;
        }

        // Store coupon in session
        $_SESSION['applied_coupon'] = [
            'id' => $result['coupon']['id'],
            'code' => $result['coupon']['code'],
            'type' => $result['coupon']['type'],
            'value' => $result['coupon']['value'],
            'discount' => $result['discount'],
            'is_popup' => false
        ];

        $newTotal = $cartTotal - $result['discount'];

        $this->json([
            'success' => true,
            'coupon' => $result['coupon']['code'],
            'discount' => $result['discount'],
            'discountFormatted' => '$' . number_format($result['discount'], 2),
            'newTotal' => $newTotal,
            'newTotalFormatted' => '$' . number_format($newTotal, 2)
        ]);
    }

    /**
     * Remove coupon code (AJAX)
     */
    public function removeCoupon(): void
    {
        $this->requireValidCSRF();

        unset($_SESSION['applied_coupon']);

        $sessionId = session_id();
        $userId = auth() ? auth()['id'] : null;
        $cartTotal = $this->cartModel->getTotal($sessionId, $userId);

        $this->json([
            'success' => true,
            'newTotal' => $cartTotal,
            'newTotalFormatted' => '$' . number_format($cartTotal, 2)
        ]);
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Handle Stripe webhook events
     */
    public function webhookStripe(): void
    {
        // Get the raw POST body
        $payload = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        // Verify webhook signature
        $webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

        if (empty($webhookSecret)) {
            error_log('Stripe webhook secret not configured');
            http_response_code(500);
            echo json_encode(['error' => 'Webhook not configured']);
            return;
        }

        try {
            \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            error_log('Stripe webhook invalid payload: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload']);
            return;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            error_log('Stripe webhook invalid signature: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode(['error' => 'Invalid signature']);
            return;
        }

        $db = \App\Core\Database::getInstance();

        // Handle the event
        switch ($event->type) {
            case 'charge.refunded':
                $charge = $event->data->object;
                $paymentIntentId = $charge->payment_intent;

                // Find the order by payment intent ID
                $order = $db->selectOne(
                    "SELECT id, order_number, status FROM orders WHERE stripe_payment_intent_id = ?",
                    [$paymentIntentId]
                );

                if ($order) {
                    // Determine if full or partial refund
                    $amountRefunded = $charge->amount_refunded / 100;
                    $amountTotal = $charge->amount / 100;

                    $newStatus = ($amountRefunded >= $amountTotal) ? 'refunded' : 'partially_refunded';

                    // Update order status
                    $db->update(
                        "UPDATE orders SET status = ?, payment_status = ? WHERE id = ?",
                        [$newStatus, $newStatus, $order['id']]
                    );

                    // Add status history
                    $refundNote = ($newStatus === 'refunded')
                        ? "Full refund of \${$amountRefunded} processed via Stripe"
                        : "Partial refund of \${$amountRefunded} processed via Stripe";

                    $db->insert(
                        "INSERT INTO order_status_history (order_id, status, notes) VALUES (?, ?, ?)",
                        [$order['id'], $newStatus, $refundNote]
                    );

                    error_log("Order {$order['order_number']} marked as {$newStatus}");
                }
                break;

            case 'charge.dispute.created':
                $dispute = $event->data->object;
                $chargeId = $dispute->charge;

                $charge = \Stripe\Charge::retrieve($chargeId);
                $paymentIntentId = $charge->payment_intent;

                $order = $db->selectOne(
                    "SELECT id, order_number FROM orders WHERE stripe_payment_intent_id = ?",
                    [$paymentIntentId]
                );

                if ($order) {
                    $disputeAmount = $dispute->amount / 100;
                    $db->insert(
                        "INSERT INTO order_status_history (order_id, status, notes) VALUES (?, ?, ?)",
                        [$order['id'], 'disputed', "CHARGEBACK DISPUTE: \${$disputeAmount} - Reason: {$dispute->reason}"]
                    );

                    error_log("ALERT: Chargeback dispute on order {$order['order_number']} for \${$disputeAmount}");
                }
                break;

            default:
                error_log('Unhandled Stripe webhook event: ' . $event->type);
        }

        http_response_code(200);
        echo json_encode(['received' => true]);
    }
}
