<?php

namespace App\Plugins;

use App\Core\Plugins\AbstractPaymentProvider;
use App\Core\Plugins\PaymentProviderInterface;

/**
 * Square Payment Provider Plugin
 *
 * Integrates Square Web Payments SDK for accepting payments
 */
class SquarePlugin extends AbstractPaymentProvider implements PaymentProviderInterface
{
    public function getSlug(): string
    {
        return 'square';
    }

    public function getName(): string
    {
        return 'Square Payments';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Accept credit cards, Apple Pay, Google Pay, and Cash App Pay through Square.';
    }

    public function getAuthor(): string
    {
        return 'Apparix';
    }

    public function getIcon(): string
    {
        return '/content/plugins/square/assets/square-logo.svg';
    }

    public function getCheckoutLabel(): string
    {
        return 'Credit Card (Square)';
    }

    /**
     * Get Square API base URL based on mode
     */
    private function getApiBase(): string
    {
        $mode = $this->getSetting('mode', 'sandbox');
        return $mode === 'production'
            ? 'https://connect.squareup.com'
            : 'https://connect.squareupsandbox.com';
    }

    /**
     * Get Web Payments SDK URL based on mode
     */
    private function getSdkUrl(): string
    {
        $mode = $this->getSetting('mode', 'sandbox');
        return $mode === 'production'
            ? 'https://web.squarecdn.com/v1/square.js'
            : 'https://sandbox.web.squarecdn.com/v1/square.js';
    }

    /**
     * Make an authenticated API request
     */
    private function apiRequest(string $method, string $endpoint, ?array $data = null): ?array
    {
        $accessToken = $this->getSetting('access_token', '');

        if (empty($accessToken)) {
            return null;
        }

        $ch = curl_init();
        $options = [
            CURLOPT_URL => $this->getApiBase() . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
                'Square-Version: 2024-01-18'
            ],
            CURLOPT_TIMEOUT => 30,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            if ($data) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return $result;
        }

        $this->log('API request failed', [
            'endpoint' => $endpoint,
            'http_code' => $httpCode,
            'response' => $result
        ]);

        return null;
    }

    /**
     * Check if Square is properly configured
     */
    public function isConfigured(): bool
    {
        $applicationId = $this->getSetting('application_id', '');
        $accessToken = $this->getSetting('access_token', '');
        $locationId = $this->getSetting('location_id', '');

        return !empty($applicationId) && !empty($accessToken) && !empty($locationId);
    }

    /**
     * Create a payment (after client-side tokenization)
     */
    public function createPaymentSession(float $amount, string $currency, array $metadata = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Square is not configured'];
        }

        // For Square, we don't create a session upfront - we need the source_id from tokenization
        // This method is called when we have a token from the client
        $sourceId = $metadata['source_id'] ?? null;
        if (!$sourceId) {
            // Return configuration for client-side tokenization
            return [
                'success' => true,
                'requires_tokenization' => true,
                'application_id' => $this->getSetting('application_id'),
                'location_id' => $this->getSetting('location_id')
            ];
        }

        $locationId = $this->getSetting('location_id');
        $idempotencyKey = $metadata['idempotency_key'] ?? uniqid('sq_', true);

        $paymentData = [
            'source_id' => $sourceId,
            'idempotency_key' => $idempotencyKey,
            'amount_money' => [
                'amount' => $this->toCents($amount),
                'currency' => strtoupper($currency)
            ],
            'location_id' => $locationId,
            'autocomplete' => true
        ];

        // Add order reference if provided
        if (isset($metadata['order_id'])) {
            $paymentData['reference_id'] = (string) $metadata['order_id'];
        }

        // Add buyer email if provided
        if (isset($metadata['email'])) {
            $paymentData['buyer_email_address'] = $metadata['email'];
        }

        $result = $this->apiRequest('POST', '/v2/payments', $paymentData);

        if (!$result || !isset($result['payment'])) {
            $errorMessage = 'Failed to create payment';
            if (isset($result['errors'][0]['detail'])) {
                $errorMessage = $result['errors'][0]['detail'];
            }
            return [
                'success' => false,
                'error' => $errorMessage
            ];
        }

        $payment = $result['payment'];

        $this->log('Payment created', [
            'payment_id' => $payment['id'],
            'amount' => $amount
        ]);

        return [
            'success' => true,
            'payment_id' => $payment['id'],
            'status' => $payment['status'],
            'receipt_url' => $payment['receipt_url'] ?? null
        ];
    }

    /**
     * Verify a payment was successful
     */
    public function verifyPayment(string $transactionId, float $expectedAmount): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Square is not configured'];
        }

        $result = $this->apiRequest('GET', "/v2/payments/{$transactionId}");

        if (!$result || !isset($result['payment'])) {
            return [
                'success' => false,
                'error' => 'Failed to retrieve payment'
            ];
        }

        $payment = $result['payment'];

        if ($payment['status'] !== 'COMPLETED') {
            return [
                'success' => false,
                'status' => $payment['status'],
                'error' => 'Payment not completed'
            ];
        }

        $paidAmount = $this->fromCents($payment['amount_money']['amount']);
        if (abs($paidAmount - $expectedAmount) > 0.01) {
            return [
                'success' => false,
                'error' => 'Amount mismatch',
                'expected' => $expectedAmount,
                'received' => $paidAmount
            ];
        }

        return [
            'success' => true,
            'status' => 'COMPLETED',
            'amount' => $paidAmount,
            'currency' => $payment['amount_money']['currency'],
            'receipt_url' => $payment['receipt_url'] ?? null
        ];
    }

    /**
     * Get checkout HTML for Square card form
     */
    public function getCheckoutHtml(float $amount, string $currency): string
    {
        return <<<HTML
<div id="square-payment-form" class="payment-form-content">
    <div id="square-card-container"></div>
    <div id="square-card-errors" class="payment-error" role="alert"></div>
</div>
<input type="hidden" name="square_source_id" id="square_source_id" value="">
HTML;
    }

    /**
     * Get JavaScript needed for checkout
     */
    public function getCheckoutJs(): array
    {
        return [
            'external' => [$this->getSdkUrl()],
            'config' => [
                'applicationId' => $this->getSetting('application_id'),
                'locationId' => $this->getSetting('location_id'),
                'mode' => $this->getSetting('mode', 'sandbox'),
                'enableApplePay' => $this->getSetting('enable_apple_pay', false),
                'enableGooglePay' => $this->getSetting('enable_google_pay', false),
                'enableCashApp' => $this->getSetting('enable_cash_app', false)
            ]
        ];
    }

    /**
     * Handle Square webhook
     */
    public function handleWebhook(string $payload, array $headers): array
    {
        $data = json_decode($payload, true);
        if (!$data) {
            return ['success' => false, 'error' => 'Invalid payload'];
        }

        $eventType = $data['type'] ?? '';

        $this->log('Webhook received', [
            'type' => $eventType,
            'id' => $data['event_id'] ?? null
        ]);

        // Handle different event types
        switch ($eventType) {
            case 'payment.completed':
                $payment = $data['data']['object']['payment'] ?? [];
                return [
                    'success' => true,
                    'event' => 'payment_completed',
                    'transaction_id' => $payment['id'] ?? null,
                    'amount' => isset($payment['amount_money']['amount'])
                        ? $this->fromCents($payment['amount_money']['amount'])
                        : 0,
                    'status' => 'completed'
                ];

            case 'refund.created':
            case 'refund.updated':
                $refund = $data['data']['object']['refund'] ?? [];
                return [
                    'success' => true,
                    'event' => 'refund_' . ($refund['status'] ?? 'unknown'),
                    'refund_id' => $refund['id'] ?? null,
                    'amount' => isset($refund['amount_money']['amount'])
                        ? $this->fromCents($refund['amount_money']['amount'])
                        : 0
                ];

            case 'payment.failed':
                $payment = $data['data']['object']['payment'] ?? [];
                return [
                    'success' => true,
                    'event' => 'payment_failed',
                    'data' => $payment
                ];

            default:
                return [
                    'success' => true,
                    'event' => $eventType,
                    'data' => $data['data']['object'] ?? []
                ];
        }
    }

    /**
     * Process a refund
     */
    public function processRefund(string $transactionId, ?float $amount = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Square is not configured'];
        }

        $refundData = [
            'idempotency_key' => uniqid('refund_', true),
            'payment_id' => $transactionId
        ];

        if ($amount !== null) {
            $refundData['amount_money'] = [
                'amount' => $this->toCents($amount),
                'currency' => 'USD'
            ];
        }

        $result = $this->apiRequest('POST', '/v2/refunds', $refundData);

        if (!$result || !isset($result['refund'])) {
            $errorMessage = 'Failed to process refund';
            if (isset($result['errors'][0]['detail'])) {
                $errorMessage = $result['errors'][0]['detail'];
            }
            return [
                'success' => false,
                'error' => $errorMessage
            ];
        }

        $refund = $result['refund'];

        $this->log('Refund created', [
            'refund_id' => $refund['id'],
            'amount' => $amount
        ]);

        return [
            'success' => true,
            'refund_id' => $refund['id'],
            'amount' => $this->fromCents($refund['amount_money']['amount']),
            'status' => strtolower($refund['status'])
        ];
    }

    /**
     * Get default settings
     */
    public function getDefaultSettings(): array
    {
        return [
            'mode' => 'sandbox',
            'application_id' => '',
            'access_token' => '',
            'location_id' => '',
            'enable_apple_pay' => false,
            'enable_google_pay' => false,
            'enable_cash_app' => false
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
                'options' => ['sandbox', 'production'],
                'default' => 'sandbox',
                'help' => 'Use sandbox mode for testing, production mode for live payments'
            ],
            [
                'key' => 'application_id',
                'label' => 'Application ID',
                'type' => 'text',
                'required' => true,
                'help' => 'Your Square Application ID from the Developer Dashboard'
            ],
            [
                'key' => 'access_token',
                'label' => 'Access Token',
                'type' => 'password',
                'required' => true,
                'help' => 'Your Square Access Token (sandbox or production)'
            ],
            [
                'key' => 'location_id',
                'label' => 'Location ID',
                'type' => 'text',
                'required' => true,
                'help' => 'Your Square Location ID for processing payments'
            ],
            [
                'key' => 'enable_apple_pay',
                'label' => 'Enable Apple Pay',
                'type' => 'checkbox',
                'default' => false,
                'help' => 'Allow customers to pay with Apple Pay (requires domain verification)'
            ],
            [
                'key' => 'enable_google_pay',
                'label' => 'Enable Google Pay',
                'type' => 'checkbox',
                'default' => false,
                'help' => 'Allow customers to pay with Google Pay'
            ],
            [
                'key' => 'enable_cash_app',
                'label' => 'Enable Cash App Pay',
                'type' => 'checkbox',
                'default' => false,
                'help' => 'Allow customers to pay with Cash App Pay (US only)'
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
            'apple_pay' => true,
            'google_pay' => true,
            'cash_app' => true,
            'tokenization' => true
        ];
    }
}
