<?php

namespace App\Plugins;

use App\Core\Plugins\PluginInterface;
use App\Core\Database;

/**
 * Etsy Sync Plugin for Apparix
 *
 * Syncs products and orders with Etsy using Open API v3
 * - OAuth 2.0 with refresh token support
 * - Listings API for product management
 * - Receipts API for order management
 *
 * @version 1.1.0
 * @author Apparix
 */
class EtsySyncPlugin implements PluginInterface
{
    private array $settings = [];
    private const API_BASE = 'https://openapi.etsy.com/v3';

    // Rate limiting (Etsy default: 10 QPS, 10000 QPD)
    private int $requestCount = 0;
    private int $windowStart = 0;
    private const MAX_REQUESTS_PER_SECOND = 8; // Stay under 10 QPS limit

    // Required OAuth scopes for full functionality
    private const REQUIRED_SCOPES = [
        'listings_r',      // Read listings
        'listings_w',      // Write listings
        'listings_d',      // Delete listings
        'transactions_r',  // Read receipts/orders
        'shops_r',         // Read shop info
        'profile_r',       // Read user profile
    ];

    public function getSlug(): string { return 'etsy-sync'; }
    public function getName(): string { return 'Etsy Sync'; }
    public function getVersion(): string { return '1.1.0'; }
    public function getType(): string { return 'marketplace'; }
    public function getDescription(): string { return 'Sync products and orders with your Etsy shop via Open API v3'; }
    public function getAuthor(): string { return 'Apparix'; }
    public function getDefaultSettings(): array {
        return [
            'who_made' => 'i_did',
            'when_made' => 'made_to_order',
            'is_supply' => false
        ];
    }

    public function init(): void { $this->loadSettings(); }

    public function onActivate(): void
    {
        $this->createSyncTables();
        $this->log('Etsy Sync plugin activated (v' . $this->getVersion() . ')');
    }

    public function onDeactivate(): void { $this->log('Etsy Sync plugin deactivated'); }

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
        if (empty($settings['keystring'])) $errors[] = 'API Keystring is required';
        if (empty($settings['shared_secret'])) $errors[] = 'Shared Secret is required';
        if (empty($settings['access_token'])) $errors[] = 'Access Token is required';
        if (empty($settings['refresh_token'])) $errors[] = 'Refresh Token is required';
        if (empty($settings['shop_id'])) $errors[] = 'Shop ID is required';
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
        return !empty($this->settings['keystring'])
            && !empty($this->settings['access_token'])
            && !empty($this->settings['refresh_token'])
            && !empty($this->settings['shop_id']);
    }

    private function createSyncTables(): void
    {
        $db = Database::getInstance();

        $db->query("
            CREATE TABLE IF NOT EXISTS etsy_product_sync (
                id INT PRIMARY KEY AUTO_INCREMENT,
                product_id INT NOT NULL,
                variant_id INT NULL,
                etsy_listing_id BIGINT,
                etsy_sku VARCHAR(100),
                etsy_state VARCHAR(20) DEFAULT 'draft',
                last_synced_at TIMESTAMP NULL,
                sync_status ENUM('pending', 'synced', 'error') DEFAULT 'pending',
                error_message TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_product_variant (product_id, variant_id),
                INDEX idx_listing (etsy_listing_id),
                INDEX idx_sku (etsy_sku),
                INDEX idx_state (etsy_state)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS etsy_order_sync (
                id INT PRIMARY KEY AUTO_INCREMENT,
                order_id INT,
                etsy_receipt_id BIGINT NOT NULL,
                buyer_user_id BIGINT,
                order_status VARCHAR(50),
                order_total DECIMAL(10,2),
                imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_etsy_receipt (etsy_receipt_id),
                INDEX idx_order (order_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS etsy_sync_log (
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
     * Generate unique request ID for tracking
     */
    private function generateRequestId(): string
    {
        return 'etsy_' . bin2hex(random_bytes(8));
    }

    /**
     * Get valid access token, refreshing if necessary
     */
    private function getAccessToken(): ?string
    {
        // Check if token needs refresh (with 5 minute buffer)
        $tokenExpiry = $this->settings['token_expiry'] ?? 0;
        if ($tokenExpiry > 0 && ($tokenExpiry - 300) < time()) {
            if (!$this->refreshAccessToken()) {
                return null;
            }
        }
        return $this->settings['access_token'] ?? null;
    }

    /**
     * Refresh the OAuth access token
     */
    private function refreshAccessToken(): bool
    {
        $requestId = $this->generateRequestId();
        $refreshToken = $this->settings['refresh_token'] ?? null;

        if (!$refreshToken) {
            $this->log('No refresh token available', 'error', ['request_id' => $requestId]);
            return false;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.etsy.com/v3/public/oauth/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'refresh_token',
                'client_id' => $this->settings['keystring'],
                'refresh_token' => $refreshToken
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($curlErrno !== 0) {
            $this->log("Token refresh failed: {$curlError}", 'error', [
                'request_id' => $requestId,
                'curl_errno' => $curlErrno
            ]);
            return false;
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200 || !isset($data['access_token'])) {
            $error = $data['error_description'] ?? $data['error'] ?? 'Unknown error';
            $this->log("Token refresh failed (HTTP {$httpCode}): {$error}", 'error', [
                'request_id' => $requestId,
                'http_code' => $httpCode
            ]);
            return false;
        }

        // Update settings with new tokens
        $this->settings['access_token'] = $data['access_token'];
        $this->settings['refresh_token'] = $data['refresh_token'];
        $this->settings['token_expiry'] = time() + ($data['expires_in'] ?? 3600);

        // Save to database
        $db = Database::getInstance();
        $db->update(
            "UPDATE plugins SET settings = ? WHERE slug = ?",
            [json_encode($this->settings), $this->getSlug()]
        );

        $this->log('Access token refreshed successfully', 'success', ['request_id' => $requestId]);
        return true;
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

        $url = self::API_BASE . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'x-api-key: ' . $this->settings['keystring'],
            'Content-Type: application/json',
            'Accept: application/json'
        ];

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
                    // Etsy requires application/x-www-form-urlencoded for some endpoints
                    if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    }
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
                    $this->exponentialBackoff($attempt, 2);
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
                'details' => $responseData
            ];
        }

        return ['error' => true, 'message' => 'Max retries exceeded'];
    }

    /**
     * Extract user-friendly error message from Etsy response
     */
    private function extractErrorMessage(array $response, int $httpCode): string
    {
        if (isset($response['error_description'])) {
            return $response['error_description'];
        }

        if (isset($response['error'])) {
            return is_string($response['error']) ? $response['error'] : json_encode($response['error']);
        }

        if (isset($response['errors']) && is_array($response['errors'])) {
            $messages = [];
            foreach ($response['errors'] as $field => $errors) {
                if (is_array($errors)) {
                    $messages[] = "{$field}: " . implode(', ', $errors);
                } else {
                    $messages[] = $errors;
                }
            }
            if (!empty($messages)) {
                return implode('; ', $messages);
            }
        }

        return "HTTP {$httpCode} error";
    }

    /**
     * Enforce rate limiting (Etsy: 10 QPS max)
     */
    private function enforceRateLimit(): void
    {
        $now = microtime(true);

        // Reset counter every second
        if ($now - $this->windowStart >= 1.0) {
            $this->windowStart = $now;
            $this->requestCount = 0;
        }

        // If at limit, wait for next second
        if ($this->requestCount >= self::MAX_REQUESTS_PER_SECOND) {
            $waitTime = 1.0 - ($now - $this->windowStart);
            if ($waitTime > 0) {
                usleep((int)($waitTime * 1000000));
            }
            $this->windowStart = microtime(true);
            $this->requestCount = 0;
        }
    }

    /**
     * Exponential backoff for retries
     */
    private function exponentialBackoff(int $attempt, int $multiplier = 1): void
    {
        $delay = min(pow(2, $attempt) * $multiplier, 30);
        sleep($delay);
    }

    /**
     * Test connection to Etsy API
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Plugin not configured'];
        }

        $requestId = $this->generateRequestId();

        // Try to get shop info
        $shopId = $this->settings['shop_id'];
        $response = $this->apiRequest('GET', "/application/shops/{$shopId}");

        if (isset($response['error']) && $response['error']) {
            return [
                'success' => false,
                'error' => 'Failed to connect to Etsy: ' . ($response['message'] ?? 'Unknown error'),
                'request_id' => $requestId
            ];
        }

        if (!isset($response['shop_id'])) {
            return [
                'success' => false,
                'error' => 'Invalid response from Etsy API',
                'request_id' => $requestId
            ];
        }

        $this->log('Connection test successful', 'success', [
            'request_id' => $requestId,
            'shop_name' => $response['shop_name'] ?? 'Unknown'
        ]);

        return [
            'success' => true,
            'message' => 'Successfully connected to Etsy',
            'shop_name' => $response['shop_name'] ?? '',
            'shop_id' => $response['shop_id'],
            'currency' => $response['currency_code'] ?? 'USD',
            'listing_active_count' => $response['listing_active_count'] ?? 0,
            'request_id' => $requestId
        ];
    }

    /**
     * Get shop information
     */
    public function getShopInfo(): ?array
    {
        if (!$this->isConfigured()) return null;
        $response = $this->apiRequest('GET', "/application/shops/{$this->settings['shop_id']}");
        return (isset($response['error']) && $response['error']) ? null : $response;
    }

    /**
     * Get shipping profiles for the shop
     */
    public function getShippingProfiles(): array
    {
        if (!$this->isConfigured()) return [];
        $response = $this->apiRequest('GET', "/application/shops/{$this->settings['shop_id']}/shipping-profiles");
        return $response['results'] ?? [];
    }

    /**
     * Get return policies for the shop
     */
    public function getReturnPolicies(): array
    {
        if (!$this->isConfigured()) return [];
        $response = $this->apiRequest('GET', "/application/shops/{$this->settings['shop_id']}/policies/return");
        return $response['results'] ?? [];
    }

    /**
     * Get seller taxonomy nodes for category selection
     */
    public function getTaxonomyNodes(): array
    {
        $response = $this->apiRequest('GET', '/application/seller-taxonomy/nodes');
        return $response['results'] ?? [];
    }

    /**
     * Sync a product to Etsy
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
            "SELECT image_path FROM product_images WHERE product_id = ? ORDER BY sort_order LIMIT 10",
            [$productId]
        );

        // Check for existing sync record
        $syncRecord = $db->selectOne(
            "SELECT * FROM etsy_product_sync WHERE product_id = ? AND variant_id IS NULL",
            [$productId]
        );

        $shopId = $this->settings['shop_id'];
        $sku = $product['sku'] ?: 'APP-' . $productId;

        try {
            if ($syncRecord && $syncRecord['etsy_listing_id']) {
                // Update existing listing
                $result = $this->updateListing($syncRecord['etsy_listing_id'], $product, $sku, $requestId);
            } else {
                // Create new listing
                $result = $this->createListing($shopId, $product, $sku, $images, $requestId);
            }

            if (!$result['success']) {
                $this->updateSyncStatus($productId, null, 'error', $result['error']);
                return $result;
            }

            $listingId = $result['listing_id'];

            // Update sync record
            if ($syncRecord) {
                $db->update(
                    "UPDATE etsy_product_sync SET etsy_listing_id = ?, etsy_sku = ?, etsy_state = ?,
                     last_synced_at = NOW(), sync_status = 'synced', error_message = NULL WHERE id = ?",
                    [$listingId, $sku, $result['state'] ?? 'draft', $syncRecord['id']]
                );
            } else {
                $db->insert(
                    "INSERT INTO etsy_product_sync (product_id, etsy_listing_id, etsy_sku, etsy_state, last_synced_at, sync_status)
                     VALUES (?, ?, ?, ?, NOW(), 'synced')",
                    [$productId, $listingId, $sku, $result['state'] ?? 'draft']
                );
            }

            $this->log('Product synced successfully', 'success', [
                'request_id' => $requestId,
                'product_id' => $productId,
                'listing_id' => $listingId
            ]);

            return [
                'success' => true,
                'message' => 'Product synced to Etsy',
                'listing_id' => $listingId,
                'state' => $result['state'] ?? 'draft',
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
     * Create a new Etsy listing
     */
    private function createListing(string $shopId, array $product, string $sku, array $images, string $requestId): array
    {
        $price = (float)($product['sale_price'] ?: $product['price']);

        // Build listing data
        $listingData = [
            'quantity' => max(1, (int)($product['inventory_count'] ?? 1)),
            'title' => $this->sanitizeTitle($product['name']),
            'description' => $this->sanitizeDescription($product['description'] ?? ''),
            'price' => $price,
            'who_made' => $this->settings['who_made'] ?? 'i_did',
            'when_made' => $this->settings['when_made'] ?? 'made_to_order',
            'taxonomy_id' => (int)($this->settings['taxonomy_id'] ?? 0),
            'is_supply' => (bool)($this->settings['is_supply'] ?? false),
            'type' => 'physical'
        ];

        // Add shipping profile (required)
        if (!empty($this->settings['shipping_profile_id'])) {
            $listingData['shipping_profile_id'] = (int)$this->settings['shipping_profile_id'];
        }

        // Add return policy if set
        if (!empty($this->settings['return_policy_id'])) {
            $listingData['return_policy_id'] = (int)$this->settings['return_policy_id'];
        }

        // Add shop section if set
        if (!empty($this->settings['shop_section_id'])) {
            $listingData['shop_section_id'] = (int)$this->settings['shop_section_id'];
        }

        // Add tags if product has them
        if (!empty($product['meta_keywords'])) {
            $tags = array_slice(array_map('trim', explode(',', $product['meta_keywords'])), 0, 13);
            $listingData['tags'] = $tags;
        }

        // Create draft listing
        $response = $this->apiRequest('POST', "/application/shops/{$shopId}/listings", $listingData);

        if (isset($response['error']) && $response['error']) {
            return ['success' => false, 'error' => $response['message'] ?? 'Failed to create listing'];
        }

        if (!isset($response['listing_id'])) {
            return ['success' => false, 'error' => 'No listing ID returned'];
        }

        $listingId = $response['listing_id'];

        // Upload images if available
        if (!empty($images)) {
            $this->uploadListingImages($shopId, $listingId, $images, $requestId);
        }

        // Update inventory with SKU
        $this->updateListingInventory($listingId, $product, $sku, $requestId);

        return [
            'success' => true,
            'listing_id' => $listingId,
            'state' => $response['state'] ?? 'draft'
        ];
    }

    /**
     * Update an existing Etsy listing
     */
    private function updateListing(int $listingId, array $product, string $sku, string $requestId): array
    {
        $price = (float)($product['sale_price'] ?: $product['price']);

        $listingData = [
            'title' => $this->sanitizeTitle($product['name']),
            'description' => $this->sanitizeDescription($product['description'] ?? ''),
            'price' => $price
        ];

        // Add tags if product has them
        if (!empty($product['meta_keywords'])) {
            $tags = array_slice(array_map('trim', explode(',', $product['meta_keywords'])), 0, 13);
            $listingData['tags'] = $tags;
        }

        $response = $this->apiRequest('PATCH', "/application/listings/{$listingId}", $listingData);

        if (isset($response['error']) && $response['error']) {
            return ['success' => false, 'error' => $response['message'] ?? 'Failed to update listing'];
        }

        // Update inventory
        $this->updateListingInventory($listingId, $product, $sku, $requestId);

        return [
            'success' => true,
            'listing_id' => $listingId,
            'state' => $response['state'] ?? 'active'
        ];
    }

    /**
     * Update listing inventory
     */
    private function updateListingInventory(int $listingId, array $product, string $sku, string $requestId): bool
    {
        $price = (float)($product['sale_price'] ?: $product['price']);
        $quantity = max(0, (int)($product['inventory_count'] ?? 0));

        // Get current inventory to preserve structure
        $currentInventory = $this->apiRequest('GET', "/application/listings/{$listingId}/inventory");

        $products = [[
            'sku' => $sku,
            'offerings' => [[
                'price' => $price,
                'quantity' => $quantity,
                'is_enabled' => true
            ]]
        ]];

        $response = $this->apiRequest('PUT', "/application/listings/{$listingId}/inventory", [
            'products' => $products
        ]);

        if (isset($response['error']) && $response['error']) {
            $this->log("Failed to update inventory: " . ($response['message'] ?? 'Unknown error'), 'error', [
                'request_id' => $requestId,
                'listing_id' => $listingId
            ]);
            return false;
        }

        return true;
    }

    /**
     * Upload images to a listing
     */
    private function uploadListingImages(string $shopId, int $listingId, array $images, string $requestId): int
    {
        $uploaded = 0;
        $baseUrl = $this->getStoreUrl();

        foreach ($images as $index => $img) {
            $imagePath = $img['image_path'] ?? $img;
            $fullPath = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '/var/www/apparix.vibrixmedia.com/public', '/') .
                        '/assets/images/products/' . $imagePath;

            if (!file_exists($fullPath)) {
                continue;
            }

            // Etsy requires multipart/form-data for image uploads
            $ch = curl_init();
            $cfile = new \CURLFile($fullPath);

            curl_setopt_array($ch, [
                CURLOPT_URL => self::API_BASE . "/application/shops/{$shopId}/listings/{$listingId}/images",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->settings['access_token'],
                    'x-api-key: ' . $this->settings['keystring']
                ],
                CURLOPT_POSTFIELDS => [
                    'image' => $cfile,
                    'rank' => $index + 1
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $uploaded++;
            } else {
                $this->log("Failed to upload image: {$imagePath}", 'error', [
                    'request_id' => $requestId,
                    'listing_id' => $listingId,
                    'http_code' => $httpCode
                ]);
            }

            // Rate limit between image uploads
            usleep(200000); // 0.2 seconds
        }

        return $uploaded;
    }

    /**
     * Sync inventory for a product
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
            "SELECT * FROM etsy_product_sync WHERE product_id = ? AND variant_id IS NULL AND sync_status = 'synced'",
            [$productId]
        );

        if (!$syncRecord || !$syncRecord['etsy_listing_id']) {
            return ['success' => false, 'error' => 'Product not synced to Etsy yet'];
        }

        $listingId = $syncRecord['etsy_listing_id'];

        // Get current inventory
        $inventory = $this->apiRequest('GET', "/application/listings/{$listingId}/inventory");

        if (isset($inventory['error']) && $inventory['error']) {
            return ['success' => false, 'error' => 'Failed to fetch current inventory'];
        }

        // Update quantity in products array
        $products = $inventory['products'] ?? [];
        if (empty($products)) {
            $products = [[
                'sku' => $syncRecord['etsy_sku'] ?? '',
                'offerings' => [[
                    'quantity' => max(0, $quantity),
                    'is_enabled' => true
                ]]
            ]];
        } else {
            foreach ($products as &$product) {
                if (isset($product['offerings'])) {
                    foreach ($product['offerings'] as &$offering) {
                        $offering['quantity'] = max(0, $quantity);
                        // Remove read-only fields
                        unset($offering['offering_id'], $offering['is_deleted']);
                        // Convert price array to decimal
                        if (isset($offering['price']) && is_array($offering['price'])) {
                            $offering['price'] = (float)($offering['price']['amount'] ?? 0) /
                                                (int)($offering['price']['divisor'] ?? 100);
                        }
                    }
                }
                // Remove read-only fields
                unset($product['product_id'], $product['is_deleted']);
                if (isset($product['property_values'])) {
                    foreach ($product['property_values'] as &$pv) {
                        unset($pv['value_pairs']);
                    }
                }
            }
        }

        $response = $this->apiRequest('PUT', "/application/listings/{$listingId}/inventory", [
            'products' => $products
        ]);

        if (isset($response['error']) && $response['error']) {
            $this->log('Inventory sync failed', 'error', [
                'request_id' => $requestId,
                'product_id' => $productId,
                'listing_id' => $listingId,
                'error' => $response['message']
            ]);
            return ['success' => false, 'error' => $response['message'] ?? 'Failed to update inventory'];
        }

        $db->update(
            "UPDATE etsy_product_sync SET last_synced_at = NOW() WHERE id = ?",
            [$syncRecord['id']]
        );

        $this->log('Inventory synced', 'success', [
            'request_id' => $requestId,
            'product_id' => $productId,
            'listing_id' => $listingId,
            'quantity' => $quantity
        ]);

        return ['success' => true, 'message' => "Inventory updated to {$quantity}"];
    }

    /**
     * Import orders from Etsy
     */
    public function importOrders(?int $minCreated = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Plugin not configured'];
        }

        $requestId = $this->generateRequestId();
        $db = Database::getInstance();
        $shopId = $this->settings['shop_id'];

        // Default to last 24 hours
        $minCreated = $minCreated ?: strtotime('-24 hours');

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $offset = 0;
        $limit = 25;

        do {
            $response = $this->apiRequest('GET', "/application/shops/{$shopId}/receipts", [], [
                'min_created' => $minCreated,
                'was_paid' => 'true',
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
                    'error' => $response['message'] ?? 'Failed to fetch receipts',
                    'imported' => $imported,
                    'skipped' => $skipped
                ];
            }

            $receipts = $response['results'] ?? [];

            foreach ($receipts as $receipt) {
                $receiptId = $receipt['receipt_id'] ?? null;

                if (!$receiptId) {
                    continue;
                }

                // Check if already imported
                $existing = $db->selectOne(
                    "SELECT id FROM etsy_order_sync WHERE etsy_receipt_id = ?",
                    [$receiptId]
                );

                if ($existing) {
                    $skipped++;
                    continue;
                }

                try {
                    $orderId = $this->createOrderFromReceipt($receipt, $requestId);

                    if ($orderId) {
                        $db->insert(
                            "INSERT INTO etsy_order_sync (order_id, etsy_receipt_id, buyer_user_id, order_status, order_total)
                             VALUES (?, ?, ?, ?, ?)",
                            [
                                $orderId,
                                $receiptId,
                                $receipt['buyer_user_id'] ?? null,
                                $receipt['status'] ?? null,
                                $this->parseEtsyMoney($receipt['grandtotal'] ?? [])
                            ]
                        );
                        $imported++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Receipt {$receiptId}: " . $e->getMessage();
                    $this->log('Failed to import order', 'error', [
                        'request_id' => $requestId,
                        'receipt_id' => $receiptId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $offset += $limit;
            $totalCount = $response['count'] ?? 0;

        } while ($offset < $totalCount && !empty($receipts));

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
     * Create an Apparix order from an Etsy receipt
     */
    private function createOrderFromReceipt(array $receipt, string $requestId): ?int
    {
        $db = Database::getInstance();

        // Parse money values
        $subtotal = $this->parseEtsyMoney($receipt['subtotal'] ?? []);
        $shipping = $this->parseEtsyMoney($receipt['total_shipping_cost'] ?? []);
        $tax = $this->parseEtsyMoney($receipt['total_tax_cost'] ?? []);
        $total = $this->parseEtsyMoney($receipt['grandtotal'] ?? []);
        $discount = $this->parseEtsyMoney($receipt['discount_amt'] ?? []);

        // Map status
        $etsyStatus = $receipt['status'] ?? 'open';
        $status = match($etsyStatus) {
            'completed' => 'completed',
            'paid' => 'processing',
            'shipped' => 'shipped',
            default => 'pending'
        };

        // Build shipping address
        $shippingAddress = implode(', ', array_filter([
            $receipt['first_line'] ?? '',
            $receipt['second_line'] ?? '',
            $receipt['city'] ?? '',
            $receipt['state'] ?? '',
            $receipt['zip'] ?? ''
        ]));

        // Create the order
        $orderId = $db->insert(
            "INSERT INTO orders (customer_name, email, phone, shipping_address, billing_address,
             shipping_city, shipping_state, shipping_zip, shipping_country,
             subtotal, shipping_cost, tax, discount, total, status, payment_status,
             payment_method, payment_provider, notes, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', 'etsy', 'etsy', ?, ?)",
            [
                $receipt['name'] ?? 'Etsy Customer',
                $receipt['buyer_email'] ?? '',
                '',
                $shippingAddress,
                $shippingAddress,
                $receipt['city'] ?? '',
                $receipt['state'] ?? '',
                $receipt['zip'] ?? '',
                $receipt['country_iso'] ?? 'US',
                $subtotal,
                $shipping,
                $tax,
                $discount,
                $total,
                $status,
                'Imported from Etsy - Receipt ID: ' . ($receipt['receipt_id'] ?? ''),
                date('Y-m-d H:i:s', $receipt['created_timestamp'] ?? time())
            ]
        );

        // Import line items (transactions)
        $transactions = $receipt['transactions'] ?? [];
        foreach ($transactions as $transaction) {
            $itemPrice = $this->parseEtsyMoney($transaction['price'] ?? []);
            $itemQuantity = (int)($transaction['quantity'] ?? 1);

            // Try to find matching product by SKU
            $sku = $transaction['sku'] ?? '';
            $product = null;
            if ($sku) {
                $syncRecord = $db->selectOne(
                    "SELECT product_id FROM etsy_product_sync WHERE etsy_sku = ?",
                    [$sku]
                );
                if ($syncRecord) {
                    $product = $db->selectOne("SELECT id FROM products WHERE id = ?", [$syncRecord['product_id']]);
                }
            }

            $db->insert(
                "INSERT INTO order_items (order_id, product_id, product_name, sku, quantity, price, total)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $orderId,
                    $product['id'] ?? null,
                    $transaction['title'] ?? 'Etsy Item',
                    $sku,
                    $itemQuantity,
                    $itemPrice,
                    $itemPrice * $itemQuantity
                ]
            );
        }

        return $orderId;
    }

    /**
     * Parse Etsy money object to decimal
     */
    private function parseEtsyMoney(array $money): float
    {
        if (empty($money)) return 0.0;
        $amount = (float)($money['amount'] ?? 0);
        $divisor = (int)($money['divisor'] ?? 100);
        return $divisor > 0 ? $amount / $divisor : 0.0;
    }

    /**
     * Link an existing Etsy listing to a product
     */
    public function linkProduct(int $productId, int $etsyListingId): array
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

        // Verify Etsy listing exists
        $response = $this->apiRequest('GET', "/application/listings/{$etsyListingId}");

        if (isset($response['error']) && $response['error']) {
            return ['success' => false, 'error' => 'Etsy listing not found: ' . ($response['message'] ?? 'Unknown error')];
        }

        if (!isset($response['listing_id'])) {
            return ['success' => false, 'error' => 'Invalid Etsy listing'];
        }

        // Get inventory to find SKU
        $inventory = $this->apiRequest('GET', "/application/listings/{$etsyListingId}/inventory");
        $sku = '';
        if (isset($inventory['products'][0]['sku'])) {
            $sku = $inventory['products'][0]['sku'];
        }

        // Check if already linked
        $existing = $db->selectOne(
            "SELECT id FROM etsy_product_sync WHERE product_id = ? AND variant_id IS NULL",
            [$productId]
        );

        if ($existing) {
            $db->update(
                "UPDATE etsy_product_sync SET etsy_listing_id = ?, etsy_sku = ?, etsy_state = ?,
                 last_synced_at = NOW(), sync_status = 'synced', error_message = NULL WHERE id = ?",
                [$etsyListingId, $sku, $response['state'] ?? 'active', $existing['id']]
            );
        } else {
            $db->insert(
                "INSERT INTO etsy_product_sync (product_id, etsy_listing_id, etsy_sku, etsy_state, last_synced_at, sync_status)
                 VALUES (?, ?, ?, ?, NOW(), 'synced')",
                [$productId, $etsyListingId, $sku, $response['state'] ?? 'active']
            );
        }

        $this->log('Product linked to Etsy listing', 'success', [
            'request_id' => $requestId,
            'product_id' => $productId,
            'listing_id' => $etsyListingId
        ]);

        return [
            'success' => true,
            'message' => 'Product linked to Etsy listing',
            'listing_id' => $etsyListingId,
            'listing_title' => $response['title'] ?? '',
            'state' => $response['state'] ?? 'active',
            'request_id' => $requestId
        ];
    }

    /**
     * Unlink a product from Etsy
     */
    public function unlinkProduct(int $productId): array
    {
        $db = Database::getInstance();

        $deleted = $db->delete(
            "DELETE FROM etsy_product_sync WHERE product_id = ?",
            [$productId]
        );

        if ($deleted) {
            $this->log('Product unlinked from Etsy', 'success', ['product_id' => $productId]);
            return ['success' => true, 'message' => 'Product unlinked from Etsy'];
        }

        return ['success' => false, 'error' => 'Product was not linked to Etsy'];
    }

    /**
     * Get sync status for all products
     */
    public function getSyncStatus(): array
    {
        $db = Database::getInstance();
        return $db->select(
            "SELECT p.id, p.name, p.sku, p.inventory_count, eps.etsy_listing_id, eps.etsy_sku,
                    eps.etsy_state, eps.sync_status, eps.last_synced_at, eps.error_message
             FROM products p
             LEFT JOIN etsy_product_sync eps ON p.id = eps.product_id AND eps.variant_id IS NULL
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
            "SELECT * FROM etsy_sync_log ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );
    }

    /**
     * Update sync status for a product
     */
    private function updateSyncStatus(int $productId, ?int $variantId, string $status, ?string $errorMessage = null): void
    {
        $db = Database::getInstance();

        $existing = $db->selectOne(
            "SELECT id FROM etsy_product_sync WHERE product_id = ? AND variant_id " . ($variantId ? "= ?" : "IS NULL"),
            $variantId ? [$productId, $variantId] : [$productId]
        );

        if ($existing) {
            $db->update(
                "UPDATE etsy_product_sync SET sync_status = ?, error_message = ? WHERE id = ?",
                [$status, $errorMessage, $existing['id']]
            );
        } else {
            $db->insert(
                "INSERT INTO etsy_product_sync (product_id, variant_id, sync_status, error_message) VALUES (?, ?, ?, ?)",
                [$productId, $variantId, $status, $errorMessage]
            );
        }
    }

    /**
     * Sanitize title for Etsy (max 140 chars)
     */
    private function sanitizeTitle(string $title): string
    {
        $title = strip_tags($title);
        $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
        $title = preg_replace('/\s+/', ' ', trim($title));
        return substr($title, 0, 140);
    }

    /**
     * Sanitize description for Etsy
     */
    private function sanitizeDescription(string $description): string
    {
        // Etsy doesn't allow HTML in descriptions
        $description = strip_tags($description);
        $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');

        // Ensure minimum length
        if (strlen($description) < 10) {
            $description = "Thank you for visiting our shop! " . $description;
        }

        return $description;
    }

    /**
     * Get store URL for image paths
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
                "INSERT INTO etsy_sync_log (request_id, action, status, message, details) VALUES (?, ?, ?, ?, ?)",
                [
                    $details['request_id'] ?? $this->generateRequestId(),
                    debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
                    $status,
                    $message,
                    json_encode($details)
                ]
            );
        } catch (\Exception $e) {
            error_log("Etsy Sync log error: " . $e->getMessage());
        }
    }
}
