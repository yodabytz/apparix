<?php

namespace App\Plugins;

use App\Core\Plugins\PluginInterface;
use App\Core\Database;

/**
 * eBay Sync Plugin for Apparix
 *
 * Syncs products and orders with eBay using the modern RESTful APIs:
 * - Inventory API for product/inventory management
 * - Fulfillment API for order management
 *
 * @version 1.1.0
 * @author Apparix
 */
class EbaySyncPlugin implements PluginInterface
{
    private array $settings = [];
    private ?string $accessToken = null;
    private ?int $tokenExpiry = null;

    // Rate limiting
    private int $requestCount = 0;
    private int $windowStart = 0;
    private const MAX_REQUESTS_PER_MINUTE = 100;

    private const ENDPOINTS = [
        'sandbox' => 'https://api.sandbox.ebay.com',
        'production' => 'https://api.ebay.com'
    ];

    private const AUTH_ENDPOINTS = [
        'sandbox' => 'https://api.sandbox.ebay.com/identity/v1/oauth2/token',
        'production' => 'https://api.ebay.com/identity/v1/oauth2/token'
    ];

    // All eBay marketplace IDs
    private const MARKETPLACES = [
        'EBAY_US' => 'United States (ebay.com)',
        'EBAY_CA' => 'Canada (ebay.ca)',
        'EBAY_GB' => 'United Kingdom (ebay.co.uk)',
        'EBAY_DE' => 'Germany (ebay.de)',
        'EBAY_FR' => 'France (ebay.fr)',
        'EBAY_IT' => 'Italy (ebay.it)',
        'EBAY_ES' => 'Spain (ebay.es)',
        'EBAY_AU' => 'Australia (ebay.com.au)',
        'EBAY_AT' => 'Austria (ebay.at)',
        'EBAY_BE' => 'Belgium (ebay.be)',
        'EBAY_CH' => 'Switzerland (ebay.ch)',
        'EBAY_NL' => 'Netherlands (ebay.nl)',
        'EBAY_IE' => 'Ireland (ebay.ie)',
        'EBAY_PL' => 'Poland (ebay.pl)',
        'EBAY_SG' => 'Singapore (ebay.com.sg)',
        'EBAY_MY' => 'Malaysia (ebay.com.my)',
        'EBAY_PH' => 'Philippines (ebay.ph)',
        'EBAY_HK' => 'Hong Kong',
        'EBAY_TW' => 'Taiwan (ebay.com.tw)',
        'EBAY_JP' => 'Japan (ebay.co.jp)',
        'EBAY_MOTORS_US' => 'eBay Motors (ebay.com/motors)'
    ];

    // OAuth scopes needed for full functionality
    private const OAUTH_SCOPES = [
        'https://api.ebay.com/oauth/api_scope',
        'https://api.ebay.com/oauth/api_scope/sell.inventory',
        'https://api.ebay.com/oauth/api_scope/sell.inventory.readonly',
        'https://api.ebay.com/oauth/api_scope/sell.fulfillment',
        'https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly',
        'https://api.ebay.com/oauth/api_scope/sell.account',
        'https://api.ebay.com/oauth/api_scope/sell.account.readonly'
    ];

    public function getSlug(): string { return 'ebay-sync'; }
    public function getName(): string { return 'eBay Sync'; }
    public function getVersion(): string { return '1.1.0'; }
    public function getType(): string { return 'marketplace'; }
    public function getDescription(): string { return 'Sync products and orders with eBay via RESTful APIs'; }
    public function getAuthor(): string { return 'Apparix'; }
    public function getDefaultSettings(): array { return ['environment' => 'sandbox', 'site_id' => 'EBAY_US']; }

    public function init(): void { $this->loadSettings(); }

    public function onActivate(): void
    {
        $this->createSyncTables();
        $this->log('eBay Sync plugin activated (v' . $this->getVersion() . ')');
    }

    public function onDeactivate(): void { $this->log('eBay Sync plugin deactivated'); }

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
        if (empty($settings['client_id'])) $errors[] = 'Client ID (App ID) is required';
        if (empty($settings['client_secret'])) $errors[] = 'Client Secret (Cert ID) is required';
        if (empty($settings['refresh_token'])) $errors[] = 'Refresh Token is required';
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
        return !empty($this->settings['client_id'])
            && !empty($this->settings['client_secret'])
            && !empty($this->settings['refresh_token']);
    }

    private function getEnvironment(): string { return $this->settings['environment'] ?? 'sandbox'; }
    private function getBaseUrl(): string { return self::ENDPOINTS[$this->getEnvironment()]; }
    private function getMarketplaceId(): string { return $this->settings['site_id'] ?? 'EBAY_US'; }

    private function createSyncTables(): void
    {
        $db = Database::getInstance();

        $db->query("
            CREATE TABLE IF NOT EXISTS ebay_product_sync (
                id INT PRIMARY KEY AUTO_INCREMENT,
                product_id INT NOT NULL,
                variant_id INT NULL,
                ebay_listing_id VARCHAR(50),
                ebay_sku VARCHAR(100),
                offer_id VARCHAR(50),
                inventory_item_group_key VARCHAR(100),
                last_synced_at TIMESTAMP NULL,
                sync_status ENUM('pending', 'synced', 'error') DEFAULT 'pending',
                error_message TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_product_variant (product_id, variant_id),
                INDEX idx_listing (ebay_listing_id),
                INDEX idx_sku (ebay_sku),
                INDEX idx_offer (offer_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS ebay_order_sync (
                id INT PRIMARY KEY AUTO_INCREMENT,
                order_id INT,
                ebay_order_id VARCHAR(50) NOT NULL,
                buyer_username VARCHAR(100),
                order_status VARCHAR(50),
                order_total DECIMAL(10,2),
                imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_ebay_order (ebay_order_id),
                INDEX idx_order (order_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS ebay_sync_log (
                id INT PRIMARY KEY AUTO_INCREMENT,
                request_id VARCHAR(50),
                action VARCHAR(50) NOT NULL,
                status ENUM('success', 'error') NOT NULL,
                message TEXT,
                details JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_request (request_id),
                INDEX idx_action (action),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /**
     * Get OAuth access token using refresh token grant
     */
    private function getAccessToken(): ?string
    {
        // Return cached token if still valid
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        $requestId = $this->generateRequestId();
        $env = $this->getEnvironment();
        $authUrl = self::AUTH_ENDPOINTS[$env];
        $credentials = base64_encode($this->settings['client_id'] . ':' . $this->settings['client_secret']);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $authUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->settings['refresh_token'],
                'scope' => implode(' ', self::OAUTH_SCOPES)
            ]),
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . $credentials,
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        // Check for curl errors
        if ($curlErrno !== 0) {
            $this->log("OAuth token request failed: {$curlError}", 'error', [
                'request_id' => $requestId,
                'curl_errno' => $curlErrno
            ]);
            return null;
        }

        $data = json_decode($response, true);

        // Check HTTP status
        if ($httpCode !== 200) {
            $error = $data['error_description'] ?? $data['error'] ?? 'Unknown error';
            $this->log("OAuth token request failed (HTTP {$httpCode}): {$error}", 'error', [
                'request_id' => $requestId,
                'http_code' => $httpCode,
                'response' => $data
            ]);
            return null;
        }

        if (!$data || !isset($data['access_token'])) {
            $this->log('OAuth response missing access_token', 'error', [
                'request_id' => $requestId,
                'response' => $response
            ]);
            return null;
        }

        $this->accessToken = $data['access_token'];
        $this->tokenExpiry = time() + ($data['expires_in'] ?? 7200) - 60; // 60 second buffer

        return $this->accessToken;
    }

    /**
     * Make API request with rate limiting and retry logic
     */
    private function apiRequest(string $method, string $path, array $data = [], array $query = [], int $retries = 3): ?array
    {
        $requestId = $this->generateRequestId();

        // Rate limiting
        $this->enforceRateLimit();

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['error' => true, 'message' => 'Failed to obtain access token'];
        }

        $url = $this->getBaseUrl() . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            'Accept: application/json',
            'X-EBAY-C-MARKETPLACE-ID: ' . $this->getMarketplaceId()
        ];

        // Add Content-Language header for inventory items
        if (strpos($path, '/inventory_item') !== false) {
            $headers[] = 'Content-Language: en-US';
        }

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_HTTPHEADER => $headers
            ]);

            if ($method !== 'GET') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                if (!empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);

            $this->requestCount++;

            // Curl error
            if ($curlErrno !== 0) {
                $this->log("API request failed (attempt {$attempt}): {$curlError}", 'error', [
                    'request_id' => $requestId,
                    'method' => $method,
                    'path' => $path,
                    'curl_errno' => $curlErrno
                ]);

                if ($attempt < $retries) {
                    $this->exponentialBackoff($attempt);
                    continue;
                }
                return ['error' => true, 'message' => "Connection error: {$curlError}"];
            }

            $responseData = json_decode($response, true) ?? [];

            // Success responses
            if ($httpCode >= 200 && $httpCode < 300) {
                return $responseData;
            }

            // Rate limited - retry with backoff
            if ($httpCode === 429) {
                $this->log("Rate limited (attempt {$attempt})", 'error', [
                    'request_id' => $requestId,
                    'method' => $method,
                    'path' => $path
                ]);

                if ($attempt < $retries) {
                    $this->exponentialBackoff($attempt, 2); // Longer backoff for rate limits
                    continue;
                }
            }

            // Server errors - retry
            if ($httpCode >= 500 && $attempt < $retries) {
                $this->log("Server error {$httpCode} (attempt {$attempt})", 'error', [
                    'request_id' => $requestId,
                    'method' => $method,
                    'path' => $path,
                    'response' => $responseData
                ]);
                $this->exponentialBackoff($attempt);
                continue;
            }

            // Client errors or final attempt - return error
            $errorMessage = $this->extractErrorMessage($responseData, $httpCode);
            $this->log("API request failed: {$errorMessage}", 'error', [
                'request_id' => $requestId,
                'method' => $method,
                'path' => $path,
                'http_code' => $httpCode,
                'response' => $responseData
            ]);

            return [
                'error' => true,
                'http_code' => $httpCode,
                'message' => $errorMessage,
                'errors' => $responseData['errors'] ?? []
            ];
        }

        return ['error' => true, 'message' => 'Max retries exceeded'];
    }

    /**
     * Extract user-friendly error message from eBay response
     */
    private function extractErrorMessage(array $response, int $httpCode): string
    {
        if (isset($response['errors']) && is_array($response['errors'])) {
            $messages = [];
            foreach ($response['errors'] as $error) {
                $msg = $error['message'] ?? $error['longMessage'] ?? '';
                if (!empty($msg)) {
                    $messages[] = $msg;
                }
            }
            if (!empty($messages)) {
                return implode('; ', $messages);
            }
        }

        if (isset($response['error_description'])) {
            return $response['error_description'];
        }

        return "HTTP {$httpCode} error";
    }

    /**
     * Enforce rate limiting
     */
    private function enforceRateLimit(): void
    {
        $now = time();

        // Reset counter every minute
        if ($now - $this->windowStart >= 60) {
            $this->windowStart = $now;
            $this->requestCount = 0;
        }

        // If approaching limit, wait
        if ($this->requestCount >= self::MAX_REQUESTS_PER_MINUTE) {
            $waitTime = 60 - ($now - $this->windowStart);
            if ($waitTime > 0) {
                sleep($waitTime);
            }
            $this->windowStart = time();
            $this->requestCount = 0;
        }
    }

    /**
     * Exponential backoff for retries
     */
    private function exponentialBackoff(int $attempt, int $multiplier = 1): void
    {
        $delay = min(pow(2, $attempt) * $multiplier, 30); // Max 30 seconds
        sleep($delay);
    }

    /**
     * Generate unique request ID for tracking
     */
    private function generateRequestId(): string
    {
        return 'ebay_' . bin2hex(random_bytes(8));
    }

    /**
     * Test connection to eBay API
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Plugin not configured'];
        }

        $requestId = $this->generateRequestId();

        // Try to get access token
        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'success' => false,
                'error' => 'Failed to authenticate with eBay. Please check your Client ID, Client Secret, and Refresh Token.',
                'request_id' => $requestId
            ];
        }

        // Try to get inventory locations to verify full access
        $response = $this->apiRequest('GET', '/sell/inventory/v1/location', [], ['limit' => 1]);

        if (isset($response['error']) && $response['error']) {
            return [
                'success' => false,
                'error' => 'Authentication successful but API access failed: ' . ($response['message'] ?? 'Unknown error'),
                'request_id' => $requestId
            ];
        }

        $this->log('Connection test successful', 'success', ['request_id' => $requestId]);

        return [
            'success' => true,
            'message' => 'Successfully connected to eBay (' . $this->getEnvironment() . ')',
            'marketplace' => $this->getMarketplaceId(),
            'request_id' => $requestId
        ];
    }

    /**
     * Sync a product to eBay
     * Uses the Inventory API flow: createOrReplaceInventoryItem -> createOffer -> publishOffer
     */
    public function syncProduct(int $productId): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Plugin not configured'];
        }

        $requestId = $this->generateRequestId();
        $db = Database::getInstance();

        // Get product data
        $product = $db->selectOne(
            "SELECT * FROM products WHERE id = ? AND is_active = 1",
            [$productId]
        );

        if (!$product) {
            return ['success' => false, 'error' => 'Product not found or inactive'];
        }

        // Get product images
        $images = $db->select(
            "SELECT image_path FROM product_images WHERE product_id = ? ORDER BY sort_order LIMIT 12",
            [$productId]
        );

        // Check for existing sync record
        $syncRecord = $db->selectOne(
            "SELECT * FROM ebay_product_sync WHERE product_id = ? AND variant_id IS NULL",
            [$productId]
        );

        try {
            $sku = $product['sku'] ?: 'APP-' . $productId;

            // Step 1: Create or update inventory item
            $inventoryItemResult = $this->createOrUpdateInventoryItem($product, $images, $sku, $requestId);

            if (!$inventoryItemResult['success']) {
                $this->updateSyncStatus($productId, null, 'error', $inventoryItemResult['error']);
                return $inventoryItemResult;
            }

            // Step 2: Create or update offer
            $offerId = $syncRecord['offer_id'] ?? null;
            $offerResult = $this->createOrUpdateOffer($product, $sku, $offerId, $requestId);

            if (!$offerResult['success']) {
                $this->updateSyncStatus($productId, null, 'error', $offerResult['error']);
                return $offerResult;
            }

            $offerId = $offerResult['offer_id'];

            // Step 3: Publish offer (if new or needs republishing)
            $listingId = $syncRecord['ebay_listing_id'] ?? null;
            if (!$listingId) {
                $publishResult = $this->publishOffer($offerId, $requestId);

                if (!$publishResult['success']) {
                    $this->updateSyncStatus($productId, null, 'error', $publishResult['error'], $offerId);
                    return $publishResult;
                }

                $listingId = $publishResult['listing_id'];
            }

            // Update sync record
            if ($syncRecord) {
                $db->update(
                    "UPDATE ebay_product_sync SET ebay_listing_id = ?, ebay_sku = ?, offer_id = ?,
                     last_synced_at = NOW(), sync_status = 'synced', error_message = NULL WHERE id = ?",
                    [$listingId, $sku, $offerId, $syncRecord['id']]
                );
            } else {
                $db->insert(
                    "INSERT INTO ebay_product_sync (product_id, ebay_listing_id, ebay_sku, offer_id, last_synced_at, sync_status)
                     VALUES (?, ?, ?, ?, NOW(), 'synced')",
                    [$productId, $listingId, $sku, $offerId]
                );
            }

            $this->log('Product synced successfully', 'success', [
                'request_id' => $requestId,
                'product_id' => $productId,
                'listing_id' => $listingId,
                'offer_id' => $offerId
            ]);

            return [
                'success' => true,
                'message' => 'Product synced to eBay',
                'listing_id' => $listingId,
                'offer_id' => $offerId,
                'request_id' => $requestId
            ];

        } catch (\Exception $e) {
            $this->log('Product sync failed: ' . $e->getMessage(), 'error', [
                'request_id' => $requestId,
                'product_id' => $productId,
                'exception' => $e->getMessage()
            ]);

            $this->updateSyncStatus($productId, null, 'error', $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create or update an inventory item
     */
    private function createOrUpdateInventoryItem(array $product, array $images, string $sku, string $requestId): array
    {
        $imageUrls = [];
        $baseUrl = rtrim($this->getStoreUrl(), '/');

        foreach ($images as $img) {
            $imageUrls[] = $baseUrl . '/assets/images/products/' . $img['image_path'];
        }

        // Build inventory item data
        $data = [
            'availability' => [
                'shipToLocationAvailability' => [
                    'quantity' => (int)($product['quantity'] ?? 0)
                ]
            ],
            'condition' => $this->mapCondition($product['condition'] ?? 'new'),
            'product' => [
                'title' => substr($product['name'], 0, 80),
                'description' => $this->formatDescription($product['description'] ?? ''),
                'imageUrls' => array_slice($imageUrls, 0, 12) // eBay max 12 images
            ]
        ];

        // Add aspects/attributes if available
        if (!empty($product['brand'])) {
            $data['product']['aspects'] = [
                'Brand' => [$product['brand']]
            ];
        }

        $response = $this->apiRequest('PUT', '/sell/inventory/v1/inventory_item/' . urlencode($sku), $data);

        // PUT returns 204 No Content on success
        if ($response === null || (is_array($response) && empty($response))) {
            return ['success' => true];
        }

        if (isset($response['error']) && $response['error']) {
            return ['success' => false, 'error' => $response['message'] ?? 'Failed to create inventory item'];
        }

        return ['success' => true];
    }

    /**
     * Create or update an offer for the inventory item
     */
    private function createOrUpdateOffer(array $product, string $sku, ?string $existingOfferId, string $requestId): array
    {
        $price = (float)($product['sale_price'] ?: $product['price']);

        $data = [
            'sku' => $sku,
            'marketplaceId' => $this->getMarketplaceId(),
            'format' => $this->settings['listing_format'] ?? 'FIXED_PRICE',
            'listingDuration' => $this->settings['listing_duration'] ?? 'GTC',
            'availableQuantity' => (int)($product['quantity'] ?? 0),
            'pricingSummary' => [
                'price' => [
                    'currency' => $this->getCurrency(),
                    'value' => number_format($price, 2, '.', '')
                ]
            ],
            'listingPolicies' => $this->getListingPolicies()
        ];

        // Add category if configured
        if (!empty($this->settings['default_category_id'])) {
            $data['categoryId'] = $this->settings['default_category_id'];
        }

        if ($existingOfferId) {
            // Update existing offer
            $response = $this->apiRequest('PUT', '/sell/inventory/v1/offer/' . $existingOfferId, $data);

            if (isset($response['error']) && $response['error']) {
                return ['success' => false, 'error' => $response['message'] ?? 'Failed to update offer'];
            }

            return ['success' => true, 'offer_id' => $existingOfferId];
        } else {
            // Create new offer
            $response = $this->apiRequest('POST', '/sell/inventory/v1/offer', $data);

            if (isset($response['error']) && $response['error']) {
                return ['success' => false, 'error' => $response['message'] ?? 'Failed to create offer'];
            }

            if (!isset($response['offerId'])) {
                return ['success' => false, 'error' => 'No offer ID returned'];
            }

            return ['success' => true, 'offer_id' => $response['offerId']];
        }
    }

    /**
     * Publish an offer to make it a live listing
     */
    private function publishOffer(string $offerId, string $requestId): array
    {
        $response = $this->apiRequest('POST', '/sell/inventory/v1/offer/' . $offerId . '/publish');

        if (isset($response['error']) && $response['error']) {
            return ['success' => false, 'error' => $response['message'] ?? 'Failed to publish offer'];
        }

        if (!isset($response['listingId'])) {
            return ['success' => false, 'error' => 'No listing ID returned'];
        }

        return ['success' => true, 'listing_id' => $response['listingId']];
    }

    /**
     * Sync inventory quantity for a product
     */
    public function syncInventory(int $productId, int $quantity): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Plugin not configured'];
        }

        $requestId = $this->generateRequestId();
        $db = Database::getInstance();

        // Get sync record
        $syncRecord = $db->selectOne(
            "SELECT * FROM ebay_product_sync WHERE product_id = ? AND variant_id IS NULL AND sync_status = 'synced'",
            [$productId]
        );

        if (!$syncRecord || !$syncRecord['ebay_sku']) {
            return ['success' => false, 'error' => 'Product not synced to eBay yet'];
        }

        $sku = $syncRecord['ebay_sku'];

        // Update inventory via Inventory API
        $data = [
            'availability' => [
                'shipToLocationAvailability' => [
                    'quantity' => max(0, $quantity)
                ]
            ]
        ];

        $response = $this->apiRequest('PUT', '/sell/inventory/v1/inventory_item/' . urlencode($sku), $data);

        // PUT returns 204 No Content on success
        if ($response === null || (is_array($response) && empty($response))) {
            // Also update the offer quantity if we have an offer ID
            if ($syncRecord['offer_id']) {
                $offerData = [
                    'availableQuantity' => max(0, $quantity)
                ];
                $this->apiRequest('PUT', '/sell/inventory/v1/offer/' . $syncRecord['offer_id'], $offerData);
            }

            $db->update(
                "UPDATE ebay_product_sync SET last_synced_at = NOW() WHERE id = ?",
                [$syncRecord['id']]
            );

            $this->log('Inventory synced', 'success', [
                'request_id' => $requestId,
                'product_id' => $productId,
                'sku' => $sku,
                'quantity' => $quantity
            ]);

            return ['success' => true, 'message' => "Inventory updated to {$quantity}"];
        }

        if (isset($response['error']) && $response['error']) {
            $this->log('Inventory sync failed', 'error', [
                'request_id' => $requestId,
                'product_id' => $productId,
                'error' => $response['message']
            ]);
            return ['success' => false, 'error' => $response['message'] ?? 'Failed to update inventory'];
        }

        return ['success' => true];
    }

    /**
     * Import orders from eBay
     */
    public function importOrders(?string $since = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Plugin not configured'];
        }

        $requestId = $this->generateRequestId();
        $db = Database::getInstance();

        // Default to last 24 hours
        if (!$since) {
            $since = date('Y-m-d\TH:i:s.000\Z', strtotime('-24 hours'));
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $offset = 0;
        $limit = 50;

        do {
            $response = $this->apiRequest('GET', '/sell/fulfillment/v1/order', [], [
                'filter' => "creationdate:[{$since}..]",
                'limit' => $limit,
                'offset' => $offset
            ]);

            if (isset($response['error']) && $response['error']) {
                $this->log('Order import failed', 'error', [
                    'request_id' => $requestId,
                    'error' => $response['message']
                ]);
                return [
                    'success' => false,
                    'error' => $response['message'] ?? 'Failed to fetch orders',
                    'imported' => $imported,
                    'skipped' => $skipped
                ];
            }

            $orders = $response['orders'] ?? [];

            foreach ($orders as $ebayOrder) {
                $ebayOrderId = $ebayOrder['orderId'] ?? null;

                if (!$ebayOrderId) {
                    continue;
                }

                // Check if already imported
                $existing = $db->selectOne(
                    "SELECT id FROM ebay_order_sync WHERE ebay_order_id = ?",
                    [$ebayOrderId]
                );

                if ($existing) {
                    $skipped++;
                    continue;
                }

                try {
                    $orderId = $this->createOrderFromEbay($ebayOrder, $requestId);

                    if ($orderId) {
                        $db->insert(
                            "INSERT INTO ebay_order_sync (order_id, ebay_order_id, buyer_username, order_status, order_total)
                             VALUES (?, ?, ?, ?, ?)",
                            [
                                $orderId,
                                $ebayOrderId,
                                $ebayOrder['buyer']['username'] ?? null,
                                $ebayOrder['orderFulfillmentStatus'] ?? null,
                                $ebayOrder['pricingSummary']['total']['value'] ?? 0
                            ]
                        );
                        $imported++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Order {$ebayOrderId}: " . $e->getMessage();
                    $this->log('Failed to import order', 'error', [
                        'request_id' => $requestId,
                        'ebay_order_id' => $ebayOrderId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $offset += $limit;
            $total = $response['total'] ?? 0;

        } while ($offset < $total && !empty($orders));

        $this->log('Order import completed', 'success', [
            'request_id' => $requestId,
            'imported' => $imported,
            'skipped' => $skipped
        ]);

        return [
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'request_id' => $requestId
        ];
    }

    /**
     * Create an Apparix order from eBay order data
     */
    private function createOrderFromEbay(array $ebayOrder, string $requestId): ?int
    {
        $db = Database::getInstance();

        // Extract shipping address
        $fulfillmentInstructions = $ebayOrder['fulfillmentStartInstructions'][0] ?? [];
        $shippingAddress = $fulfillmentInstructions['shippingStep']['shipTo'] ?? [];
        $contactAddress = $shippingAddress['contactAddress'] ?? [];

        // Create shipping address string
        $addressParts = array_filter([
            $contactAddress['addressLine1'] ?? '',
            $contactAddress['addressLine2'] ?? '',
            $contactAddress['city'] ?? '',
            $contactAddress['stateOrProvince'] ?? '',
            $contactAddress['postalCode'] ?? '',
            $contactAddress['countryCode'] ?? ''
        ]);
        $shippingAddressStr = implode(', ', $addressParts);

        // Get buyer info
        $buyer = $ebayOrder['buyer'] ?? [];
        $buyerName = $shippingAddress['fullName'] ?? $buyer['username'] ?? 'eBay Customer';
        $buyerEmail = $shippingAddress['email'] ?? $buyer['buyerRegistrationAddress']['email'] ?? '';

        // Extract pricing
        $pricing = $ebayOrder['pricingSummary'] ?? [];
        $subtotal = (float)($pricing['priceSubtotal']['value'] ?? 0);
        $shipping = (float)($pricing['deliveryCost']['value'] ?? 0);
        $tax = (float)($pricing['tax']['value'] ?? 0);
        $total = (float)($pricing['total']['value'] ?? 0);

        // Map order status
        $ebayStatus = $ebayOrder['orderFulfillmentStatus'] ?? 'NOT_STARTED';
        $status = match($ebayStatus) {
            'FULFILLED' => 'shipped',
            'IN_PROGRESS' => 'processing',
            default => 'pending'
        };

        // Create the order
        $orderId = $db->insert(
            "INSERT INTO orders (customer_name, email, phone, shipping_address, billing_address,
             subtotal, shipping_cost, tax, total, status, payment_status, payment_method,
             payment_provider, notes, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', 'ebay', 'ebay', ?, NOW())",
            [
                $buyerName,
                $buyerEmail,
                $shippingAddress['primaryPhone']['phoneNumber'] ?? '',
                $shippingAddressStr,
                $shippingAddressStr,
                $subtotal,
                $shipping,
                $tax,
                $total,
                $status,
                'Imported from eBay Order: ' . ($ebayOrder['orderId'] ?? '')
            ]
        );

        // Add order items
        $lineItems = $ebayOrder['lineItems'] ?? [];
        foreach ($lineItems as $item) {
            $sku = $item['sku'] ?? '';
            $quantity = (int)($item['quantity'] ?? 1);
            $price = (float)($item['lineItemCost']['value'] ?? 0) / $quantity;
            $title = $item['title'] ?? 'eBay Item';

            // Try to find matching product
            $product = null;
            if ($sku) {
                $product = $db->selectOne("SELECT id FROM products WHERE sku = ?", [$sku]);
            }

            $db->insert(
                "INSERT INTO order_items (order_id, product_id, product_name, sku, quantity, price, total)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $orderId,
                    $product['id'] ?? null,
                    $title,
                    $sku,
                    $quantity,
                    $price,
                    $quantity * $price
                ]
            );
        }

        return $orderId;
    }

    /**
     * Link an existing eBay listing to a product
     */
    public function linkProduct(int $productId, string $ebayListingId): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Plugin not configured'];
        }

        $requestId = $this->generateRequestId();
        $db = Database::getInstance();

        // Verify product exists
        $product = $db->selectOne("SELECT id, sku FROM products WHERE id = ?", [$productId]);
        if (!$product) {
            return ['success' => false, 'error' => 'Product not found'];
        }

        // Get the eBay item details to verify it exists and get SKU
        $response = $this->apiRequest('GET', '/sell/inventory/v1/inventory_item', [], [
            'limit' => 100
        ]);

        if (isset($response['error']) && $response['error']) {
            return ['success' => false, 'error' => 'Failed to fetch eBay inventory: ' . ($response['message'] ?? 'Unknown error')];
        }

        // Try to find the offer for this listing
        $offersResponse = $this->apiRequest('GET', '/sell/inventory/v1/offer', [], [
            'limit' => 100
        ]);

        $foundOffer = null;
        $foundSku = null;

        if (isset($offersResponse['offers'])) {
            foreach ($offersResponse['offers'] as $offer) {
                if (isset($offer['listing']['listingId']) && $offer['listing']['listingId'] === $ebayListingId) {
                    $foundOffer = $offer;
                    $foundSku = $offer['sku'] ?? null;
                    break;
                }
            }
        }

        if (!$foundOffer) {
            return ['success' => false, 'error' => 'eBay listing not found. Please verify the listing ID.'];
        }

        // Check if already linked
        $existing = $db->selectOne(
            "SELECT id FROM ebay_product_sync WHERE product_id = ? AND variant_id IS NULL",
            [$productId]
        );

        if ($existing) {
            $db->update(
                "UPDATE ebay_product_sync SET ebay_listing_id = ?, ebay_sku = ?, offer_id = ?,
                 last_synced_at = NOW(), sync_status = 'synced', error_message = NULL WHERE id = ?",
                [$ebayListingId, $foundSku, $foundOffer['offerId'] ?? null, $existing['id']]
            );
        } else {
            $db->insert(
                "INSERT INTO ebay_product_sync (product_id, ebay_listing_id, ebay_sku, offer_id, last_synced_at, sync_status)
                 VALUES (?, ?, ?, ?, NOW(), 'synced')",
                [$productId, $ebayListingId, $foundSku, $foundOffer['offerId'] ?? null]
            );
        }

        $this->log('Product linked to eBay listing', 'success', [
            'request_id' => $requestId,
            'product_id' => $productId,
            'listing_id' => $ebayListingId,
            'sku' => $foundSku
        ]);

        return [
            'success' => true,
            'message' => 'Product linked to eBay listing',
            'listing_id' => $ebayListingId,
            'sku' => $foundSku,
            'request_id' => $requestId
        ];
    }

    /**
     * Unlink a product from eBay (removes sync record only, not the eBay listing)
     */
    public function unlinkProduct(int $productId): array
    {
        $db = Database::getInstance();

        $deleted = $db->delete(
            "DELETE FROM ebay_product_sync WHERE product_id = ?",
            [$productId]
        );

        if ($deleted) {
            $this->log('Product unlinked from eBay', 'success', ['product_id' => $productId]);
            return ['success' => true, 'message' => 'Product unlinked from eBay'];
        }

        return ['success' => false, 'error' => 'Product was not linked to eBay'];
    }

    /**
     * Get sync status for all products
     */
    public function getSyncStatus(): array
    {
        $db = Database::getInstance();
        return $db->select(
            "SELECT p.id, p.name, p.sku, p.quantity, eps.ebay_sku, eps.ebay_listing_id,
                    eps.offer_id, eps.sync_status, eps.last_synced_at, eps.error_message
             FROM products p
             LEFT JOIN ebay_product_sync eps ON p.id = eps.product_id AND eps.variant_id IS NULL
             WHERE p.is_active = 1
             ORDER BY p.name"
        );
    }

    /**
     * Get recent sync logs
     */
    public function getRecentLogs(int $limit = 50): array
    {
        $db = Database::getInstance();
        return $db->select(
            "SELECT * FROM ebay_sync_log ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );
    }

    /**
     * Update sync status for a product
     */
    private function updateSyncStatus(int $productId, ?int $variantId, string $status, ?string $errorMessage = null, ?string $offerId = null): void
    {
        $db = Database::getInstance();

        $existing = $db->selectOne(
            "SELECT id FROM ebay_product_sync WHERE product_id = ? AND variant_id " . ($variantId ? "= ?" : "IS NULL"),
            $variantId ? [$productId, $variantId] : [$productId]
        );

        if ($existing) {
            $sql = "UPDATE ebay_product_sync SET sync_status = ?, error_message = ?";
            $params = [$status, $errorMessage];

            if ($offerId) {
                $sql .= ", offer_id = ?";
                $params[] = $offerId;
            }

            $sql .= " WHERE id = ?";
            $params[] = $existing['id'];

            $db->update($sql, $params);
        } else {
            $db->insert(
                "INSERT INTO ebay_product_sync (product_id, variant_id, sync_status, error_message, offer_id) VALUES (?, ?, ?, ?, ?)",
                [$productId, $variantId, $status, $errorMessage, $offerId]
            );
        }
    }

    /**
     * Map Apparix condition to eBay condition enum
     */
    private function mapCondition(string $condition): string
    {
        return match(strtolower($condition)) {
            'new', 'new_with_tags', 'new_with_box' => 'NEW',
            'new_other', 'new_without_tags' => 'NEW_OTHER',
            'new_with_defects' => 'NEW_WITH_DEFECTS',
            'certified_refurbished' => 'CERTIFIED_REFURBISHED',
            'excellent_refurbished' => 'EXCELLENT_REFURBISHED',
            'very_good_refurbished' => 'VERY_GOOD_REFURBISHED',
            'good_refurbished' => 'GOOD_REFURBISHED',
            'seller_refurbished' => 'SELLER_REFURBISHED',
            'like_new', 'used_excellent' => 'LIKE_NEW',
            'used', 'used_good' => 'USED_GOOD',
            'used_acceptable' => 'USED_ACCEPTABLE',
            'for_parts' => 'FOR_PARTS_OR_NOT_WORKING',
            default => 'NEW'
        };
    }

    /**
     * Format description for eBay (HTML allowed)
     */
    private function formatDescription(string $description): string
    {
        // eBay allows HTML in descriptions
        // Remove any potentially dangerous tags
        $description = strip_tags($description, '<p><br><b><strong><i><em><u><ul><ol><li><h1><h2><h3><h4><h5><h6><div><span><table><tr><td><th>');

        // Ensure minimum length
        if (strlen($description) < 20) {
            $description = $description . "\n\nThank you for viewing our listing!";
        }

        return $description;
    }

    /**
     * Get listing policies (payment, fulfillment, return)
     * These should be set up in eBay Seller Hub first
     */
    private function getListingPolicies(): array
    {
        $policies = [];

        if (!empty($this->settings['fulfillment_policy_id'])) {
            $policies['fulfillmentPolicyId'] = $this->settings['fulfillment_policy_id'];
        }

        if (!empty($this->settings['payment_policy_id'])) {
            $policies['paymentPolicyId'] = $this->settings['payment_policy_id'];
        }

        if (!empty($this->settings['return_policy_id'])) {
            $policies['returnPolicyId'] = $this->settings['return_policy_id'];
        }

        return $policies;
    }

    /**
     * Get currency code based on marketplace
     */
    private function getCurrency(): string
    {
        return match($this->getMarketplaceId()) {
            'EBAY_GB' => 'GBP',
            'EBAY_DE', 'EBAY_FR', 'EBAY_IT', 'EBAY_ES', 'EBAY_AT', 'EBAY_BE', 'EBAY_NL', 'EBAY_IE' => 'EUR',
            'EBAY_AU' => 'AUD',
            'EBAY_CA' => 'CAD',
            'EBAY_CH' => 'CHF',
            'EBAY_PL' => 'PLN',
            'EBAY_SG' => 'SGD',
            'EBAY_MY' => 'MYR',
            'EBAY_PH' => 'PHP',
            'EBAY_HK' => 'HKD',
            'EBAY_TW' => 'TWD',
            'EBAY_JP' => 'JPY',
            default => 'USD'
        };
    }

    /**
     * Get store URL for image links
     */
    private function getStoreUrl(): string
    {
        return $this->settings['store_url'] ?? (defined('APP_URL') ? APP_URL : 'https://example.com');
    }

    /**
     * Log action to database
     */
    private function log(string $message, string $status = 'success', array $details = []): void
    {
        try {
            $db = Database::getInstance();
            $db->insert(
                "INSERT INTO ebay_sync_log (request_id, action, status, message, details) VALUES (?, ?, ?, ?, ?)",
                [
                    $details['request_id'] ?? $this->generateRequestId(),
                    debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
                    $status,
                    $message,
                    json_encode($details)
                ]
            );
        } catch (\Exception $e) {
            error_log("eBay Sync log error: " . $e->getMessage());
        }
    }
}
