<?php

namespace App\Plugins;

use App\Core\Database;
use App\Core\OrderStatusEmailService;
use App\Core\Plugins\HookRegistry;
use App\Core\Plugins\PluginInterface;

class PrintifySyncPlugin implements PluginInterface
{
    private const API_BASE = 'https://api.printify.com/v1';
    private const USER_AGENT = 'Apparix Printify Sync/1.0';
    private const DEFAULT_AVAILABLE_STOCK = 100;
    private const SHIPPING_METHOD_IDS = [
        'standard' => -9101,
        'priority' => -9102,
        'printify_express' => -9103,
        'economy' => -9104,
    ];

    private array $settings = [];
    private int $requestCount = 0;
    private int $windowStart = 0;

    public function getSlug(): string { return 'printify-sync'; }
    public function getName(): string { return 'Printify Sync'; }
    public function getVersion(): string { return '1.0.8'; }
    public function getType(): string { return 'marketplace'; }
    public function getDescription(): string { return 'Sync selected products, automatic inventory, live shipping, fulfillment, tracking, and order statuses with Printify.'; }
    public function getAuthor(): string { return 'Apparix'; }

    public function getDefaultSettings(): array
    {
        return [
            'api_token' => '',
            'shop_id' => '',
            'sync_products' => [],
            'variant_map' => [],
            'default_blueprint_id' => '',
            'default_print_provider_id' => '',
            'default_variant_id' => '',
            'publish_after_create' => false,
            'send_orders' => true,
            'sync_order_statuses' => false,
            'sync_inventory' => true,
            'inventory_sync_interval_hours' => 1,
            'sync_price' => true,
        ];
    }

    public function init(): void
    {
        $this->loadSettings();
        HookRegistry::add('after_order_create', [$this, 'handleOrderCreated'], 10);
        HookRegistry::add('shipping_options', [$this, 'filterShippingOptions'], 10);
        HookRegistry::add('checkout_shipping_rate', [$this, 'filterCheckoutShippingRate'], 10);
    }

    public function onActivate(): void
    {
        $this->createSyncTables();
        $this->log('activate', 'success', 'Printify Sync plugin activated');
    }

    public function filterShippingOptions(array $result, array $cartItems, array $address, float $subtotal): array
    {
        if (!$this->isConfigured() || empty($cartItems)) {
            return $result;
        }

        $quote = $this->getShippingQuote($cartItems, $address);
        if (empty($quote['applicable']) || !empty($quote['incomplete'])) {
            return $result;
        }
        if (!empty($quote['validation_error'])) {
            return ['success' => false, 'options' => [], 'error' => $quote['validation_error']];
        }
        if (empty($quote['success'])) {
            return ['success' => false, 'options' => [], 'error' => 'Unable to retrieve Printify shipping for this address. Please verify the address and try again.'];
        }

        $options = [];
        foreach ($quote['rates'] as $type => $amount) {
            $options[] = [
                'method_id' => self::SHIPPING_METHOD_IDS[$type],
                'method_code' => 'printify_' . $type,
                'name' => $this->shippingMethodName($type),
                'rate' => $amount,
                'is_free' => $amount <= 0,
                'carrier' => 'Printify',
                'delivery_estimate' => null,
                'min_order_free' => null,
            ];
        }

        usort($options, static fn(array $left, array $right): int => $left['rate'] <=> $right['rate']);

        return [
            'success' => !empty($options),
            'options' => $options,
            'free_shipping_threshold' => null,
            'amount_until_free' => null,
            'error' => empty($options) ? 'Printify did not return shipping options for this address.' : null,
        ];
    }

    public function filterCheckoutShippingRate(?array $rateInfo, array $cartItems, array $address, int $methodId): ?array
    {
        $type = array_search($methodId, self::SHIPPING_METHOD_IDS, true);
        if (!$this->isConfigured()) {
            return $type === false ? $rateInfo : null;
        }

        $quote = $this->getShippingQuote($cartItems, $address);
        if (!empty($quote['applicable']) && $type === false) {
            return null;
        }
        if ($type === false) {
            return $rateInfo;
        }
        if (empty($quote['applicable']) || empty($quote['success']) || !array_key_exists($type, $quote['rates'])) {
            return null;
        }

        $amount = (float)$quote['rates'][$type];
        return [
            'method_id' => $methodId,
            'method_code' => 'printify_' . $type,
            'name' => $this->shippingMethodName($type),
            'rate' => $amount,
            'is_free' => $amount <= 0,
            'carrier' => 'Printify',
            'delivery_estimate' => null,
        ];
    }

    private function getShippingQuote(array $cartItems, array $address): array
    {
        $lineItems = [];
        $physicalItems = 0;

        foreach ($cartItems as $item) {
            if (!empty($item['is_digital'])) {
                continue;
            }
            $physicalItems++;
            $mapping = Database::getInstance()->selectOne(
                "SELECT printify_product_id, printify_variant_id
                 FROM printify_product_sync
                 WHERE product_id = ? AND variant_id = ? AND sync_status = 'synced'
                   AND printify_product_id IS NOT NULL AND printify_variant_id IS NOT NULL",
                [(int)$item['product_id'], (int)($item['variant_id'] ?? 0)]
            );
            if (!$mapping) {
                return ['success' => false, 'applicable' => false, 'rates' => []];
            }
            $lineItems[] = [
                'external_id' => 'apparix-quote-' . count($lineItems),
                'product_id' => (string)$mapping['printify_product_id'],
                'variant_id' => (int)$mapping['printify_variant_id'],
                'quantity' => max(1, (int)($item['quantity'] ?? 1)),
            ];
        }

        if ($physicalItems === 0 || count($lineItems) !== $physicalItems) {
            return ['success' => false, 'applicable' => false, 'rates' => []];
        }

        foreach (['address1', 'city', 'postal', 'country'] as $required) {
            if (trim((string)($address[$required] ?? '')) === '') {
                return ['success' => false, 'applicable' => true, 'incomplete' => true, 'rates' => []];
            }
        }
        if (trim((string)($address['phone'] ?? '')) === '') {
            return [
                'success' => false,
                'applicable' => true,
                'rates' => [],
                'validation_error' => 'A phone number is required to calculate Printify shipping.',
            ];
        }

        $addressTo = [
            'first_name' => (string)($address['first_name'] ?: 'Customer'),
            'last_name' => (string)($address['last_name'] ?? ''),
            'email' => filter_var($address['email'] ?? '', FILTER_VALIDATE_EMAIL) ? (string)$address['email'] : 'shipping@apparix.app',
            'phone' => (string)($address['phone'] ?? ''),
            'country' => strtoupper((string)$address['country']),
            'region' => (string)($address['state'] ?? ''),
            'address1' => (string)$address['address1'],
            'address2' => (string)($address['address2'] ?? ''),
            'city' => (string)$address['city'],
            'zip' => (string)$address['postal'],
        ];
        $cacheKey = hash('sha256', json_encode([$lineItems, $addressTo], JSON_UNESCAPED_SLASHES));
        $cached = $_SESSION['printify_shipping_quotes'][$cacheKey] ?? null;
        if (is_array($cached) && (int)($cached['expires_at'] ?? 0) >= time()) {
            return ['success' => true, 'applicable' => true, 'rates' => $cached['rates']];
        }

        $response = $this->apiRequest(
            'POST',
            '/shops/' . urlencode((string)$this->settings['shop_id']) . '/orders/shipping.json',
            ['line_items' => $lineItems, 'address_to' => $addressTo]
        );
        if (empty($response['success']) || !is_array($response['data'] ?? null)) {
            return [
                'success' => false,
                'applicable' => true,
                'rates' => [],
                'provider_error' => $response['error'] ?? 'Printify shipping quote failed',
                'validation' => $response['validation'] ?? null,
            ];
        }

        $rates = [];
        foreach (self::SHIPPING_METHOD_IDS as $type => $unusedMethodId) {
            if (isset($response['data'][$type]) && is_numeric($response['data'][$type])) {
                $rates[$type] = round(((float)$response['data'][$type]) / 100, 2);
            }
        }
        if (!isset($rates['priority']) && isset($response['data']['express']) && is_numeric($response['data']['express'])) {
            $rates['priority'] = round(((float)$response['data']['express']) / 100, 2);
        }
        if (empty($rates)) {
            return ['success' => false, 'applicable' => true, 'rates' => []];
        }

        $_SESSION['printify_shipping_quotes'] = [
            $cacheKey => ['rates' => $rates, 'expires_at' => time() + 300],
        ];
        return ['success' => true, 'applicable' => true, 'rates' => $rates];
    }

    private function shippingMethodName(string $type): string
    {
        return match ($type) {
            'printify_express' => 'Printify Express Shipping',
            default => 'Printify ' . ucfirst($type) . ' Shipping',
        };
    }

    public function onDeactivate(): void
    {
        $this->log('deactivate', 'success', 'Printify Sync plugin deactivated');
    }

    public function getSettingsView(): string
    {
        $settings = array_merge($this->getDefaultSettings(), $this->settings);
        $products = $this->getSelectableProducts();
        $selectedProductIds = array_map('intval', $settings['sync_products'] ?? []);
        $shopsResult = $this->isConfiguredForToken() ? $this->getShops() : ['success' => false];
        $shops = $shopsResult['success'] ? ($shopsResult['data'] ?? []) : [];
        $connectionError = $this->isConfiguredForToken() && !$shopsResult['success'] ? ($shopsResult['error'] ?? null) : null;
        $printifyPage = max(1, (int)($_GET['printify_page'] ?? 1));
        $printifySearch = trim((string)($_GET['printify_search'] ?? ''));
        $printifyProductsResult = $this->isConfigured()
            ? ($printifySearch !== '' ? $this->searchPrintifyProducts($printifySearch) : $this->getPrintifyProducts($printifyPage))
            : ['success' => false, 'data' => []];
        $printifyProducts = $printifyProductsResult['success'] ? ($printifyProductsResult['data']['data'] ?? $printifyProductsResult['data'] ?? []) : [];
        $syncedPrintifyProducts = $this->getSyncedPrintifyProductMap();
        $printifyPagination = $printifyProductsResult['success'] && is_array($printifyProductsResult['data'] ?? null) ? $printifyProductsResult['data'] : [];
        $printifyProductsError = $this->isConfigured() && !$printifyProductsResult['success'] ? ($printifyProductsResult['error'] ?? null) : null;

        ob_start();
        include __DIR__ . '/views/settings.php';
        return ob_get_clean();
    }

    private function getSyncedPrintifyProductMap(): array
    {
        try {
            $rows = Database::getInstance()->select(
                "SELECT ps.printify_product_id, MIN(ps.product_id) AS product_id, COUNT(*) AS sync_rows, MAX(ps.last_synced_at) AS last_synced_at, p.name, p.slug
                 FROM printify_product_sync ps
                 LEFT JOIN products p ON p.id = ps.product_id
                 WHERE ps.printify_product_id IS NOT NULL AND ps.sync_status = 'synced'
                 GROUP BY ps.printify_product_id, p.name, p.slug"
            );
        } catch (\Throwable $e) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $printifyProductId = (string)($row['printify_product_id'] ?? '');
            if ($printifyProductId === '') {
                continue;
            }
            $map[$printifyProductId] = [
                'product_id' => (int)($row['product_id'] ?? 0),
                'sync_rows' => (int)($row['sync_rows'] ?? 0),
                'last_synced_at' => $row['last_synced_at'] ?? null,
                'name' => $row['name'] ?? null,
                'slug' => $row['slug'] ?? null,
            ];
        }
        return $map;
    }
    public function getSettingsSchema(): array
    {
        $manifest = json_decode((string)file_get_contents(__DIR__ . '/plugin.json'), true);
        return $manifest['settings'] ?? [];
    }

    public function validateSettings(array $settings): array
    {
        $errors = [];
        if (empty($settings['api_token'])) {
            $errors[] = 'Printify Personal Access Token is required';
        }
        if (empty($settings['shop_id']) || !ctype_digit((string)$settings['shop_id'])) {
            $errors[] = 'Printify Shop ID must be numeric';
        }
        $inventoryInterval = (int)($settings['inventory_sync_interval_hours'] ?? 1);
        if (!in_array($inventoryInterval, [1, 6, 24], true)) {
            $errors[] = 'Inventory sync interval must be 1, 6, or 24 hours';
        }
        foreach (['default_blueprint_id', 'default_print_provider_id', 'default_variant_id'] as $key) {
            if (!empty($settings[$key]) && !ctype_digit((string)$settings[$key])) {
                $errors[] = str_replace('_', ' ', ucfirst($key)) . ' must be numeric';
            }
        }
        foreach (($settings['sync_products'] ?? []) as $productId) {
            if (!ctype_digit((string)$productId)) {
                $errors[] = 'Selected product IDs must be numeric';
                break;
            }
        }
        foreach (($settings['variant_map'] ?? []) as $productId => $variants) {
            if (!ctype_digit((string)$productId) || !is_array($variants)) {
                $errors[] = 'Variant mapping data is invalid';
                break;
            }
            foreach ($variants as $variantId => $printifyVariantId) {
                if (!ctype_digit((string)$variantId) || ($printifyVariantId !== '' && !ctype_digit((string)$printifyVariantId))) {
                    $errors[] = 'Variant IDs must be numeric';
                    break 2;
                }
            }
        }
        return $errors;
    }

    public function isConfigured(): bool
    {
        return $this->isConfiguredForToken() && !empty($this->settings['shop_id']);
    }

    public function getShops(): array
    {
        return $this->apiRequest('GET', '/shops.json');
    }

    public function syncSelectedProducts(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Printify Sync is not configured'];
        }
        $productIds = array_values(array_filter(array_map('intval', $this->settings['sync_products'] ?? [])));
        if (empty($productIds)) {
            return ['success' => false, 'error' => 'No Apparix products are selected for Printify sync'];
        }
        $summary = ['success' => true, 'synced' => 0, 'failed' => 0, 'errors' => []];
        foreach ($productIds as $productId) {
            $result = $this->syncProduct($productId);
            if ($result['success']) {
                $summary['synced']++;
            } else {
                $summary['failed']++;
                $summary['errors'][] = "Product {$productId}: " . ($result['error'] ?? 'Unknown error');
            }
        }
        if ($summary['failed'] > 0 && $summary['synced'] === 0) {
            $summary['success'] = false;
        }
        return $summary;
    }

    public function syncProduct(int $productId): array
    {
        $product = $this->getProductForSync($productId);
        if (!$product) {
            return ['success' => false, 'error' => 'Product not found'];
        }
        $payload = $this->buildProductPayload($product);
        if (!$payload['success']) {
            $this->saveProductMapping($productId, 0, null, null, 'error', $payload['error']);
            return $payload;
        }
        $mapping = $this->getProductMapping($productId, 0);
        if ($mapping && !empty($mapping['printify_product_id'])) {
            $response = $this->apiRequest('PUT', '/shops/' . urlencode((string)$this->settings['shop_id']) . '/products/' . urlencode($mapping['printify_product_id']) . '.json', $payload['data']);
            $printifyProductId = $mapping['printify_product_id'];
        } else {
            $response = $this->apiRequest('POST', '/shops/' . urlencode((string)$this->settings['shop_id']) . '/products.json', $payload['data']);
            $printifyProductId = $response['data']['id'] ?? null;
        }
        if (!$response['success'] || empty($printifyProductId)) {
            $error = $response['error'] ?? 'Printify product sync failed';
            $this->saveProductMapping($productId, 0, $printifyProductId, null, 'error', $error);
            return ['success' => false, 'error' => $error];
        }
        foreach ($payload['sync_variants'] as $syncVariant) {
            $this->saveProductMapping($productId, (int)$syncVariant['variant_id'], $printifyProductId, $syncVariant['sku'], 'synced', null);
        }
        if (!empty($this->settings['publish_after_create'])) {
            $this->publishProduct($printifyProductId);
        }
        return ['success' => true, 'printify_product_id' => $printifyProductId];
    }

    public function handleOrderCreated(int $orderId): void
    {
        if (empty($this->settings['send_orders']) || !$this->isConfigured()) {
            return;
        }
        try {
            if (!empty($this->getPrintifyOrderItems($orderId))) {
                $this->saveOrderMapping($orderId, null, 'pending', null);
            }
        } catch (\Throwable $e) {
            $this->log('queue_order', 'error', $e->getMessage(), ['order_id' => $orderId]);
        }
    }

    public function submitPendingOrders(int $limit = 20): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Printify Sync is not configured'];
        }

        $summary = ['success' => true, 'submitted' => 0, 'failed' => 0];

        if (!empty($this->settings['send_orders'])) {
            $pending = Database::getInstance()->select("SELECT order_id FROM printify_order_sync WHERE status IN ('pending', 'error') ORDER BY created_at ASC LIMIT ?", [$limit]);
            foreach ($pending as $row) {
                $result = $this->sendOrderToPrintify((int)$row['order_id']);
                if ($result['success']) {
                    $summary['submitted']++;
                } else {
                    $summary['failed']++;
                }
            }
        }

        $summary['status_sync'] = $this->syncSubmittedOrderStatuses(max(20, $limit));
        if (empty($summary['status_sync']['success']) && ($summary['status_sync']['failed'] ?? 0) > 0) {
            $summary['success'] = false;
        }
        $summary['inventory_sync'] = $this->syncInventoryIfDue();

        return $summary;
    }

    public function syncInventoryIfDue(bool $force = false): array
    {
        if (empty($this->settings['sync_inventory'])) {
            return ['success' => true, 'enabled' => false, 'due' => false, 'products_checked' => 0, 'variants_checked' => 0, 'updated' => 0, 'failed' => 0];
        }
        if (!$this->isConfigured()) {
            return ['success' => false, 'enabled' => true, 'due' => false, 'products_checked' => 0, 'variants_checked' => 0, 'updated' => 0, 'failed' => 0, 'error' => 'Printify Sync is not configured'];
        }

        $db = Database::getInstance();
        $intervalHours = (int)$this->settings['inventory_sync_interval_hours'];
        if (!$force) {
            $lastRun = $db->selectOne(
                "SELECT MAX(created_at) AS last_run
                 FROM printify_sync_log
                 WHERE action = 'inventory_sync' AND status IN ('success', 'warning')"
            );
            $lastRunAt = !empty($lastRun['last_run']) ? strtotime((string)$lastRun['last_run']) : false;
            if ($lastRunAt !== false && $lastRunAt > time() - ($intervalHours * 3600)) {
                return [
                    'success' => true,
                    'enabled' => true,
                    'due' => false,
                    'last_run' => $lastRun['last_run'],
                    'products_checked' => 0,
                    'variants_checked' => 0,
                    'updated' => 0,
                    'failed' => 0,
                ];
            }
        }

        $lockName = 'apparix_printify_inventory_' . substr(hash('sha256', (string)$this->settings['shop_id']), 0, 24);
        $lock = $db->selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lockName]);
        if ((int)($lock['acquired'] ?? 0) !== 1) {
            return ['success' => true, 'enabled' => true, 'due' => true, 'locked' => true, 'products_checked' => 0, 'variants_checked' => 0, 'updated' => 0, 'failed' => 0];
        }

        try {
            return $this->syncPrintifyInventory();
        } catch (\Throwable $e) {
            $this->log('inventory_sync', 'error', $e->getMessage());
            return ['success' => false, 'enabled' => true, 'due' => true, 'products_checked' => 0, 'variants_checked' => 0, 'updated' => 0, 'failed' => 1, 'error' => $e->getMessage()];
        } finally {
            try {
                $db->selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
            } catch (\Throwable $e) {
                error_log('Printify inventory lock release failed: ' . $e->getMessage());
            }
        }
    }

    private function syncPrintifyInventory(): array
    {
        $db = Database::getInstance();
        $products = $db->select(
            "SELECT product_id, printify_product_id
             FROM printify_product_sync
             WHERE sync_status = 'synced'
               AND printify_product_id IS NOT NULL
               AND printify_product_id <> ''
             GROUP BY product_id, printify_product_id
             ORDER BY product_id ASC"
        );

        $summary = [
            'success' => true,
            'enabled' => true,
            'due' => true,
            'products_checked' => 0,
            'variants_checked' => 0,
            'updated' => 0,
            'sold_out' => 0,
            'restocked' => 0,
            'missing_local_variants' => 0,
            'failed' => 0,
        ];

        foreach ($products as $product) {
            $productId = (int)$product['product_id'];
            $printifyProductId = (string)$product['printify_product_id'];
            $response = $this->apiRequest(
                'GET',
                '/shops/' . urlencode((string)$this->settings['shop_id']) . '/products/' . urlencode($printifyProductId) . '.json'
            );
            $summary['products_checked']++;

            if (empty($response['success']) || !is_array($response['data'] ?? null)) {
                $summary['failed']++;
                continue;
            }

            try {
                $result = $this->syncPrintifyProductInventory($productId, $printifyProductId, $response['data']);
                foreach (['variants_checked', 'updated', 'sold_out', 'restocked', 'missing_local_variants'] as $key) {
                    $summary[$key] += (int)($result[$key] ?? 0);
                }
            } catch (\Throwable $e) {
                $summary['failed']++;
                $this->log('inventory_product_sync', 'error', $e->getMessage(), [
                    'product_id' => $productId,
                    'printify_product_id' => $printifyProductId,
                ]);
            }
        }

        $summary['success'] = $summary['failed'] === 0;
        $logStatus = $summary['failed'] > 0 ? 'warning' : 'success';
        $this->log(
            'inventory_sync',
            $logStatus,
            'Printify inventory sync completed: ' . $summary['updated'] . ' variant(s) updated',
            $summary
        );
        return $summary;
    }

    private function syncPrintifyProductInventory(int $productId, string $printifyProductId, array $printifyProduct): array
    {
        $remoteVariants = [];
        foreach (($printifyProduct['variants'] ?? []) as $variant) {
            if (!is_array($variant) || (int)($variant['id'] ?? 0) <= 0) {
                continue;
            }
            $remoteVariants[(int)$variant['id']] = $variant;
        }

        $db = Database::getInstance();
        $mappings = $db->select(
            "SELECT ps.variant_id, ps.printify_variant_id,
                    pv.inventory_count AS current_inventory, pv.is_active AS current_active
             FROM printify_product_sync ps
             LEFT JOIN product_variants pv ON pv.id = ps.variant_id AND pv.product_id = ps.product_id
             WHERE ps.product_id = ? AND ps.printify_product_id = ? AND ps.sync_status = 'synced'
             ORDER BY ps.variant_id ASC",
            [$productId, $printifyProductId]
        );

        $result = [
            'variants_checked' => 0,
            'updated' => 0,
            'sold_out' => 0,
            'restocked' => 0,
            'missing_local_variants' => 0,
        ];
        $hasLocalVariants = false;
        $db->beginTransaction();
        try {
            foreach ($mappings as $mapping) {
                $localVariantId = (int)$mapping['variant_id'];
                $printifyVariantId = (int)($mapping['printify_variant_id'] ?? 0);
                if ($printifyVariantId <= 0) {
                    continue;
                }

                $remoteVariant = $remoteVariants[$printifyVariantId] ?? null;
                $inventory = $remoteVariant ? $this->printifyVariantInventory($remoteVariant) : 0;
                $isActive = $remoteVariant && $this->printifyVariantIsActive($remoteVariant) ? 1 : 0;
                $result['variants_checked']++;

                if ($localVariantId > 0) {
                    $hasLocalVariants = true;
                    if ($mapping['current_inventory'] === null) {
                        $result['missing_local_variants']++;
                        continue;
                    }
                    $oldInventory = (int)$mapping['current_inventory'];
                    $oldActive = (int)$mapping['current_active'];
                    if ($oldInventory === $inventory && $oldActive === $isActive) {
                        continue;
                    }

                    $db->update(
                        'UPDATE product_variants SET inventory_count = ?, is_active = ? WHERE id = ? AND product_id = ?',
                        [$inventory, $isActive, $localVariantId, $productId]
                    );
                    $result['updated']++;
                    if ($oldInventory > 0 && $inventory === 0) {
                        $result['sold_out']++;
                    } elseif ($oldInventory === 0 && $inventory > 0) {
                        $result['restocked']++;
                    }
                    continue;
                }

                $currentProduct = $db->selectOne('SELECT inventory_count FROM products WHERE id = ?', [$productId]);
                if ($currentProduct && (int)$currentProduct['inventory_count'] !== $inventory) {
                    $oldInventory = (int)$currentProduct['inventory_count'];
                    $db->update('UPDATE products SET inventory_count = ?, updated_at = NOW() WHERE id = ?', [$inventory, $productId]);
                    $result['updated']++;
                    if ($oldInventory > 0 && $inventory === 0) {
                        $result['sold_out']++;
                    } elseif ($oldInventory === 0 && $inventory > 0) {
                        $result['restocked']++;
                    }
                }
            }

            if ($hasLocalVariants) {
                $db->update(
                    "UPDATE products
                     SET inventory_count = (
                         SELECT COALESCE(SUM(inventory_count), 0)
                         FROM product_variants
                         WHERE product_id = ? AND is_active = 1
                     ), updated_at = NOW()
                     WHERE id = ?",
                    [$productId, $productId]
                );
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }

        return $result;
    }


    public function syncSubmittedOrderStatuses(int $limit = 50): array
    {
        if (empty($this->settings['sync_order_statuses'])) {
            return ['success' => true, 'enabled' => false, 'checked' => 0, 'updated' => 0, 'failed' => 0];
        }
        if (!$this->isConfigured()) {
            return ['success' => false, 'checked' => 0, 'updated' => 0, 'failed' => 0, 'error' => 'Printify Sync is not configured'];
        }

        $limit = max(1, min(100, $limit));
        $mappings = Database::getInstance()->select(
            "SELECT pos.order_id, pos.printify_order_id
             FROM printify_order_sync pos
             JOIN orders o ON o.id = pos.order_id
             WHERE pos.status = 'submitted'
               AND pos.printify_order_id IS NOT NULL
               AND pos.printify_order_id <> ''
               AND o.status NOT IN ('delivered', 'cancelled', 'refunded')
             ORDER BY pos.updated_at ASC
             LIMIT ?",
            [$limit]
        );

        $summary = ['success' => true, 'checked' => 0, 'updated' => 0, 'failed' => 0];
        foreach ($mappings as $mapping) {
            $orderId = (int)$mapping['order_id'];
            $printifyOrderId = (string)$mapping['printify_order_id'];
            $response = $this->apiRequest(
                'GET',
                '/shops/' . urlencode((string)$this->settings['shop_id']) . '/orders/' . urlencode($printifyOrderId) . '.json'
            );
            $summary['checked']++;

            if (empty($response['success']) || !is_array($response['data'] ?? null)) {
                $summary['failed']++;
                $this->log('sync_order_status', 'error', $response['error'] ?? 'Unable to retrieve Printify order', [
                    'order_id' => $orderId,
                    'printify_order_id' => $printifyOrderId,
                ]);
                continue;
            }

            try {
                $result = $this->applyPrintifyOrderUpdate($orderId, $printifyOrderId, $response['data']);
                if (!empty($result['updated'])) {
                    $summary['updated']++;
                }
            } catch (\Throwable $e) {
                $summary['failed']++;
                $this->log('sync_order_status', 'error', $e->getMessage(), [
                    'order_id' => $orderId,
                    'printify_order_id' => $printifyOrderId,
                ]);
            }
        }

        $summary['success'] = $summary['failed'] === 0;
        return $summary;
    }

    private function applyPrintifyOrderUpdate(int $orderId, string $printifyOrderId, array $printifyOrder): array
    {
        $order = $this->getOrderForPrintify($orderId);
        if (!$order) {
            throw new \RuntimeException('Mapped Apparix order no longer exists');
        }

        $printifyStatus = strtolower(trim((string)($printifyOrder['status'] ?? '')));
        $shipment = $this->extractShipmentState($printifyOrder['shipments'] ?? []);
        $targetStatus = $this->mapPrintifyOrderStatus($printifyStatus, $shipment);
        $currentStatus = strtolower((string)($order['status'] ?? 'pending'));
        $nextStatus = $this->forwardOrderStatus($currentStatus, $targetStatus);
        $statusChanged = $nextStatus !== null && $nextStatus !== $currentStatus;
        $trackingChanged = !empty($shipment['tracking_number']) && (
            (string)($order['tracking_number'] ?? '') !== $shipment['tracking_number']
            || (string)($order['shipping_carrier'] ?? '') !== $shipment['carrier']
        );

        if (!$statusChanged && !$trackingChanged) {
            Database::getInstance()->update(
                'UPDATE printify_order_sync SET updated_at = NOW(), error_message = NULL WHERE order_id = ?',
                [$orderId]
            );
            if ($printifyStatus === 'has-issues') {
                $this->log('sync_order_status', 'warning', 'Printify reports an issue with the order', [
                    'order_id' => $orderId,
                    'printify_order_id' => $printifyOrderId,
                ]);
            }
            return ['updated' => false, 'status' => $currentStatus];
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            if ($trackingChanged) {
                $db->update(
                    "UPDATE orders
                     SET shipping_carrier = ?, tracking_number = ?,
                         shipped_at = COALESCE(shipped_at, ?), updated_at = NOW()
                     WHERE id = ?",
                    [
                        $shipment['carrier'],
                        $shipment['tracking_number'],
                        $shipment['shipped_at'] ?? date('Y-m-d H:i:s'),
                        $orderId,
                    ]
                );
            }

            if ($statusChanged) {
                $affected = $db->update(
                    'UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ? AND status = ?',
                    [$nextStatus, $orderId, $currentStatus]
                );
                if ($affected !== 1) {
                    $statusChanged = false;
                } else {
                    $notes = 'Updated automatically from Printify (' . ($printifyStatus ?: 'shipment update') . ')';
                    if (!empty($shipment['tracking_number']) && $nextStatus === 'shipped') {
                        $notes .= '; tracking ' . $shipment['tracking_number'];
                    }
                    $db->query(
                        'INSERT INTO order_status_history (order_id, status, notes) VALUES (?, ?, ?)',
                        [$orderId, $nextStatus, $notes]
                    );
                }
            }

            $db->update(
                'UPDATE printify_order_sync SET updated_at = NOW(), error_message = NULL WHERE order_id = ?',
                [$orderId]
            );
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }

        if ($statusChanged && !empty($order['customer_email'])) {
            try {
                $trackingInfo = !empty($shipment['tracking_number']) ? [
                    'carrier' => $shipment['carrier'],
                    'tracking_number' => $shipment['tracking_number'],
                    'estimated_delivery' => null,
                ] : null;
                (new OrderStatusEmailService())->sendStatusEmail($order, $nextStatus, $trackingInfo);
            } catch (\Throwable $e) {
                $this->log('order_status_email', 'error', $e->getMessage(), [
                    'order_id' => $orderId,
                    'status' => $nextStatus,
                ]);
            }
        }

        $this->log('sync_order_status', 'success', 'Printify order status synchronized', [
            'order_id' => $orderId,
            'printify_order_id' => $printifyOrderId,
            'printify_status' => $printifyStatus,
            'apparix_status' => $statusChanged ? $nextStatus : $currentStatus,
            'tracking_number' => $shipment['tracking_number'] ?? null,
        ]);

        return [
            'updated' => $statusChanged || $trackingChanged,
            'status' => $statusChanged ? $nextStatus : $currentStatus,
            'tracking_updated' => $trackingChanged,
        ];
    }

    private function extractShipmentState(mixed $shipments): array
    {
        if (!is_array($shipments) || empty($shipments)) {
            return ['has_shipments' => false, 'all_delivered' => false];
        }

        $primary = null;
        $allDelivered = true;
        foreach ($shipments as $shipment) {
            if (!is_array($shipment)) {
                $allDelivered = false;
                continue;
            }
            if (empty($shipment['delivered_at'])) {
                $allDelivered = false;
            }
            $number = trim((string)($shipment['number'] ?? $shipment['tracking_number'] ?? ''));
            if ($primary === null && $number !== '') {
                $carrier = $shipment['carrier'] ?? '';
                if (is_array($carrier)) {
                    $carrier = $carrier['code'] ?? $carrier['name'] ?? '';
                }
                $primary = [
                    'carrier' => $this->normalizeCarrier((string)$carrier),
                    'tracking_number' => mb_substr($number, 0, 255),
                    'shipped_at' => $this->normalizePrintifyDate($shipment['shipped_at'] ?? null),
                ];
            }
        }

        return array_merge($primary ?? [], [
            'has_shipments' => true,
            'all_delivered' => $allDelivered,
        ]);
    }

    private function mapPrintifyOrderStatus(string $printifyStatus, array $shipment): ?string
    {
        if (!empty($shipment['has_shipments']) && !empty($shipment['all_delivered'])) {
            return 'delivered';
        }
        if (!empty($shipment['has_shipments'])) {
            return 'shipped';
        }

        return match ($printifyStatus) {
            'sending-to-production', 'in-production' => 'processing',
            'fulfilled' => 'shipped',
            'canceled', 'cancelled' => 'cancelled',
            default => null,
        };
    }

    private function forwardOrderStatus(string $currentStatus, ?string $targetStatus): ?string
    {
        if ($targetStatus === null || in_array($currentStatus, ['delivered', 'cancelled', 'refunded'], true)) {
            return null;
        }
        if ($targetStatus === 'cancelled') {
            return 'cancelled';
        }

        $rank = ['pending' => 0, 'processing' => 1, 'shipped' => 2, 'delivered' => 3];
        if (!isset($rank[$currentStatus], $rank[$targetStatus]) || $rank[$targetStatus] <= $rank[$currentStatus]) {
            return null;
        }
        return $targetStatus;
    }

    private function normalizeCarrier(string $carrier): string
    {
        $normalized = strtolower(trim($carrier));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        return match ($normalized) {
            'usps', 'united_states_postal_service' => 'usps',
            'ups', 'united_parcel_service' => 'ups',
            'fedex', 'federal_express' => 'fedex',
            'dhl' => 'dhl',
            'dhl_express' => 'dhl_express',
            'amazon', 'amazon_logistics' => 'amazon',
            'ontrac' => 'ontrac',
            'lasership' => 'lasership',
            'an_post' => 'an_post',
            'royal_mail' => 'royal_mail',
            'canada_post' => 'canada_post',
            'australia_post', 'australian_post' => 'australia_post',
            default => 'other',
        };
    }

    private function normalizePrintifyDate(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }


    public function sendOrderToPrintify(int $orderId): array
    {
        $order = $this->getOrderForPrintify($orderId);
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }
        $items = $this->getPrintifyOrderItems($orderId);
        if (empty($items)) {
            return ['success' => true, 'message' => 'Order has no Printify-mapped items'];
        }
        $customizedItems = array_values(array_filter($items, fn($item) => !empty($this->orderItemCustomizations($item))));
        if (!empty($customizedItems)) {
            $this->saveOrderMapping($orderId, null, 'awaiting_personalization', 'Order contains customer personalization and must be prepared manually before Printify submission');
            $this->log('hold_personalized_order', 'warning', 'Order held for manual personalization before Printify submission', [
                'order_id' => $orderId,
                'items' => array_map(fn($item) => [
                    'order_item_id' => (int)$item['id'],
                    'product_id' => (int)$item['product_id'],
                    'variant_id' => isset($item['variant_id']) ? (int)$item['variant_id'] : null,
                    'customizations' => $this->orderItemCustomizations($item),
                ], $customizedItems),
            ]);
            return [
                'success' => false,
                'error' => 'Order contains customer personalization and was held for manual Printify preparation',
                'status' => 'awaiting_personalization',
            ];
        }

        $payload = $this->buildOrderPayload($order, $items);
        $response = $this->apiRequest('POST', '/shops/' . urlencode((string)$this->settings['shop_id']) . '/orders.json', $payload);
        if (!$response['success']) {
            $this->saveOrderMapping($orderId, null, 'error', $response['error'] ?? 'Printify order submission failed');
            return $response;
        }
        $printifyOrderId = $response['data']['id'] ?? null;
        $this->saveOrderMapping($orderId, $printifyOrderId, 'submitted', null);
        return ['success' => true, 'printify_order_id' => $printifyOrderId];
    }

    public function handleAdminAction(string $action, array $post): array
    {
        if ($action === 'import_printify_products') {
            $ids = $post['printify_product_ids'] ?? [];
            return $this->importPrintifyProducts(is_array($ids) ? $ids : []);
        }

        if (str_starts_with($action, 'resync_printify_product:')) {
            $printifyProductId = substr($action, strlen('resync_printify_product:'));
            if (!preg_match('/^[A-Za-z0-9_-]+$/', $printifyProductId)) {
                return ['success' => false, 'error' => 'Invalid Printify product ID'];
            }
            $result = $this->importPrintifyProducts([$printifyProductId]);
            if (!empty($result['success'])) {
                $result['message'] = 'Printify product resynced to Apparix';
            }
            return $result;
        }

        return ['success' => false, 'error' => 'Unknown Printify action'];
    }

    public function getPrintifyProducts(int $page = 1): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Printify Sync is not configured'];
        }
        $page = max(1, $page);
        return $this->apiRequest('GET', '/shops/' . urlencode((string)$this->settings['shop_id']) . '/products.json', [], ['page' => $page]);
    }

    public function searchPrintifyProducts(string $query): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Printify Sync is not configured'];
        }

        $query = mb_strtolower(trim($query));
        if ($query === '') {
            return $this->getPrintifyProducts(1);
        }

        $matches = [];
        $lastPage = 1;
        $maxPages = 40;

        for ($page = 1; $page <= $lastPage && $page <= $maxPages; $page++) {
            $result = $this->getPrintifyProducts($page);
            if (!$result['success']) {
                return $result;
            }

            $data = is_array($result['data'] ?? null) ? $result['data'] : [];
            $products = $data['data'] ?? $data;
            if (!is_array($products)) {
                $products = [];
            }

            $lastPage = max($lastPage, (int)($data['last_page'] ?? $page));
            foreach ($products as $product) {
                if (is_array($product) && $this->printifyProductMatchesSearch($product, $query)) {
                    $matches[] = $product;
                }
            }
        }

        return [
            'success' => true,
            'data' => [
                'data' => $matches,
                'current_page' => 1,
                'last_page' => 1,
                'from' => empty($matches) ? 0 : 1,
                'to' => count($matches),
                'total' => count($matches),
                'search' => $query,
            ],
        ];
    }

    private function printifyProductMatchesSearch(array $product, string $query): bool
    {
        $haystack = [
            (string)($product['id'] ?? ''),
            (string)($product['title'] ?? ''),
            (string)($product['description'] ?? ''),
        ];

        foreach (($product['variants'] ?? []) as $variant) {
            if (is_array($variant)) {
                $haystack[] = (string)($variant['sku'] ?? '');
                $haystack[] = (string)($variant['title'] ?? '');
            }
        }

        foreach (($product['options'] ?? []) as $option) {
            if (!is_array($option)) {
                continue;
            }
            $haystack[] = (string)($option['name'] ?? '');
            foreach (($option['values'] ?? []) as $value) {
                if (is_array($value)) {
                    $haystack[] = (string)($value['title'] ?? $value['name'] ?? '');
                }
            }
        }

        return str_contains(mb_strtolower(implode(' ', $haystack)), $query);
    }

    public function importPrintifyProducts(array $printifyProductIds): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Printify Sync is not configured'];
        }

        $ids = array_values(array_unique(array_filter(
            array_map('strval', $printifyProductIds),
            fn($id) => preg_match('/^[A-Za-z0-9_-]+$/', $id)
        )));
        if (empty($ids)) {
            return ['success' => false, 'error' => 'Select at least one Printify product to import'];
        }

        $summary = ['success' => true, 'synced' => 0, 'imported' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];
        foreach ($ids as $printifyProductId) {
            $response = $this->apiRequest('GET', '/shops/' . urlencode((string)$this->settings['shop_id']) . '/products/' . urlencode($printifyProductId) . '.json');
            if (!$response['success']) {
                $summary['failed']++;
                $summary['errors'][] = $printifyProductId . ': ' . ($response['error'] ?? 'Unable to fetch product');
                continue;
            }

            try {
                $result = $this->importPrintifyProduct($response['data']);
            } catch (\Throwable $e) {
                $this->log('import_product', 'error', $e->getMessage(), ['printify_product_id' => $printifyProductId]);
                $result = ['success' => false, 'error' => 'Import failed: ' . $e->getMessage()];
            }
            if ($result['success']) {
                $summary['synced']++;
                if (!empty($result['updated'])) {
                    $summary['updated']++;
                } else {
                    $summary['imported']++;
                }
            } else {
                $summary['failed']++;
                $summary['errors'][] = $printifyProductId . ': ' . ($result['error'] ?? 'Import failed');
            }
        }

        if ($summary['failed'] > 0 && $summary['synced'] === 0) {
            $summary['success'] = false;
            $summary['error'] = implode('; ', array_slice($summary['errors'], 0, 3));
        } else {
            $summary['message'] = $summary['synced'] . ' Printify product(s) synced'
                . ($summary['updated'] ? ' (' . $summary['updated'] . ' updated)' : '')
                . ($summary['imported'] ? ' (' . $summary['imported'] . ' new)' : '')
                . ($summary['failed'] ? ', ' . $summary['failed'] . ' failed' : '');
        }

        return $summary;
    }

    private function importPrintifyProduct(array $printifyProduct): array
    {
        $printifyProductId = (string)($printifyProduct['id'] ?? '');
        $title = trim((string)($printifyProduct['title'] ?? ''));
        if ($printifyProductId === '' || $title === '') {
            return ['success' => false, 'error' => 'Printify product is missing an ID or title'];
        }

        $db = Database::getInstance();
        $startedTransaction = !$db->inTransaction();
        if ($startedTransaction) {
            $db->beginTransaction();
        }

        try {
            $existing = $db->selectOne(
                "SELECT product_id FROM printify_product_sync WHERE printify_product_id = ? ORDER BY variant_id ASC LIMIT 1",
                [$printifyProductId]
            );
            $variants = $this->selectedPrintifyVariants($printifyProduct['variants'] ?? []);
            if (empty($variants)) {
                return ['success' => false, 'error' => 'Printify product has no selected variants'];
            }
            $firstVariant = $this->defaultPrintifyVariant($variants);
            $price = isset($firstVariant['price']) ? ((float)$firstVariant['price'] / 100) : 0.00;
            $sku = (string)($firstVariant['sku'] ?? ('PFY-' . $printifyProductId));
            $priceMin = $this->minVariantPrice($variants);
            $priceMax = $this->maxVariantPrice($variants);
            $inventory = $this->totalVariantInventory($variants);
            $cost = $this->minVariantCost($variants);
            $description = (string)($printifyProduct['description'] ?? '');
            $metaKeywords = $this->printifyKeywords($printifyProduct);
            $metaDescription = $this->printifyMetaDescription($description);

            if ($existing && !$db->selectOne("SELECT id FROM products WHERE id = ?", [(int)$existing['product_id']])) {
                $db->query("DELETE FROM printify_product_sync WHERE printify_product_id = ?", [$printifyProductId]);
                $existing = false;
            }

            if ($existing) {
                $productId = (int)$existing['product_id'];
                $db->update(
                    "UPDATE products SET name = ?, description = ?, meta_keywords = ?, meta_description = ?, price = ?, price_min = ?, price_max = ?, cost = ?, inventory_count = ?, sku = ?, updated_at = NOW() WHERE id = ?",
                    [$title, $description, $metaKeywords, $metaDescription, $price, $priceMin, $priceMax, $cost, $inventory, $sku, $productId]
                );
                $this->clearImportedProductData($productId);
            } else {
                $productId = (int)$db->insert(
                    "INSERT INTO products (name, slug, description, meta_keywords, meta_description, price, price_min, price_max, cost, inventory_count, sku, is_active, disabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0)",
                    [$title, $this->uniqueProductSlug($title), $description, $metaKeywords, $metaDescription, $price, $priceMin, $priceMax, $cost, $inventory, $sku]
                );
            }

            $usedOptionValueIds = $this->usedPrintifyOptionValueIds($variants);
            $optionValueMap = $this->importPrintifyOptions($productId, $printifyProduct['options'] ?? [], $usedOptionValueIds);
            $variantMap = [];

            if (empty($variants)) {
                $this->saveImportedProductMapping($productId, 0, $printifyProductId, null, $sku);
            } else {
                foreach ($variants as $variant) {
                    $printifyVariantId = (int)($variant['id'] ?? 0);
                    if ($printifyVariantId <= 0) {
                        continue;
                    }
                    $variantSku = (string)($variant['sku'] ?? ($sku . '-' . $printifyVariantId));
                    $variantPrice = isset($variant['price']) ? ((float)$variant['price'] / 100) : $price;
                    $variantId = (int)$db->insert(
                        "INSERT INTO product_variants (product_id, sku, price_adjustment, cost, inventory_count, is_active, weight_oz) VALUES (?, ?, ?, ?, ?, ?, ?)",
                        [
                            $productId,
                            $variantSku,
                            round($variantPrice - $price, 2),
                            isset($variant['cost']) ? ((float)$variant['cost'] / 100) : null,
                            $this->printifyVariantInventory($variant),
                            $this->printifyVariantIsActive($variant) ? 1 : 0,
                            isset($variant['grams']) ? round(((float)$variant['grams']) * 0.035274, 2) : null,
                        ]
                    );
                    $variantMap[$printifyVariantId] = $variantId;
                    foreach (($variant['options'] ?? []) as $printifyOptionValueId) {
                        $optionValueId = $optionValueMap[(string)$printifyOptionValueId] ?? null;
                        if ($optionValueId) {
                            $db->query("INSERT IGNORE INTO variant_option_values (variant_id, option_value_id) VALUES (?, ?)", [$variantId, $optionValueId]);
                        }
                    }
                    $this->saveImportedProductMapping($productId, $variantId, $printifyProductId, $printifyVariantId, $variantSku);
                }
            }

            $this->importPrintifyImages($productId, $printifyProduct['images'] ?? [], $variants, $optionValueMap, $title);

            if ($startedTransaction) {
                $db->commit();
            }
            return ['success' => true, 'product_id' => $productId, 'updated' => (bool)$existing];
        } catch (\Throwable $e) {
            if ($startedTransaction) {
                $db->rollback();
            }
            throw $e;
        }
    }

    private function printifyKeywords(array $printifyProduct): ?string
    {
        $keywords = [];
        foreach (($printifyProduct['tags'] ?? []) as $tag) {
            $keyword = $this->cleanKeyword($tag);
            if ($keyword !== '') {
                $keywords[mb_strtolower($keyword)] = $keyword;
            }
        }

        $joined = '';
        foreach ($keywords as $keyword) {
            $candidate = $joined === '' ? $keyword : $joined . ', ' . $keyword;
            if (mb_strlen($candidate) > 500) {
                break;
            }
            $joined = $candidate;
        }

        return $joined !== '' ? $joined : null;
    }

    private function printifyMetaDescription(string $description): ?string
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($description)) ?: '');
        if ($clean === '') {
            return null;
        }
        return mb_substr($clean, 0, 320);
    }

    private function cleanKeyword(mixed $value): string
    {
        if (is_array($value)) {
            $value = $value['title'] ?? $value['name'] ?? $value['tag'] ?? '';
        }
        $keyword = trim(preg_replace('/\s+/', ' ', strip_tags((string)$value)) ?: '');
        $keyword = trim(str_replace([',', "\r", "\n", "\t"], ' ', $keyword));
        return mb_substr($keyword, 0, 60);
    }

    private function productTagsForPrintify(array $product): array
    {
        $tags = [];
        foreach (explode(',', (string)($product['meta_keywords'] ?? '')) as $keyword) {
            $tag = $this->cleanKeyword($keyword);
            if ($tag !== '') {
                $tags[mb_strtolower($tag)] = $tag;
            }
        }
        return array_slice(array_values($tags), 0, 15);
    }

    private function clearImportedProductData(int $productId): void
    {
        $db = Database::getInstance();
        $db->query("DELETE FROM product_images WHERE product_id = ?", [$productId]);
        $db->query("DELETE FROM product_variants WHERE product_id = ?", [$productId]);
        $db->query("DELETE FROM product_options WHERE product_id = ?", [$productId]);
        $db->query("DELETE FROM printify_product_sync WHERE product_id = ?", [$productId]);
    }

    private function importPrintifyOptions(int $productId, array $options, array $usedOptionValueIds): array
    {
        $db = Database::getInstance();
        $map = [];
        foreach (array_values($options) as $optionIndex => $option) {
            $name = trim((string)($option['name'] ?? 'Option'));
            if ($name === '') {
                $name = 'Option';
            }
            $optionId = (int)$db->insert(
                "INSERT INTO product_options (product_id, option_name, sort_order) VALUES (?, ?, ?)",
                [$productId, mb_substr($name, 0, 100), $optionIndex]
            );
            foreach (array_values($option['values'] ?? []) as $valueIndex => $value) {
                $printifyValueId = (string)($value['id'] ?? '');
                $valueName = trim((string)($value['title'] ?? $value['name'] ?? ''));
                if ($printifyValueId === '' || $valueName === '' || !isset($usedOptionValueIds[$printifyValueId])) {
                    continue;
                }
                $valueId = (int)$db->insert(
                    "INSERT INTO product_option_values (option_id, value_name, sort_order) VALUES (?, ?, ?)",
                    [$optionId, mb_substr($valueName, 0, 100), $valueIndex]
                );
                $map[$printifyValueId] = $valueId;
            }
        }
        return $map;
    }

    private function importPrintifyImages(int $productId, array $images, array $variants, array $optionValueMap, string $title): void
    {
        $db = Database::getInstance();
        $variantOptions = [];
        foreach ($variants as $variant) {
            $variantId = (int)($variant['id'] ?? 0);
            if ($variantId > 0) {
                $variantOptions[$variantId] = array_map('strval', $variant['options'] ?? []);
            }
        }

        $seen = [];
        $sortOrder = 0;
        foreach ($images as $image) {
            $src = $image['src'] ?? null;
            if (!is_string($src) || !preg_match('#^https?://#i', $src) || strlen($src) > 255 || isset($seen[$src])) {
                continue;
            }
            if (isset($image['is_selected_for_publishing']) && !$image['is_selected_for_publishing']) {
                continue;
            }
            $imageVariantIds = array_map('intval', $image['variant_ids'] ?? []);
            if (!empty($imageVariantIds) && !array_intersect($imageVariantIds, array_keys($variantOptions))) {
                continue;
            }
            $seen[$src] = true;
            $linkedOptionIds = [];
            $printifyImageValueIds = $this->printifyOptionValueIdsForImage($image, $imageVariantIds, $variantOptions);
            foreach ($printifyImageValueIds as $printifyValueId) {
                if (isset($optionValueMap[$printifyValueId])) {
                    $linkedOptionIds[(int)$optionValueMap[$printifyValueId]] = true;
                }
            }
            $primaryLinkedOptionId = $linkedOptionIds ? (int)array_key_first($linkedOptionIds) : null;
            $imageId = (int)$db->insert(
                "INSERT INTO product_images (product_id, image_path, option_value_id, alt_text, is_primary, sort_order) VALUES (?, ?, ?, ?, ?, ?)",
                [$productId, $src, $primaryLinkedOptionId, mb_substr($title, 0, 255), $sortOrder === 0 ? 1 : 0, $sortOrder]
            );
            foreach (array_keys($linkedOptionIds) as $optionValueId) {
                $db->query("INSERT IGNORE INTO product_image_option_values (image_id, option_value_id) VALUES (?, ?)", [$imageId, (int)$optionValueId]);
            }
            $sortOrder++;
        }
    }

    private function printifyOptionValueIdsForImage(array $image, array $imageVariantIds, array $variantOptions): array
    {
        $mockupVariantId = $this->printifyVariantIdFromMockupId((string)($image['mockup_id'] ?? ''));
        if ($mockupVariantId > 0 && isset($variantOptions[$mockupVariantId])) {
            return array_values(array_unique(array_map('strval', $variantOptions[$mockupVariantId])));
        }

        $optionSets = [];
        foreach ($imageVariantIds as $printifyVariantId) {
            if (!isset($variantOptions[$printifyVariantId])) {
                continue;
            }
            $optionSets[] = array_values(array_unique(array_map('strval', $variantOptions[$printifyVariantId])));
        }

        if (empty($optionSets)) {
            return [];
        }

        $common = array_shift($optionSets);
        foreach ($optionSets as $optionSet) {
            $common = array_values(array_intersect($common, $optionSet));
        }

        return $common;
    }

    private function printifyVariantIdFromMockupId(string $mockupId): int
    {
        if (preg_match('/^[A-Za-z0-9]+_(\d+)_/', $mockupId, $matches)) {
            return (int)$matches[1];
        }
        return 0;
    }
    private function selectedPrintifyVariants(array $variants): array
    {
        return array_values(array_filter($variants, fn($variant) => !empty($variant['is_enabled'])));
    }

    private function usedPrintifyOptionValueIds(array $variants): array
    {
        $used = [];
        foreach ($variants as $variant) {
            foreach (($variant['options'] ?? []) as $printifyOptionValueId) {
                $used[(string)$printifyOptionValueId] = true;
            }
        }
        return $used;
    }
    private function defaultPrintifyVariant(array $variants): array
    {
        foreach ($variants as $variant) {
            if (!empty($variant['is_default'])) {
                return $variant;
            }
        }
        return $variants[0] ?? [];
    }

    private function minVariantPrice(array $variants): float
    {
        $prices = array_values(array_filter(array_map(fn($variant) => isset($variant['price']) ? ((float)$variant['price'] / 100) : null, $variants), fn($price) => $price !== null));
        return $prices ? min($prices) : 0.00;
    }

    private function maxVariantPrice(array $variants): float
    {
        $prices = array_values(array_filter(array_map(fn($variant) => isset($variant['price']) ? ((float)$variant['price'] / 100) : null, $variants), fn($price) => $price !== null));
        return $prices ? max($prices) : 0.00;
    }

    private function minVariantCost(array $variants): ?float
    {
        $costs = array_values(array_filter(array_map(fn($variant) => isset($variant['cost']) ? ((float)$variant['cost'] / 100) : null, $variants), fn($cost) => $cost !== null));
        return $costs ? min($costs) : null;
    }
    private function totalVariantInventory(array $variants): int
    {
        return array_sum(array_map(fn($variant) => $this->printifyVariantInventory($variant), $variants));
    }

    private function printifyVariantInventory(array $variant): int
    {
        if (!$this->printifyVariantIsActive($variant)) {
            return 0;
        }

        $quantity = isset($variant['quantity']) ? (int)$variant['quantity'] : 0;
        return max($quantity, self::DEFAULT_AVAILABLE_STOCK);
    }

    private function printifyVariantIsActive(array $variant): bool
    {
        $enabled = !isset($variant['is_enabled']) || !empty($variant['is_enabled']);
        $available = !isset($variant['is_available']) || !empty($variant['is_available']);
        return $enabled && $available;
    }
    private function uniqueProductSlug(string $title): string
    {
        $base = function_exists('slug') ? slug($title) : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        $base = $base ?: 'printify-product';
        $slug = $base;
        $i = 2;
        $db = Database::getInstance();
        while ($db->selectOne("SELECT id FROM products WHERE slug = ?", [$slug])) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
    private function saveImportedProductMapping(int $productId, int $variantId, string $printifyProductId, ?int $printifyVariantId, ?string $sku): void
    {
        Database::getInstance()->query(
            "INSERT INTO printify_product_sync (product_id, variant_id, printify_product_id, printify_variant_id, printify_sku, last_synced_at, sync_status, error_message)
             VALUES (?, ?, ?, ?, ?, NOW(), 'synced', NULL)
             ON DUPLICATE KEY UPDATE
                printify_product_id = VALUES(printify_product_id),
                printify_variant_id = VALUES(printify_variant_id),
                printify_sku = VALUES(printify_sku),
                last_synced_at = VALUES(last_synced_at),
                sync_status = 'synced',
                error_message = NULL",
            [$productId, $variantId, $printifyProductId, $printifyVariantId, $sku]
        );
    }
    private function buildProductPayload(array $product): array
    {
        $blueprintId = (int)($this->settings['default_blueprint_id'] ?? 0);
        $printProviderId = (int)($this->settings['default_print_provider_id'] ?? 0);
        $defaultVariantId = (int)($this->settings['default_variant_id'] ?? 0);
        if ($blueprintId <= 0 || $printProviderId <= 0 || $defaultVariantId <= 0) {
            return ['success' => false, 'error' => 'Default blueprint, print provider, and variant IDs are required before syncing products'];
        }
        $imageUrl = $this->absoluteAssetUrl($product['primary_image'] ?? '');
        if (!$imageUrl) {
            return ['success' => false, 'error' => 'Product needs a primary image before it can sync to Printify'];
        }
        $basePrice = (float)($product['sale_price'] ?: $product['price']);
        $syncVariants = $this->buildVariantPayloads($product, $defaultVariantId, $basePrice);
        if (empty($syncVariants)) {
            return ['success' => false, 'error' => 'No active variants are mapped to Printify variant IDs'];
        }
        $printifyVariantIds = array_map(fn($variant) => (int)$variant['id'], $syncVariants);
        return [
            'success' => true,
            'sync_variants' => $syncVariants,
            'data' => [
                'title' => $product['name'],
                'description' => strip_tags((string)($product['description'] ?? '')),
                'tags' => $this->productTagsForPrintify($product),
                'blueprint_id' => $blueprintId,
                'print_provider_id' => $printProviderId,
                'variants' => array_map(fn($variant) => [
                    'id' => (int)$variant['id'],
                    'price' => (int)$variant['price'],
                    'is_enabled' => true,
                    'sku' => $variant['sku'],
                ], $syncVariants),
                'print_areas' => [[
                    'variant_ids' => $printifyVariantIds,
                    'placeholders' => [[
                        'position' => 'front',
                        'images' => [[
                            'src' => $imageUrl,
                            'x' => 0.5,
                            'y' => 0.5,
                            'scale' => 1,
                            'angle' => 0,
                        ]],
                    ]],
                ]],
            ],
        ];
    }

    private function buildVariantPayloads(array $product, int $defaultVariantId, float $basePrice): array
    {
        $variants = $this->getActiveVariants((int)$product['id']);
        if (empty($variants)) {
            return [[
                'variant_id' => 0,
                'id' => $defaultVariantId,
                'price' => (int)round($basePrice * 100),
                'sku' => $product['sku'] ?: 'APX-' . $product['id'],
            ]];
        }
        $variantMap = $this->settings['variant_map'][(string)$product['id']] ?? [];
        $payloads = [];
        foreach ($variants as $variant) {
            $apparixVariantId = (int)$variant['id'];
            $printifyVariantId = (int)($variantMap[(string)$apparixVariantId] ?? 0);
            if ($printifyVariantId <= 0) {
                continue;
            }
            $variantPrice = $variant['price'] !== null && $variant['price'] !== '' ? (float)$variant['price'] : $basePrice + (float)($variant['price_adjustment'] ?? 0);
            $payloads[] = [
                'variant_id' => $apparixVariantId,
                'id' => $printifyVariantId,
                'price' => (int)round($variantPrice * 100),
                'sku' => $variant['sku'] ?: ('APX-' . $product['id'] . '-' . $apparixVariantId),
            ];
        }
        return $payloads;
    }

    private function buildOrderPayload(array $order, array $items): array
    {
        return [
            'external_id' => (string)$order['order_number'],
            'label' => 'Apparix ' . $order['order_number'],
            'line_items' => array_map(fn($item) => $this->buildOrderLineItem($order, $item), $items),
            'shipping_method' => $this->printifyOrderShippingMethod($order),
            'send_shipping_notification' => true,
            'address_to' => [
                'first_name' => (string)($order['shipping_first_name'] ?: $order['billing_first_name'] ?: 'Customer'),
                'last_name' => (string)($order['shipping_last_name'] ?: $order['billing_last_name'] ?: ''),
                'email' => (string)$order['customer_email'],
                'phone' => (string)($order['shipping_phone'] ?: $order['billing_phone'] ?: ''),
                'country' => (string)($order['shipping_country'] ?: $order['billing_country'] ?: 'US'),
                'region' => (string)($order['shipping_state'] ?: $order['billing_state'] ?: ''),
                'address1' => (string)($order['shipping_address1'] ?: $order['billing_address1'] ?: ''),
                'address2' => (string)($order['shipping_address2'] ?: $order['billing_address2'] ?: ''),
                'city' => (string)($order['shipping_city'] ?: $order['billing_city'] ?: ''),
                'zip' => (string)($order['shipping_postal'] ?: $order['billing_postal'] ?: ''),
            ],
        ];
    }

    private function printifyOrderShippingMethod(array $order): int
    {
        $name = strtolower((string)($order['shipping_method'] ?? ''));
        if (str_contains($name, 'express')) {
            return 3;
        }
        if (str_contains($name, 'priority')) {
            return 2;
        }
        if (str_contains($name, 'economy')) {
            return 4;
        }
        return 1;
    }

    private function buildOrderLineItem(array $order, array $item): array
    {
        $externalIdParts = [
            (string)$order['order_number'],
            'item',
            (string)$item['id'],
        ];
        $customizations = $this->orderItemCustomizations($item);
        if (!empty($customizations)) {
            $externalIdParts[] = substr(hash('sha256', json_encode($customizations, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)), 0, 12);
        }

        return [
            'external_id' => implode('-', $externalIdParts),
            'product_id' => $item['printify_product_id'],
            'variant_id' => (int)$item['printify_variant_id'],
            'quantity' => (int)$item['quantity'],
        ];
    }

    private function orderItemCustomizations(array $item): array
    {
        $decoded = json_decode((string)($item['customizations'] ?? ''), true);
        if (!is_array($decoded)) {
            return [];
        }

        $customizations = [];
        foreach ($decoded as $customization) {
            if (!is_array($customization)) {
                continue;
            }
            $key = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($customization['key'] ?? ''));
            $value = trim((string)($customization['value'] ?? ''));
            if ($key === '' || $value === '') {
                continue;
            }
            $customizations[] = [
                'key' => mb_substr($key, 0, 64),
                'label' => mb_substr(trim((string)($customization['label'] ?? $key)), 0, 120),
                'value' => mb_substr($value, 0, 500),
                'printify_position' => mb_substr(trim((string)($customization['printify_position'] ?? '')), 0, 64),
            ];
        }

        return $customizations;
    }

    private function publishProduct(string $printifyProductId): void
    {
        $this->apiRequest('POST', '/shops/' . urlencode((string)$this->settings['shop_id']) . '/products/' . urlencode($printifyProductId) . '/publish.json', [
            'title' => true,
            'description' => true,
            'images' => true,
            'variants' => true,
            'tags' => true,
            'keyFeatures' => true,
            'shipping_template' => true,
        ]);
    }

    private function apiRequest(string $method, string $path, array $data = [], array $query = []): array
    {
        $this->enforceRateLimit();
        $url = self::API_BASE . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        $ch = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . ($this->settings['api_token'] ?? ''),
            'Accept: application/json',
            'User-Agent: ' . self::USER_AGENT,
        ];
        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $headers[] = 'Content-Type: application/json;charset=utf-8';
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);
        if ($curlErrno !== 0) {
            $this->log('api_request', 'error', $curlError, ['path' => $path, 'curl_errno' => $curlErrno]);
            return ['success' => false, 'error' => $curlError];
        }
        $decoded = json_decode((string)$response, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $error = $decoded['error'] ?? $decoded['message'] ?? 'Printify API request failed with HTTP ' . $httpCode;
            $this->log('api_request', 'error', $error, ['path' => $path, 'http_code' => $httpCode]);
            return [
                'success' => false,
                'error' => $error,
                'http_code' => $httpCode,
                'validation' => $decoded['errors'] ?? null,
            ];
        }
        return ['success' => true, 'data' => $decoded];
    }

    private function enforceRateLimit(): void
    {
        $now = time();
        if ($this->windowStart !== $now) {
            $this->windowStart = $now;
            $this->requestCount = 0;
        }
        $this->requestCount++;
        if ($this->requestCount > 8) {
            usleep(150000);
        }
    }

    private function createSyncTables(): void
    {
        $db = Database::getInstance();
        $db->query("CREATE TABLE IF NOT EXISTS printify_product_sync (
            id INT PRIMARY KEY AUTO_INCREMENT,
            product_id INT NOT NULL,
            variant_id INT NOT NULL DEFAULT 0,
            printify_product_id VARCHAR(64),
            printify_variant_id INT DEFAULT NULL,
            printify_sku VARCHAR(100),
            last_synced_at TIMESTAMP NULL,
            sync_status ENUM('pending', 'synced', 'error') DEFAULT 'pending',
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_product_variant (product_id, variant_id),
            INDEX idx_printify_product (printify_product_id),
            INDEX idx_status (sync_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("CREATE TABLE IF NOT EXISTS printify_order_sync (
            id INT PRIMARY KEY AUTO_INCREMENT,
            order_id INT NOT NULL,
            printify_order_id VARCHAR(64),
            status ENUM('pending', 'awaiting_personalization', 'submitted', 'error') DEFAULT 'pending',
            error_message TEXT,
            submitted_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_order (order_id),
            INDEX idx_printify_order (printify_order_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->query("ALTER TABLE printify_order_sync MODIFY status ENUM('pending', 'awaiting_personalization', 'submitted', 'error') DEFAULT 'pending'");
        $db->query("CREATE TABLE IF NOT EXISTS printify_sync_log (
            id INT PRIMARY KEY AUTO_INCREMENT,
            action VARCHAR(50) NOT NULL,
            status ENUM('success', 'error', 'warning') NOT NULL,
            message TEXT,
            details JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_action (action),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function loadSettings(): void
    {
        $db = Database::getInstance();
        $result = $db->selectOne("SELECT settings FROM plugins WHERE slug = ?", [$this->getSlug()]);
        $saved = [];
        if ($result && !empty($result['settings'])) {
            $saved = json_decode($result['settings'], true) ?: [];
        }
        $this->settings = array_merge($this->getDefaultSettings(), $saved);
        $this->settings['sync_products'] = array_values(array_filter(array_map('intval', $this->settings['sync_products'] ?? [])));
        $this->settings['variant_map'] = is_array($this->settings['variant_map'] ?? null) ? $this->settings['variant_map'] : [];
        $interval = (int)($this->settings['inventory_sync_interval_hours'] ?? 1);
        $this->settings['inventory_sync_interval_hours'] = in_array($interval, [1, 6, 24], true) ? $interval : 1;
    }

    private function isConfiguredForToken(): bool
    {
        return !empty($this->settings['api_token']);
    }

    private function getSelectableProducts(): array
    {
        return Database::getInstance()->select("SELECT p.id, p.name, p.sku, p.price, p.sale_price, p.is_active,
            ps.printify_product_id, ps.sync_status, ps.last_synced_at, ps.error_message
            FROM products p
            LEFT JOIN printify_product_sync ps ON ps.product_id = p.id AND ps.variant_id = 0
            WHERE p.disabled = 0
            ORDER BY p.name ASC");
    }

    public function getActiveVariants(int $productId): array
    {
        return Database::getInstance()->select("SELECT id, name, sku, price, price_adjustment, inventory_count, is_active
            FROM product_variants
            WHERE product_id = ? AND is_active = 1
            ORDER BY sort_order ASC, id ASC", [$productId]);
    }

    private function getProductForSync(int $productId): ?array
    {
        return Database::getInstance()->selectOne("SELECT p.*, pi.image_path AS primary_image
            FROM products p
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
            WHERE p.id = ?", [$productId]);
    }

    private function getProductMapping(int $productId, int $variantId): ?array
    {
        return Database::getInstance()->selectOne("SELECT * FROM printify_product_sync WHERE product_id = ? AND variant_id = ?", [$productId, $variantId]);
    }

    private function getOrderForPrintify(int $orderId): ?array
    {
        return Database::getInstance()->selectOne("SELECT o.*, ba.first_name AS billing_first_name, ba.last_name AS billing_last_name,
            ba.address_line1 AS billing_address1, ba.address_line2 AS billing_address2, ba.city AS billing_city,
            ba.state AS billing_state, ba.postal_code AS billing_postal, ba.country AS billing_country, ba.phone AS billing_phone,
            sa.first_name AS shipping_first_name, sa.last_name AS shipping_last_name, sa.address_line1 AS shipping_address1,
            sa.address_line2 AS shipping_address2, sa.city AS shipping_city, sa.state AS shipping_state,
            sa.postal_code AS shipping_postal, sa.country AS shipping_country, sa.phone AS shipping_phone
            FROM orders o
            LEFT JOIN addresses ba ON o.billing_address_id = ba.id
            LEFT JOIN addresses sa ON o.shipping_address_id = sa.id
            WHERE o.id = ?", [$orderId]);
    }

    private function getPrintifyOrderItems(int $orderId): array
    {
        return Database::getInstance()->select("SELECT oi.*, ps.printify_product_id, ps.printify_variant_id
            FROM order_items oi
            JOIN printify_product_sync ps ON ps.product_id = oi.product_id AND ps.variant_id = COALESCE(oi.variant_id, 0)
            WHERE oi.order_id = ? AND ps.sync_status = 'synced' AND ps.printify_product_id IS NOT NULL AND ps.printify_variant_id IS NOT NULL", [$orderId]);
    }

    private function saveProductMapping(int $productId, int $variantId, ?string $printifyProductId, ?string $sku, string $status, ?string $error): void
    {
        $printifyVariantId = $variantId > 0 ? (int)(($this->settings['variant_map'][(string)$productId][(string)$variantId] ?? 0)) : (int)($this->settings['default_variant_id'] ?? 0);
        Database::getInstance()->query("INSERT INTO printify_product_sync
            (product_id, variant_id, printify_product_id, printify_variant_id, printify_sku, last_synced_at, sync_status, error_message)
            VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
            ON DUPLICATE KEY UPDATE printify_product_id = VALUES(printify_product_id), printify_variant_id = VALUES(printify_variant_id),
            printify_sku = VALUES(printify_sku), last_synced_at = VALUES(last_synced_at), sync_status = VALUES(sync_status), error_message = VALUES(error_message)",
            [$productId, $variantId, $printifyProductId, $printifyVariantId ?: null, $sku, $status, $error]);
    }

    private function saveOrderMapping(int $orderId, ?string $printifyOrderId, string $status, ?string $error): void
    {
        Database::getInstance()->query("INSERT INTO printify_order_sync (order_id, printify_order_id, status, error_message, submitted_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE printify_order_id = VALUES(printify_order_id), status = VALUES(status), error_message = VALUES(error_message), submitted_at = VALUES(submitted_at)",
            [$orderId, $printifyOrderId, $status, $error]);
    }

    private function absoluteAssetUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return rtrim(appUrl(), '/') . '/' . ltrim($path, '/');
    }

    private function log(string $action, string $status, string $message, array $details = []): void
    {
        try {
            Database::getInstance()->query("INSERT INTO printify_sync_log (action, status, message, details) VALUES (?, ?, ?, ?)", [$action, $status, $message, json_encode($details)]);
        } catch (\Throwable $e) {
            error_log('Printify Sync log failed: ' . $e->getMessage());
        }
    }
}
