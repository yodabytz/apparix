<?php

namespace App\Plugins;

use App\Core\Plugins\PluginInterface;
use App\Core\Database;

/**
 * Amazon Sync Plugin
 *
 * Syncs products and orders with Amazon Seller Central using the Selling Partner API (SP-API).
 *
 * Authentication: Uses Login with Amazon (LWA) OAuth 2.0 tokens.
 * Note: As of October 2023, AWS Signature Version 4 is no longer required.
 *
 * @see https://developer-docs.amazon.com/sp-api/docs
 */
class AmazonSyncPlugin implements PluginInterface
{
    private array $settings = [];
    private ?string $accessToken = null;
    private ?int $tokenExpiry = null;

    /**
     * SP-API Regional Endpoints
     */
    private const ENDPOINTS = [
        'na' => 'https://sellingpartnerapi-na.amazon.com',
        'eu' => 'https://sellingpartnerapi-eu.amazon.com',
        'fe' => 'https://sellingpartnerapi-fe.amazon.com'
    ];

    /**
     * Marketplace ID to Region mapping
     * @see https://developer-docs.amazon.com/sp-api/docs/marketplace-ids
     */
    private const MARKETPLACE_REGIONS = [
        // North America
        'ATVPDKIKX0DER' => 'na',  // United States
        'A2EUQ1WTGCTBG2' => 'na', // Canada
        'A1AM78C64UM0Y8' => 'na', // Mexico
        'A2Q3Y263D00KWC' => 'na', // Brazil

        // Europe
        'A1F83G8C2ARO7P' => 'eu', // United Kingdom
        'A1PA6795UKMFR9' => 'eu', // Germany
        'A13V1IB3VIYBER' => 'eu', // France
        'APJ6JRA9NG5V4' => 'eu',  // Italy
        'A1RKKUPIHCS9HS' => 'eu', // Spain
        'A1805IZSGTT6HS' => 'eu', // Netherlands
        'A2NODRKZP88ZB9' => 'eu', // Sweden
        'A1C3SOZRARQ6R3' => 'eu', // Poland
        'ARBP9OOSHTCHU' => 'eu',  // Egypt
        'A33AVAJ2PDY3EV' => 'eu', // Turkey
        'A17E79C6D8DWNP' => 'eu', // Saudi Arabia
        'A2VIGQ35RCS4UG' => 'eu', // United Arab Emirates
        'A21TJRUUN4KGV' => 'eu',  // India
        'AE08WJ6YKNBMC' => 'eu',  // Belgium

        // Far East
        'A1VC38T7YXB528' => 'fe', // Japan
        'A39IBJ37TRP1C6' => 'fe', // Australia
        'A19VAU5U5O7RUS' => 'fe', // Singapore
    ];

    /**
     * Rate limit configuration (requests per second by API type)
     */
    private const RATE_LIMITS = [
        'orders' => 0.0167,      // 1 request per minute
        'listings' => 5,         // 5 requests per second
        'inventory' => 2,        // 2 requests per second
        'feeds' => 0.0083,       // 1 request per 2 minutes
        'default' => 1
    ];

    public function getSlug(): string { return 'amazon-sync'; }
    public function getName(): string { return 'Amazon Sync'; }
    public function getVersion(): string { return '1.1.0'; }
    public function getType(): string { return 'marketplace'; }
    public function getDescription(): string { return 'Sync products and orders with Amazon Seller Central via SP-API'; }
    public function getAuthor(): string { return 'Apparix'; }

    public function getDefaultSettings(): array
    {
        return [
            'marketplace_id' => 'ATVPDKIKX0DER',
            'sync_inventory' => true,
            'sync_orders' => true,
            'sync_interval' => 15
        ];
    }

    public function init(): void
    {
        $this->loadSettings();
    }

    public function onActivate(): void
    {
        $this->createSyncTables();
        $this->log('Plugin activated', 'success', ['version' => $this->getVersion()]);
    }

    public function onDeactivate(): void
    {
        $this->log('Plugin deactivated');
    }

    public function getSettingsView(): string
    {
        return file_get_contents(__DIR__ . '/views/settings.php');
    }

    public function getSettingsSchema(): array
    {
        $manifest = json_decode(file_get_contents(__DIR__ . '/plugin.json'), true);
        return $manifest['settings'] ?? [];
    }

    public function validateSettings(array $settings): array
    {
        $errors = [];

        if (empty($settings['seller_id'])) {
            $errors[] = 'Seller ID is required';
        }

        if (empty($settings['marketplace_id'])) {
            $errors[] = 'Marketplace ID is required';
        } elseif (!isset(self::MARKETPLACE_REGIONS[$settings['marketplace_id']])) {
            $errors[] = 'Invalid Marketplace ID';
        }

        if (empty($settings['lwa_client_id'])) {
            $errors[] = 'LWA Client ID is required';
        }

        if (empty($settings['lwa_client_secret'])) {
            $errors[] = 'LWA Client Secret is required';
        }

        if (empty($settings['refresh_token'])) {
            $errors[] = 'Refresh Token is required';
        }

        return $errors;
    }

    private function loadSettings(): void
    {
        $db = Database::getInstance();
        $result = $db->selectOne("SELECT settings FROM plugins WHERE slug = ?", [$this->getSlug()]);
        if ($result && !empty($result['settings'])) {
            $this->settings = json_decode($result['settings'], true) ?: [];
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->settings['seller_id'])
            && !empty($this->settings['marketplace_id'])
            && !empty($this->settings['lwa_client_id'])
            && !empty($this->settings['lwa_client_secret'])
            && !empty($this->settings['refresh_token']);
    }

    private function createSyncTables(): void
    {
        $db = Database::getInstance();

        $db->query("
            CREATE TABLE IF NOT EXISTS amazon_product_sync (
                id INT PRIMARY KEY AUTO_INCREMENT,
                product_id INT NOT NULL,
                variant_id INT DEFAULT NULL,
                amazon_asin VARCHAR(20),
                amazon_sku VARCHAR(100) NOT NULL,
                amazon_fnsku VARCHAR(20),
                fulfillment_channel ENUM('MFN', 'AFN') DEFAULT 'MFN',
                listing_status ENUM('active', 'inactive', 'incomplete', 'error') DEFAULT 'incomplete',
                last_synced_at TIMESTAMP NULL,
                last_inventory_sync TIMESTAMP NULL,
                sync_status ENUM('pending', 'synced', 'error') DEFAULT 'pending',
                error_message TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_product_variant (product_id, variant_id),
                UNIQUE KEY unique_sku (amazon_sku),
                INDEX idx_asin (amazon_asin),
                INDEX idx_status (sync_status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS amazon_order_sync (
                id INT PRIMARY KEY AUTO_INCREMENT,
                order_id INT,
                amazon_order_id VARCHAR(50) NOT NULL,
                amazon_order_status VARCHAR(50),
                fulfillment_channel VARCHAR(10),
                order_total DECIMAL(10,2),
                currency_code VARCHAR(3),
                purchase_date TIMESTAMP NULL,
                last_update_date TIMESTAMP NULL,
                imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_amazon_order (amazon_order_id),
                INDEX idx_order_id (order_id),
                INDEX idx_status (amazon_order_status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS amazon_sync_log (
                id INT PRIMARY KEY AUTO_INCREMENT,
                action VARCHAR(50) NOT NULL,
                status ENUM('success', 'error', 'warning') NOT NULL,
                message TEXT,
                request_id VARCHAR(100),
                details JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_action (action),
                INDEX idx_status (status),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /**
     * Get LWA Access Token
     * Implements token caching to avoid unnecessary requests
     */
    private function getAccessToken(): ?string
    {
        // Return cached token if still valid
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.amazon.com/auth/o2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->settings['refresh_token'],
                'client_id' => $this->settings['lwa_client_id'],
                'client_secret' => $this->settings['lwa_client_secret']
            ])
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $this->log('Token request failed: ' . $curlError, 'error');
            return null;
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200 || !isset($data['access_token'])) {
            $error = $data['error_description'] ?? $data['error'] ?? 'Unknown error';
            $this->log('Failed to get access token: ' . $error, 'error', [
                'http_code' => $httpCode,
                'response' => $data
            ]);
            return null;
        }

        $this->accessToken = $data['access_token'];
        // Cache token with 60 second buffer before expiry
        $this->tokenExpiry = time() + ($data['expires_in'] ?? 3600) - 60;

        return $this->accessToken;
    }

    /**
     * Get the regional endpoint for the configured marketplace
     */
    private function getEndpoint(): string
    {
        $marketplaceId = $this->settings['marketplace_id'] ?? 'ATVPDKIKX0DER';
        $region = self::MARKETPLACE_REGIONS[$marketplaceId] ?? 'na';
        return self::ENDPOINTS[$region];
    }

    /**
     * Make an API request to SP-API
     * Includes retry logic with exponential backoff for rate limiting
     */
    private function apiRequest(
        string $method,
        string $path,
        array $data = [],
        array $query = [],
        string $apiType = 'default',
        int $retryCount = 0
    ): array {
        $maxRetries = 3;

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to obtain access token'];
        }

        $url = $this->getEndpoint() . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'x-amz-access-token: ' . $accessToken,
            'x-amz-date: ' . gmdate('Ymd\THis\Z'),
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => true
        ]);

        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $this->log("API request failed: {$curlError}", 'error', [
                'method' => $method,
                'path' => $path
            ]);
            return ['success' => false, 'error' => 'Connection error: ' . $curlError];
        }

        // Parse headers and body
        $headerStr = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        $responseData = json_decode($body, true) ?: [];

        // Extract request ID for logging
        $requestId = null;
        if (preg_match('/x-amzn-requestid:\s*([^\r\n]+)/i', $headerStr, $matches)) {
            $requestId = trim($matches[1]);
        }

        // Handle rate limiting (429) with exponential backoff
        if ($httpCode === 429 && $retryCount < $maxRetries) {
            $waitTime = pow(2, $retryCount) * 1000000; // Exponential backoff in microseconds
            $this->log("Rate limited, retrying in " . ($waitTime / 1000000) . "s", 'warning', [
                'retry' => $retryCount + 1,
                'request_id' => $requestId
            ]);
            usleep($waitTime);
            return $this->apiRequest($method, $path, $data, $query, $apiType, $retryCount + 1);
        }

        // Handle errors
        if ($httpCode >= 400) {
            $errorMessage = $responseData['errors'][0]['message']
                ?? $responseData['message']
                ?? "HTTP {$httpCode} error";

            $this->log("API error: {$errorMessage}", 'error', [
                'http_code' => $httpCode,
                'request_id' => $requestId,
                'path' => $path,
                'errors' => $responseData['errors'] ?? null
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'http_code' => $httpCode,
                'request_id' => $requestId,
                'errors' => $responseData['errors'] ?? []
            ];
        }

        return [
            'success' => true,
            'data' => $responseData,
            'http_code' => $httpCode,
            'request_id' => $requestId
        ];
    }

    /**
     * Sync a product to Amazon
     * Creates or updates product listing via Listings API
     */
    public function syncProduct(int $productId): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Plugin not configured'];
        }

        $db = Database::getInstance();

        // Get product details
        $product = $db->selectOne("
            SELECT p.*, ps.amazon_sku, ps.amazon_asin, ps.sync_status
            FROM products p
            LEFT JOIN amazon_product_sync ps ON p.id = ps.product_id
            WHERE p.id = ?
        ", [$productId]);

        if (!$product) {
            return ['success' => false, 'error' => 'Product not found'];
        }

        // Generate SKU if not exists
        $amazonSku = $product['amazon_sku'] ?: ($product['sku'] ?: 'APP-' . $productId);
        $sellerId = $this->settings['seller_id'];
        $marketplaceId = $this->settings['marketplace_id'];

        // Check if listing exists using getListingsItem
        $existingListing = $this->apiRequest(
            'GET',
            "/listings/2021-08-01/items/{$sellerId}/{$amazonSku}",
            [],
            ['marketplaceIds' => $marketplaceId, 'includedData' => 'summaries,attributes']
        );

        // Prepare listing data
        $listingData = [
            'productType' => $this->determineProductType($product),
            'requirements' => 'LISTING',
            'attributes' => $this->buildProductAttributes($product)
        ];

        if ($existingListing['success'] && !empty($existingListing['data']['sku'])) {
            // Update existing listing using PATCH
            $response = $this->apiRequest(
                'PATCH',
                "/listings/2021-08-01/items/{$sellerId}/{$amazonSku}",
                [
                    'productType' => $listingData['productType'],
                    'patches' => $this->buildPatchOperations($listingData['attributes'])
                ],
                ['marketplaceIds' => $marketplaceId],
                'listings'
            );
        } else {
            // Create new listing using PUT
            $response = $this->apiRequest(
                'PUT',
                "/listings/2021-08-01/items/{$sellerId}/{$amazonSku}",
                $listingData,
                ['marketplaceIds' => $marketplaceId],
                'listings'
            );
        }

        // Update sync status in database
        $syncStatus = $response['success'] ? 'synced' : 'error';
        $errorMessage = $response['success'] ? null : ($response['error'] ?? 'Unknown error');

        $db->query("
            INSERT INTO amazon_product_sync (product_id, amazon_sku, sync_status, error_message, last_synced_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                amazon_sku = VALUES(amazon_sku),
                sync_status = VALUES(sync_status),
                error_message = VALUES(error_message),
                last_synced_at = NOW()
        ", [$productId, $amazonSku, $syncStatus, $errorMessage]);

        $this->log(
            $response['success'] ? "Product synced: {$product['name']}" : "Product sync failed: {$product['name']}",
            $response['success'] ? 'success' : 'error',
            ['product_id' => $productId, 'sku' => $amazonSku, 'request_id' => $response['request_id'] ?? null]
        );

        return $response;
    }

    /**
     * Sync inventory levels to Amazon
     */
    public function syncInventory(int $productId, int $quantity): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Plugin not configured'];
        }

        $db = Database::getInstance();

        // Get Amazon SKU for this product
        $sync = $db->selectOne(
            "SELECT amazon_sku, fulfillment_channel FROM amazon_product_sync WHERE product_id = ?",
            [$productId]
        );

        if (!$sync || empty($sync['amazon_sku'])) {
            return ['success' => false, 'error' => 'Product not linked to Amazon'];
        }

        $sellerId = $this->settings['seller_id'];
        $marketplaceId = $this->settings['marketplace_id'];

        // For Merchant Fulfilled (MFN), use Listings API to update quantity
        if ($sync['fulfillment_channel'] !== 'AFN') {
            $response = $this->apiRequest(
                'PATCH',
                "/listings/2021-08-01/items/{$sellerId}/{$sync['amazon_sku']}",
                [
                    'productType' => 'PRODUCT',
                    'patches' => [
                        [
                            'op' => 'replace',
                            'path' => '/attributes/fulfillment_availability',
                            'value' => [
                                [
                                    'fulfillment_channel_code' => 'DEFAULT',
                                    'quantity' => $quantity,
                                    'marketplace_id' => $marketplaceId
                                ]
                            ]
                        ]
                    ]
                ],
                ['marketplaceIds' => $marketplaceId],
                'inventory'
            );
        } else {
            // For FBA (AFN), inventory is managed by Amazon
            return ['success' => true, 'message' => 'FBA inventory managed by Amazon'];
        }

        if ($response['success']) {
            $db->query(
                "UPDATE amazon_product_sync SET last_inventory_sync = NOW() WHERE product_id = ?",
                [$productId]
            );
        }

        $this->log(
            $response['success'] ? "Inventory synced: SKU {$sync['amazon_sku']}" : "Inventory sync failed",
            $response['success'] ? 'success' : 'error',
            ['product_id' => $productId, 'quantity' => $quantity, 'request_id' => $response['request_id'] ?? null]
        );

        return $response;
    }

    /**
     * Import orders from Amazon
     */
    public function importOrders(?string $since = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Plugin not configured'];
        }

        $db = Database::getInstance();
        $marketplaceId = $this->settings['marketplace_id'];

        // Default to last 24 hours if no date specified
        $createdAfter = $since ?: date('Y-m-d\TH:i:s\Z', strtotime('-24 hours'));

        $query = [
            'MarketplaceIds' => $marketplaceId,
            'CreatedAfter' => $createdAfter,
            'OrderStatuses' => 'Unshipped,PartiallyShipped,Shipped'
        ];

        $response = $this->apiRequest('GET', '/orders/v0/orders', [], $query, 'orders');

        if (!$response['success']) {
            return $response;
        }

        $orders = $response['data']['payload']['Orders'] ?? [];
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($orders as $amazonOrder) {
            $amazonOrderId = $amazonOrder['AmazonOrderId'];

            // Check if already imported
            $existing = $db->selectOne(
                "SELECT id FROM amazon_order_sync WHERE amazon_order_id = ?",
                [$amazonOrderId]
            );

            if ($existing) {
                $skipped++;
                continue;
            }

            try {
                // Get order items
                $itemsResponse = $this->apiRequest(
                    'GET',
                    "/orders/v0/orders/{$amazonOrderId}/orderItems",
                    [],
                    [],
                    'orders'
                );

                if (!$itemsResponse['success']) {
                    $errors[] = "Failed to get items for order {$amazonOrderId}";
                    continue;
                }

                $orderItems = $itemsResponse['data']['payload']['OrderItems'] ?? [];

                // Create order in our system
                $orderId = $this->createLocalOrder($amazonOrder, $orderItems);

                // Record the sync
                $db->insert('amazon_order_sync', [
                    'order_id' => $orderId,
                    'amazon_order_id' => $amazonOrderId,
                    'amazon_order_status' => $amazonOrder['OrderStatus'],
                    'fulfillment_channel' => $amazonOrder['FulfillmentChannel'] ?? 'MFN',
                    'order_total' => $amazonOrder['OrderTotal']['Amount'] ?? 0,
                    'currency_code' => $amazonOrder['OrderTotal']['CurrencyCode'] ?? 'USD',
                    'purchase_date' => date('Y-m-d H:i:s', strtotime($amazonOrder['PurchaseDate'])),
                    'last_update_date' => date('Y-m-d H:i:s', strtotime($amazonOrder['LastUpdateDate']))
                ]);

                $imported++;

            } catch (\Exception $e) {
                $errors[] = "Error importing order {$amazonOrderId}: " . $e->getMessage();
            }

            // Respect rate limits
            usleep(100000); // 100ms delay between orders
        }

        $this->log("Orders imported: {$imported}, skipped: {$skipped}", 'success', [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ]);

        return [
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }

    /**
     * Create a local order from Amazon order data
     */
    private function createLocalOrder(array $amazonOrder, array $orderItems): ?int
    {
        $db = Database::getInstance();

        // Map Amazon order status to local status
        $statusMap = [
            'Pending' => 'pending',
            'Unshipped' => 'processing',
            'PartiallyShipped' => 'processing',
            'Shipped' => 'shipped',
            'Canceled' => 'cancelled',
            'Unfulfillable' => 'cancelled'
        ];

        $status = $statusMap[$amazonOrder['OrderStatus']] ?? 'pending';

        // Calculate totals
        $subtotal = 0;
        foreach ($orderItems as $item) {
            $subtotal += ($item['ItemPrice']['Amount'] ?? 0);
        }

        $shippingTotal = $amazonOrder['OrderTotal']['Amount'] - $subtotal;
        if ($shippingTotal < 0) $shippingTotal = 0;

        // Insert order
        $orderId = $db->insert('orders', [
            'user_id' => null,
            'status' => $status,
            'subtotal' => $subtotal,
            'shipping_total' => $shippingTotal,
            'tax_total' => 0,
            'discount_total' => 0,
            'total' => $amazonOrder['OrderTotal']['Amount'] ?? $subtotal,
            'currency' => $amazonOrder['OrderTotal']['CurrencyCode'] ?? 'USD',
            'payment_method' => 'amazon',
            'payment_provider' => 'amazon',
            'shipping_name' => $amazonOrder['ShippingAddress']['Name'] ?? 'Amazon Customer',
            'shipping_address1' => $amazonOrder['ShippingAddress']['AddressLine1'] ?? '',
            'shipping_address2' => $amazonOrder['ShippingAddress']['AddressLine2'] ?? '',
            'shipping_city' => $amazonOrder['ShippingAddress']['City'] ?? '',
            'shipping_state' => $amazonOrder['ShippingAddress']['StateOrRegion'] ?? '',
            'shipping_postal_code' => $amazonOrder['ShippingAddress']['PostalCode'] ?? '',
            'shipping_country' => $amazonOrder['ShippingAddress']['CountryCode'] ?? 'US',
            'billing_email' => $amazonOrder['BuyerEmail'] ?? '',
            'notes' => 'Imported from Amazon. Order ID: ' . $amazonOrder['AmazonOrderId'],
            'created_at' => date('Y-m-d H:i:s', strtotime($amazonOrder['PurchaseDate']))
        ]);

        // Insert order items
        foreach ($orderItems as $item) {
            // Try to find matching local product by SKU
            $localProduct = $db->selectOne(
                "SELECT p.id, p.name FROM products p
                 JOIN amazon_product_sync aps ON p.id = aps.product_id
                 WHERE aps.amazon_sku = ? OR aps.amazon_asin = ?",
                [$item['SellerSKU'] ?? '', $item['ASIN'] ?? '']
            );

            $db->insert('order_items', [
                'order_id' => $orderId,
                'product_id' => $localProduct['id'] ?? null,
                'product_name' => $item['Title'] ?? ($localProduct['name'] ?? 'Unknown Product'),
                'sku' => $item['SellerSKU'] ?? '',
                'quantity' => $item['QuantityOrdered'] ?? 1,
                'price' => ($item['ItemPrice']['Amount'] ?? 0) / ($item['QuantityOrdered'] ?? 1),
                'total' => $item['ItemPrice']['Amount'] ?? 0
            ]);
        }

        return $orderId;
    }

    /**
     * Get sync status for all products
     */
    public function getSyncStatus(): array
    {
        $db = Database::getInstance();
        return $db->select("
            SELECT p.id, p.name, p.sku, p.stock_quantity,
                   aps.amazon_sku, aps.amazon_asin, aps.fulfillment_channel,
                   aps.listing_status, aps.sync_status, aps.last_synced_at,
                   aps.last_inventory_sync, aps.error_message
            FROM products p
            LEFT JOIN amazon_product_sync aps ON p.id = aps.product_id
            WHERE p.is_active = 1
            ORDER BY aps.sync_status DESC, p.name
        ");
    }

    /**
     * Get recent sync logs
     */
    public function getSyncLogs(int $limit = 50): array
    {
        $db = Database::getInstance();
        return $db->select(
            "SELECT * FROM amazon_sync_log ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );
    }

    /**
     * Test the API connection
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Plugin not configured'];
        }

        // Test by getting seller participation
        $sellerId = $this->settings['seller_id'];
        $marketplaceId = $this->settings['marketplace_id'];

        $response = $this->apiRequest(
            'GET',
            "/sellers/v1/marketplaceParticipations",
            [],
            [],
            'default'
        );

        if ($response['success']) {
            $participations = $response['data']['payload'] ?? [];
            $marketplaceFound = false;

            foreach ($participations as $p) {
                if (($p['marketplace']['id'] ?? '') === $marketplaceId) {
                    $marketplaceFound = true;
                    break;
                }
            }

            if (!$marketplaceFound) {
                return [
                    'success' => false,
                    'error' => 'Seller is not registered in the selected marketplace'
                ];
            }

            return [
                'success' => true,
                'message' => 'Connection successful',
                'participations' => count($participations)
            ];
        }

        return $response;
    }

    /**
     * Determine the Amazon product type based on product data
     */
    private function determineProductType(array $product): string
    {
        // This would ideally be mapped from category or stored per-product
        // Default to generic PRODUCT type
        return 'PRODUCT';
    }

    /**
     * Build product attributes for Amazon listing
     */
    private function buildProductAttributes(array $product): array
    {
        $marketplaceId = $this->settings['marketplace_id'];

        return [
            'item_name' => [
                ['value' => $product['name'], 'marketplace_id' => $marketplaceId]
            ],
            'brand' => [
                ['value' => $product['brand'] ?? appName(), 'marketplace_id' => $marketplaceId]
            ],
            'manufacturer' => [
                ['value' => $product['manufacturer'] ?? appName(), 'marketplace_id' => $marketplaceId]
            ],
            'product_description' => [
                ['value' => strip_tags($product['description'] ?? ''), 'marketplace_id' => $marketplaceId]
            ],
            'bullet_point' => $this->extractBulletPoints($product['description'] ?? ''),
            'list_price' => [
                [
                    'Amount' => $product['price'],
                    'CurrencyCode' => 'USD',
                    'marketplace_id' => $marketplaceId
                ]
            ],
            'purchasable_offer' => [
                [
                    'currency' => 'USD',
                    'our_price' => [['schedule' => [['value_with_tax' => $product['price']]]]],
                    'marketplace_id' => $marketplaceId
                ]
            ],
            'fulfillment_availability' => [
                [
                    'fulfillment_channel_code' => 'DEFAULT',
                    'quantity' => $product['stock_quantity'] ?? 0,
                    'marketplace_id' => $marketplaceId
                ]
            ],
            'condition_type' => [
                ['value' => 'new_new', 'marketplace_id' => $marketplaceId]
            ]
        ];
    }

    /**
     * Extract bullet points from product description
     */
    private function extractBulletPoints(string $description): array
    {
        $marketplaceId = $this->settings['marketplace_id'];
        $bullets = [];

        // Try to extract list items
        if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $description, $matches)) {
            foreach (array_slice($matches[1], 0, 5) as $bullet) {
                $bullets[] = ['value' => strip_tags($bullet), 'marketplace_id' => $marketplaceId];
            }
        }

        // If no list items found, split by sentences
        if (empty($bullets)) {
            $text = strip_tags($description);
            $sentences = preg_split('/[.!?]+/', $text, 6, PREG_SPLIT_NO_EMPTY);
            foreach (array_slice($sentences, 0, 5) as $sentence) {
                $sentence = trim($sentence);
                if (strlen($sentence) > 10) {
                    $bullets[] = ['value' => $sentence, 'marketplace_id' => $marketplaceId];
                }
            }
        }

        return $bullets;
    }

    /**
     * Build patch operations for updating listings
     */
    private function buildPatchOperations(array $attributes): array
    {
        $patches = [];
        foreach ($attributes as $key => $value) {
            $patches[] = [
                'op' => 'replace',
                'path' => '/attributes/' . $key,
                'value' => $value
            ];
        }
        return $patches;
    }

    /**
     * Log an action to the sync log
     */
    private function log(string $message, string $status = 'success', array $details = []): void
    {
        try {
            $db = Database::getInstance();
            $db->insert('amazon_sync_log', [
                'action' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
                'status' => $status,
                'message' => $message,
                'request_id' => $details['request_id'] ?? null,
                'details' => json_encode($details)
            ]);
        } catch (\Exception $e) {
            error_log("Amazon Sync Log Error: " . $e->getMessage());
        }
    }

    /**
     * Link a local product to an existing Amazon listing
     */
    public function linkProduct(int $productId, string $amazonSku, ?string $asin = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Plugin not configured'];
        }

        $db = Database::getInstance();

        // Verify the SKU exists on Amazon
        $sellerId = $this->settings['seller_id'];
        $marketplaceId = $this->settings['marketplace_id'];

        $response = $this->apiRequest(
            'GET',
            "/listings/2021-08-01/items/{$sellerId}/{$amazonSku}",
            [],
            ['marketplaceIds' => $marketplaceId, 'includedData' => 'summaries']
        );

        if (!$response['success']) {
            return ['success' => false, 'error' => 'SKU not found on Amazon: ' . ($response['error'] ?? 'Unknown error')];
        }

        $listingData = $response['data'];
        $fetchedAsin = $listingData['summaries'][0]['asin'] ?? $asin;

        $db->query("
            INSERT INTO amazon_product_sync (product_id, amazon_sku, amazon_asin, sync_status, listing_status, last_synced_at)
            VALUES (?, ?, ?, 'synced', 'active', NOW())
            ON DUPLICATE KEY UPDATE
                amazon_sku = VALUES(amazon_sku),
                amazon_asin = VALUES(amazon_asin),
                sync_status = 'synced',
                listing_status = 'active',
                last_synced_at = NOW()
        ", [$productId, $amazonSku, $fetchedAsin]);

        $this->log("Product linked to Amazon SKU: {$amazonSku}", 'success', [
            'product_id' => $productId,
            'amazon_sku' => $amazonSku,
            'asin' => $fetchedAsin
        ]);

        return ['success' => true, 'asin' => $fetchedAsin];
    }
}
