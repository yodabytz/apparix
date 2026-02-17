<?php

namespace App\Plugins;

use App\Core\Plugins\AbstractPaymentProvider;
use App\Core\Plugins\PaymentProviderInterface;

/**
 * PayPal Payment Provider Plugin
 *
 * Integrates PayPal Commerce Platform for accepting payments:
 * - PayPal Checkout buttons
 * - Pay Later / Pay in 4
 * - Venmo (US only)
 * - Credit/Debit cards via PayPal
 *
 * Uses PayPal REST API v2 with OAuth 2.0 authentication
 *
 * @version 1.1.0
 * @author Apparix
 */
class PayPalPlugin extends AbstractPaymentProvider implements PaymentProviderInterface
{
    private ?string $accessToken = null;
    private ?int $tokenExpiry = null;

    // Rate limiting
    private int $requestCount = 0;
    private float $windowStart = 0;
    private const MAX_REQUESTS_PER_SECOND = 30;

    public function getSlug(): string
    {
        return 'paypal';
    }

    public function getName(): string
    {
        return 'PayPal Payments';
    }

    public function getVersion(): string
    {
        return '1.1.0';
    }

    public function getDescription(): string
    {
        return 'Accept PayPal, Venmo, Pay Later, and credit/debit cards through PayPal Commerce Platform.';
    }

    public function getAuthor(): string
    {
        return 'Apparix';
    }

    public function getIcon(): string
    {
        return '/content/plugins/paypal/assets/paypal-logo.svg';
    }

    public function getCheckoutLabel(): string
    {
        return 'PayPal';
    }

    /**
     * Get PayPal API base URL based on mode
     */
    private function getApiBase(): string
    {
        $mode = $this->getSetting('mode', 'sandbox');
        return $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Generate unique request ID for idempotency and tracking
     */
    private function generateRequestId(): string
    {
        return 'paypal_' . bin2hex(random_bytes(12));
    }

    /**
     * Get OAuth access token for API calls
     */
    private function getAccessToken(): ?string
    {
        // Return cached token if still valid (with 60 second buffer)
        if ($this->accessToken && $this->tokenExpiry && (time() + 60) < $this->tokenExpiry) {
            return $this->accessToken;
        }

        $clientId = $this->getSetting('client_id', '');
        $clientSecret = $this->getSetting('client_secret', '');

        if (empty($clientId) || empty($clientSecret)) {
            $this->log('Missing client credentials', [], 'error');
            return null;
        }

        $requestId = $this->generateRequestId();

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->getApiBase() . '/v1/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $clientId . ':' . $clientSecret,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Language: en_US',
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        // Check for curl errors
        if ($curlErrno !== 0) {
            $this->log('Token request failed', [
                'request_id' => $requestId,
                'curl_error' => $curlError,
                'curl_errno' => $curlErrno
            ], 'error');
            return null;
        }

        if ($httpCode !== 200) {
            $this->log('Token request failed', [
                'request_id' => $requestId,
                'http_code' => $httpCode,
                'response' => $response
            ], 'error');
            return null;
        }

        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            $this->accessToken = $data['access_token'];
            // PayPal tokens typically expire in ~9 hours, but we refresh more often
            $this->tokenExpiry = time() + min($data['expires_in'] ?? 3600, 3600);
            return $this->accessToken;
        }

        $this->log('Invalid token response', [
            'request_id' => $requestId,
            'response' => $response
        ], 'error');

        return null;
    }

    /**
     * Enforce rate limiting
     */
    private function enforceRateLimit(): void
    {
        $now = microtime(true);

        if ($now - $this->windowStart >= 1.0) {
            $this->windowStart = $now;
            $this->requestCount = 0;
        }

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
    private function exponentialBackoff(int $attempt): void
    {
        $delay = min(pow(2, $attempt), 16);
        sleep($delay);
    }

    /**
     * Make an authenticated API request with retry logic
     */
    private function apiRequest(string $method, string $endpoint, ?array $data = null, int $retries = 3): ?array
    {
        $this->enforceRateLimit();

        $token = $this->getAccessToken();
        if (!$token) {
            return ['error' => true, 'message' => 'Failed to obtain access token'];
        }

        $requestId = $this->generateRequestId();

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            $ch = curl_init();
            $options = [
                CURLOPT_URL => $this->getApiBase() . $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                    'PayPal-Request-Id: ' . $requestId,
                    'Prefer: return=representation'
                ],
                CURLOPT_TIMEOUT => 60,
            ];

            switch (strtoupper($method)) {
                case 'POST':
                    $options[CURLOPT_POST] = true;
                    if ($data) {
                        $options[CURLOPT_POSTFIELDS] = json_encode($data);
                    }
                    break;
                case 'PATCH':
                    $options[CURLOPT_CUSTOMREQUEST] = 'PATCH';
                    if ($data) {
                        $options[CURLOPT_POSTFIELDS] = json_encode($data);
                    }
                    break;
                case 'PUT':
                    $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                    if ($data) {
                        $options[CURLOPT_POSTFIELDS] = json_encode($data);
                    }
                    break;
                case 'DELETE':
                    $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                    break;
                case 'GET':
                default:
                    // GET is default
                    break;
            }

            curl_setopt_array($ch, $options);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);

            $this->requestCount++;

            // Curl error - retry
            if ($curlErrno !== 0) {
                $this->log('API request curl error', [
                    'request_id' => $requestId,
                    'endpoint' => $endpoint,
                    'attempt' => $attempt,
                    'curl_error' => $curlError
                ], 'error');

                if ($attempt < $retries) {
                    $this->exponentialBackoff($attempt);
                    continue;
                }
                return ['error' => true, 'message' => 'Connection error: ' . $curlError];
            }

            $result = json_decode($response, true) ?? [];

            // Success
            if ($httpCode >= 200 && $httpCode < 300) {
                return $result;
            }

            // Rate limited - retry with backoff
            if ($httpCode === 429 && $attempt < $retries) {
                $this->log('Rate limited', [
                    'request_id' => $requestId,
                    'endpoint' => $endpoint,
                    'attempt' => $attempt
                ], 'error');
                $this->exponentialBackoff($attempt);
                continue;
            }

            // Server error - retry
            if ($httpCode >= 500 && $attempt < $retries) {
                $this->log('Server error', [
                    'request_id' => $requestId,
                    'endpoint' => $endpoint,
                    'http_code' => $httpCode,
                    'attempt' => $attempt
                ], 'error');
                $this->exponentialBackoff($attempt);
                continue;
            }

            // Client error or final attempt
            $errorMessage = $this->extractErrorMessage($result, $httpCode);
            $this->log('API request failed', [
                'request_id' => $requestId,
                'endpoint' => $endpoint,
                'http_code' => $httpCode,
                'error' => $errorMessage,
                'response' => $result
            ], 'error');

            return [
                'error' => true,
                'http_code' => $httpCode,
                'message' => $errorMessage,
                'details' => $result
            ];
        }

        return ['error' => true, 'message' => 'Max retries exceeded'];
    }

    /**
     * Extract user-friendly error message from PayPal response
     */
    private function extractErrorMessage(array $response, int $httpCode): string
    {
        if (isset($response['message'])) {
            return $response['message'];
        }

        if (isset($response['error_description'])) {
            return $response['error_description'];
        }

        if (isset($response['details']) && is_array($response['details'])) {
            $messages = [];
            foreach ($response['details'] as $detail) {
                if (isset($detail['description'])) {
                    $messages[] = $detail['description'];
                } elseif (isset($detail['issue'])) {
                    $messages[] = $detail['issue'];
                }
            }
            if (!empty($messages)) {
                return implode('; ', $messages);
            }
        }

        return "HTTP {$httpCode} error";
    }

    /**
     * Test connection to PayPal API
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'PayPal is not configured'];
        }

        $requestId = $this->generateRequestId();

        // Try to get an access token
        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'success' => false,
                'error' => 'Failed to authenticate with PayPal. Please check your Client ID and Client Secret.',
                'request_id' => $requestId
            ];
        }

        // Verify by getting merchant info
        $response = $this->apiRequest('GET', '/v1/identity/oauth2/userinfo?schema=paypalv1.1');

        if (isset($response['error']) && $response['error']) {
            return [
                'success' => false,
                'error' => 'Authentication successful but API access failed: ' . ($response['message'] ?? 'Unknown error'),
                'request_id' => $requestId
            ];
        }

        $this->log('Connection test successful', [
            'request_id' => $requestId,
            'mode' => $this->getSetting('mode', 'sandbox')
        ]);

        return [
            'success' => true,
            'message' => 'Successfully connected to PayPal (' . $this->getSetting('mode', 'sandbox') . ')',
            'payer_id' => $response['payer_id'] ?? null,
            'email' => $response['emails'][0]['value'] ?? null,
            'request_id' => $requestId
        ];
    }

    /**
     * Check if PayPal is properly configured
     */
    public function isConfigured(): bool
    {
        $clientId = $this->getSetting('client_id', '');
        $clientSecret = $this->getSetting('client_secret', '');

        return !empty($clientId) && !empty($clientSecret);
    }

    /**
     * Create a PayPal order for checkout
     */
    public function createPaymentSession(float $amount, string $currency, array $metadata = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'PayPal is not configured'];
        }

        $requestId = $this->generateRequestId();
        $currency = strtoupper($currency ?: $this->getSetting('currency', 'USD'));

        // Build purchase unit
        $purchaseUnit = [
            'amount' => [
                'currency_code' => $currency,
                'value' => number_format($amount, 2, '.', '')
            ],
            'reference_id' => $metadata['order_id'] ?? $requestId
        ];

        // Add item breakdown if provided
        if (!empty($metadata['items'])) {
            $itemTotal = 0;
            $items = [];
            foreach ($metadata['items'] as $item) {
                $itemAmount = number_format((float)($item['price'] ?? 0), 2, '.', '');
                $itemTotal += (float)$itemAmount * (int)($item['quantity'] ?? 1);
                $items[] = [
                    'name' => substr($item['name'] ?? 'Item', 0, 127),
                    'quantity' => (string)(int)($item['quantity'] ?? 1),
                    'unit_amount' => [
                        'currency_code' => $currency,
                        'value' => $itemAmount
                    ]
                ];
            }
            $purchaseUnit['items'] = $items;
            $purchaseUnit['amount']['breakdown'] = [
                'item_total' => [
                    'currency_code' => $currency,
                    'value' => number_format($itemTotal, 2, '.', '')
                ]
            ];

            // Add shipping if provided
            if (isset($metadata['shipping'])) {
                $purchaseUnit['amount']['breakdown']['shipping'] = [
                    'currency_code' => $currency,
                    'value' => number_format((float)$metadata['shipping'], 2, '.', '')
                ];
            }

            // Add tax if provided
            if (isset($metadata['tax'])) {
                $purchaseUnit['amount']['breakdown']['tax_total'] = [
                    'currency_code' => $currency,
                    'value' => number_format((float)$metadata['tax'], 2, '.', '')
                ];
            }
        }

        // Add description if provided
        if (!empty($metadata['description'])) {
            $purchaseUnit['description'] = substr($metadata['description'], 0, 127);
        }

        // Add shipping address if provided
        if (!empty($metadata['shipping_address'])) {
            $addr = $metadata['shipping_address'];
            $purchaseUnit['shipping'] = [
                'name' => [
                    'full_name' => $addr['name'] ?? ''
                ],
                'address' => [
                    'address_line_1' => $addr['line1'] ?? '',
                    'address_line_2' => $addr['line2'] ?? '',
                    'admin_area_2' => $addr['city'] ?? '',
                    'admin_area_1' => $addr['state'] ?? '',
                    'postal_code' => $addr['postal_code'] ?? '',
                    'country_code' => $addr['country'] ?? 'US'
                ]
            ];
        }

        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [$purchaseUnit],
            'application_context' => [
                'brand_name' => $metadata['store_name'] ?? 'Store',
                'landing_page' => 'NO_PREFERENCE',
                'shipping_preference' => !empty($metadata['shipping_address']) ? 'SET_PROVIDED_ADDRESS' : 'GET_FROM_FILE',
                'user_action' => 'PAY_NOW',
                'return_url' => $metadata['return_url'] ?? '',
                'cancel_url' => $metadata['cancel_url'] ?? ''
            ]
        ];

        $result = $this->apiRequest('POST', '/v2/checkout/orders', $orderData);

        if (isset($result['error']) && $result['error']) {
            return [
                'success' => false,
                'error' => $result['message'] ?? 'Failed to create PayPal order',
                'request_id' => $requestId
            ];
        }

        if (!isset($result['id'])) {
            return [
                'success' => false,
                'error' => 'No order ID returned from PayPal',
                'request_id' => $requestId
            ];
        }

        $this->log('PayPal order created', [
            'request_id' => $requestId,
            'order_id' => $result['id'],
            'amount' => $amount,
            'currency' => $currency
        ]);

        // Find approval URL
        $approvalUrl = null;
        foreach ($result['links'] ?? [] as $link) {
            if ($link['rel'] === 'approve') {
                $approvalUrl = $link['href'];
                break;
            }
        }

        return [
            'success' => true,
            'order_id' => $result['id'],
            'status' => $result['status'],
            'approval_url' => $approvalUrl,
            'request_id' => $requestId
        ];
    }

    /**
     * Capture an approved PayPal order
     */
    public function captureOrder(string $orderId): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'PayPal is not configured'];
        }

        $requestId = $this->generateRequestId();

        $result = $this->apiRequest('POST', "/v2/checkout/orders/{$orderId}/capture");

        if (isset($result['error']) && $result['error']) {
            return [
                'success' => false,
                'error' => $result['message'] ?? 'Failed to capture PayPal order',
                'request_id' => $requestId
            ];
        }

        if (($result['status'] ?? '') === 'COMPLETED') {
            $capture = $result['purchase_units'][0]['payments']['captures'][0] ?? null;

            $this->log('PayPal order captured', [
                'request_id' => $requestId,
                'order_id' => $orderId,
                'capture_id' => $capture['id'] ?? null,
                'amount' => $capture['amount']['value'] ?? 0
            ]);

            return [
                'success' => true,
                'status' => 'COMPLETED',
                'transaction_id' => $capture['id'] ?? $orderId,
                'amount' => $capture['amount']['value'] ?? 0,
                'currency' => $capture['amount']['currency_code'] ?? 'USD',
                'payer' => $result['payer'] ?? null,
                'request_id' => $requestId
            ];
        }

        return [
            'success' => false,
            'status' => $result['status'] ?? 'UNKNOWN',
            'error' => 'Order not completed',
            'request_id' => $requestId
        ];
    }

    /**
     * Get order details
     */
    public function getOrder(string $orderId): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'PayPal is not configured'];
        }

        $result = $this->apiRequest('GET', "/v2/checkout/orders/{$orderId}");

        if (isset($result['error']) && $result['error']) {
            return [
                'success' => false,
                'error' => $result['message'] ?? 'Failed to retrieve order'
            ];
        }

        return [
            'success' => true,
            'order' => $result
        ];
    }

    /**
     * Verify a payment was successful
     */
    public function verifyPayment(string $transactionId, float $expectedAmount): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'PayPal is not configured'];
        }

        $result = $this->apiRequest('GET', "/v2/checkout/orders/{$transactionId}");

        if (isset($result['error']) && $result['error']) {
            return [
                'success' => false,
                'error' => $result['message'] ?? 'Failed to retrieve PayPal order'
            ];
        }

        if (($result['status'] ?? '') !== 'COMPLETED') {
            return [
                'success' => false,
                'status' => $result['status'] ?? 'UNKNOWN',
                'error' => 'Order not completed'
            ];
        }

        $amount = (float)($result['purchase_units'][0]['amount']['value'] ?? 0);
        if (abs($amount - $expectedAmount) > 0.01) {
            return [
                'success' => false,
                'error' => 'Amount mismatch',
                'expected' => $expectedAmount,
                'received' => $amount
            ];
        }

        return [
            'success' => true,
            'status' => 'COMPLETED',
            'amount' => $amount,
            'currency' => $result['purchase_units'][0]['amount']['currency_code'] ?? 'USD'
        ];
    }

    /**
     * Get checkout HTML for PayPal buttons
     */
    public function getCheckoutHtml(float $amount, string $currency): string
    {
        return <<<HTML
<div id="paypal-payment-form" class="payment-form-content">
    <div id="paypal-button-container"></div>
    <div id="paypal-message-container"></div>
    <div id="paypal-card-errors" class="payment-error" role="alert"></div>
</div>
<input type="hidden" name="paypal_order_id" id="paypal_order_id" value="">
HTML;
    }

    /**
     * Get JavaScript needed for checkout
     */
    public function getCheckoutJs(): array
    {
        $clientId = $this->getSetting('client_id', '');
        $mode = $this->getSetting('mode', 'sandbox');
        $currency = $this->getSetting('currency', 'USD');
        $enableVenmo = $this->getSetting('enable_venmo', false);
        $enablePayLater = $this->getSetting('enable_pay_later', true);
        $disableFunding = $this->getSetting('disable_funding', '');

        $components = ['buttons'];
        if ($enablePayLater) {
            $components[] = 'messages';
        }

        $sdkUrl = 'https://www.paypal.com/sdk/js?client-id=' . urlencode($clientId)
            . '&currency=' . urlencode($currency)
            . '&components=' . implode(',', $components)
            . '&intent=capture';

        if ($enableVenmo) {
            $sdkUrl .= '&enable-funding=venmo';
        }

        if (!empty($disableFunding)) {
            $sdkUrl .= '&disable-funding=' . urlencode($disableFunding);
        }

        return [
            'external' => [$sdkUrl],
            'config' => [
                'clientId' => $clientId,
                'mode' => $mode,
                'currency' => $currency,
                'enableVenmo' => $enableVenmo,
                'enablePayLater' => $enablePayLater
            ]
        ];
    }

    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature(string $payload, array $headers): bool
    {
        $webhookId = $this->getSetting('webhook_id', '');
        if (empty($webhookId)) {
            $this->log('Webhook ID not configured, skipping verification', [], 'warning');
            return true; // Skip verification if webhook ID not set
        }

        // Extract required headers
        $transmissionId = $headers['PAYPAL-TRANSMISSION-ID'] ?? $headers['paypal-transmission-id'] ?? '';
        $transmissionTime = $headers['PAYPAL-TRANSMISSION-TIME'] ?? $headers['paypal-transmission-time'] ?? '';
        $transmissionSig = $headers['PAYPAL-TRANSMISSION-SIG'] ?? $headers['paypal-transmission-sig'] ?? '';
        $certUrl = $headers['PAYPAL-CERT-URL'] ?? $headers['paypal-cert-url'] ?? '';
        $authAlgo = $headers['PAYPAL-AUTH-ALGO'] ?? $headers['paypal-auth-algo'] ?? '';

        if (empty($transmissionId) || empty($transmissionSig)) {
            $this->log('Missing webhook signature headers', [], 'error');
            return false;
        }

        // Verify using PayPal's verification endpoint
        $verifyData = [
            'auth_algo' => $authAlgo,
            'cert_url' => $certUrl,
            'transmission_id' => $transmissionId,
            'transmission_sig' => $transmissionSig,
            'transmission_time' => $transmissionTime,
            'webhook_id' => $webhookId,
            'webhook_event' => json_decode($payload, true)
        ];

        $result = $this->apiRequest('POST', '/v1/notifications/verify-webhook-signature', $verifyData);

        if (isset($result['error']) && $result['error']) {
            $this->log('Webhook verification API call failed', [
                'error' => $result['message'] ?? 'Unknown error'
            ], 'error');
            return false;
        }

        $verificationStatus = $result['verification_status'] ?? '';

        if ($verificationStatus !== 'SUCCESS') {
            $this->log('Webhook signature verification failed', [
                'status' => $verificationStatus
            ], 'error');
            return false;
        }

        return true;
    }

    /**
     * Handle PayPal webhook
     */
    public function handleWebhook(string $payload, array $headers): array
    {
        // Verify webhook signature
        if (!$this->verifyWebhookSignature($payload, $headers)) {
            return [
                'success' => false,
                'error' => 'Webhook signature verification failed'
            ];
        }

        $data = json_decode($payload, true);
        if (!$data) {
            return ['success' => false, 'error' => 'Invalid payload'];
        }

        $eventType = $data['event_type'] ?? '';
        $eventId = $data['id'] ?? null;

        $this->log('Webhook received', [
            'type' => $eventType,
            'event_id' => $eventId
        ]);

        // Handle different event types
        switch ($eventType) {
            case 'PAYMENT.CAPTURE.COMPLETED':
                $resource = $data['resource'] ?? [];
                return [
                    'success' => true,
                    'event' => 'payment_completed',
                    'event_id' => $eventId,
                    'transaction_id' => $resource['id'] ?? null,
                    'amount' => $resource['amount']['value'] ?? 0,
                    'currency' => $resource['amount']['currency_code'] ?? 'USD',
                    'status' => 'completed'
                ];

            case 'PAYMENT.CAPTURE.REFUNDED':
                $resource = $data['resource'] ?? [];
                return [
                    'success' => true,
                    'event' => 'refund_completed',
                    'event_id' => $eventId,
                    'refund_id' => $resource['id'] ?? null,
                    'amount' => $resource['amount']['value'] ?? 0,
                    'currency' => $resource['amount']['currency_code'] ?? 'USD'
                ];

            case 'PAYMENT.CAPTURE.DENIED':
            case 'PAYMENT.CAPTURE.DECLINED':
                $resource = $data['resource'] ?? [];
                return [
                    'success' => true,
                    'event' => 'payment_failed',
                    'event_id' => $eventId,
                    'transaction_id' => $resource['id'] ?? null,
                    'reason' => $resource['status_details']['reason'] ?? 'Unknown'
                ];

            case 'PAYMENT.CAPTURE.PENDING':
                $resource = $data['resource'] ?? [];
                return [
                    'success' => true,
                    'event' => 'payment_pending',
                    'event_id' => $eventId,
                    'transaction_id' => $resource['id'] ?? null,
                    'reason' => $resource['status_details']['reason'] ?? 'Unknown'
                ];

            case 'CHECKOUT.ORDER.APPROVED':
                $resource = $data['resource'] ?? [];
                return [
                    'success' => true,
                    'event' => 'order_approved',
                    'event_id' => $eventId,
                    'order_id' => $resource['id'] ?? null
                ];

            default:
                return [
                    'success' => true,
                    'event' => $eventType,
                    'event_id' => $eventId,
                    'data' => $data['resource'] ?? []
                ];
        }
    }

    /**
     * Process a refund
     */
    public function processRefund(string $transactionId, ?float $amount = null, ?string $currency = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'PayPal is not configured'];
        }

        $requestId = $this->generateRequestId();
        $currency = $currency ?: $this->getSetting('currency', 'USD');

        $refundData = [];
        if ($amount !== null) {
            $refundData['amount'] = [
                'value' => number_format($amount, 2, '.', ''),
                'currency_code' => strtoupper($currency)
            ];
        }

        // Add note for record
        $refundData['note_to_payer'] = 'Refund processed by ' . ($this->getSetting('store_name', 'Store'));

        $result = $this->apiRequest(
            'POST',
            "/v2/payments/captures/{$transactionId}/refund",
            empty($refundData) ? null : $refundData
        );

        if (isset($result['error']) && $result['error']) {
            return [
                'success' => false,
                'error' => $result['message'] ?? 'Failed to process refund',
                'request_id' => $requestId
            ];
        }

        $status = $result['status'] ?? '';

        if ($status === 'COMPLETED') {
            $this->log('Refund processed', [
                'request_id' => $requestId,
                'refund_id' => $result['id'] ?? null,
                'amount' => $amount,
                'capture_id' => $transactionId
            ]);

            return [
                'success' => true,
                'refund_id' => $result['id'] ?? null,
                'amount' => $result['amount']['value'] ?? $amount,
                'currency' => $result['amount']['currency_code'] ?? $currency,
                'status' => 'completed',
                'request_id' => $requestId
            ];
        }

        if ($status === 'PENDING') {
            return [
                'success' => true,
                'refund_id' => $result['id'] ?? null,
                'amount' => $result['amount']['value'] ?? $amount,
                'status' => 'pending',
                'reason' => $result['status_details']['reason'] ?? 'Processing',
                'request_id' => $requestId
            ];
        }

        return [
            'success' => false,
            'status' => $status,
            'error' => 'Refund not completed',
            'request_id' => $requestId
        ];
    }

    /**
     * Get default settings
     */
    public function getDefaultSettings(): array
    {
        return [
            'mode' => 'sandbox',
            'client_id' => '',
            'client_secret' => '',
            'webhook_id' => '',
            'currency' => 'USD',
            'enable_venmo' => false,
            'enable_pay_later' => true,
            'disable_funding' => ''
        ];
    }

    /**
     * Get settings schema
     */
    public function getSettingsSchema(): array
    {
        return [
            [
                'key' => 'mode',
                'label' => 'Mode',
                'type' => 'select',
                'options' => [
                    'sandbox' => 'Sandbox (Testing)',
                    'live' => 'Live (Production)'
                ],
                'default' => 'sandbox',
                'help' => 'Use sandbox for testing, live for real payments'
            ],
            [
                'key' => 'client_id',
                'label' => 'Client ID',
                'type' => 'text',
                'required' => true,
                'help' => 'PayPal REST API Client ID from Developer Dashboard'
            ],
            [
                'key' => 'client_secret',
                'label' => 'Client Secret',
                'type' => 'password',
                'required' => true,
                'help' => 'PayPal REST API Client Secret from Developer Dashboard'
            ],
            [
                'key' => 'webhook_id',
                'label' => 'Webhook ID',
                'type' => 'text',
                'required' => false,
                'help' => 'Webhook ID for signature verification (from Developer Dashboard > Webhooks)'
            ],
            [
                'key' => 'currency',
                'label' => 'Currency',
                'type' => 'select',
                'options' => [
                    'USD' => 'US Dollar (USD)',
                    'EUR' => 'Euro (EUR)',
                    'GBP' => 'British Pound (GBP)',
                    'CAD' => 'Canadian Dollar (CAD)',
                    'AUD' => 'Australian Dollar (AUD)',
                    'JPY' => 'Japanese Yen (JPY)',
                    'CNY' => 'Chinese Yuan (CNY)',
                    'CHF' => 'Swiss Franc (CHF)',
                    'HKD' => 'Hong Kong Dollar (HKD)',
                    'SGD' => 'Singapore Dollar (SGD)',
                    'MXN' => 'Mexican Peso (MXN)',
                    'BRL' => 'Brazilian Real (BRL)'
                ],
                'default' => 'USD',
                'help' => 'Default currency for transactions'
            ],
            [
                'key' => 'enable_venmo',
                'label' => 'Enable Venmo',
                'type' => 'checkbox',
                'default' => false,
                'help' => 'Allow customers to pay with Venmo (US only)'
            ],
            [
                'key' => 'enable_pay_later',
                'label' => 'Enable Pay Later',
                'type' => 'checkbox',
                'default' => true,
                'help' => 'Show Pay Later / Pay in 4 options at checkout'
            ],
            [
                'key' => 'disable_funding',
                'label' => 'Disable Funding Sources',
                'type' => 'text',
                'required' => false,
                'help' => 'Comma-separated list of funding sources to disable (e.g., credit,card,paylater)'
            ]
        ];
    }

    /**
     * Get supported features
     */
    public function getSupportedFeatures(): array
    {
        return [
            'refunds' => true,
            'partial_refunds' => true,
            'webhooks' => true,
            'webhook_verification' => true,
            'venmo' => true,
            'pay_later' => true,
            'guest_checkout' => true,
            'multi_currency' => true,
            'connection_test' => true
        ];
    }
}
