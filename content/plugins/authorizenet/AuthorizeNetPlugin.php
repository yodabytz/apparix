<?php

namespace App\Plugins;

use App\Core\Plugins\AbstractPaymentProvider;
use App\Core\Plugins\PaymentProviderInterface;

/**
 * Authorize.net Payment Provider Plugin
 *
 * Integrates Authorize.net Accept.js for accepting payments
 */
class AuthorizeNetPlugin extends AbstractPaymentProvider implements PaymentProviderInterface
{
    public function getSlug(): string
    {
        return 'authorizenet';
    }

    public function getName(): string
    {
        return 'Authorize.net';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Accept credit cards and eChecks through Authorize.net payment gateway.';
    }

    public function getAuthor(): string
    {
        return 'Apparix';
    }

    public function getIcon(): string
    {
        return '/content/plugins/authorizenet/assets/authorizenet-logo.svg';
    }

    public function getCheckoutLabel(): string
    {
        return 'Credit Card';
    }

    /**
     * Get Authorize.net API endpoint based on mode
     */
    private function getApiEndpoint(): string
    {
        $mode = $this->getSetting('mode', 'sandbox');
        return $mode === 'production'
            ? 'https://api.authorize.net/xml/v1/request.api'
            : 'https://apitest.authorize.net/xml/v1/request.api';
    }

    /**
     * Get Accept.js URL based on mode
     */
    private function getAcceptJsUrl(): string
    {
        $mode = $this->getSetting('mode', 'sandbox');
        return $mode === 'production'
            ? 'https://js.authorize.net/v1/Accept.js'
            : 'https://jstest.authorize.net/v1/Accept.js';
    }

    /**
     * Make an API request to Authorize.net
     */
    private function apiRequest(array $request): ?array
    {
        $apiLoginId = $this->getSetting('api_login_id', '');
        $transactionKey = $this->getSetting('transaction_key', '');

        if (empty($apiLoginId) || empty($transactionKey)) {
            return null;
        }

        // Add merchant authentication
        $request['merchantAuthentication'] = [
            'name' => $apiLoginId,
            'transactionKey' => $transactionKey
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->getApiEndpoint(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($request),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Authorize.net returns JSON with BOM, so strip it
        $response = preg_replace('/^\xEF\xBB\xBF/', '', $response);
        $result = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && $result) {
            return $result;
        }

        $this->log('API request failed', [
            'http_code' => $httpCode,
            'response' => $result
        ]);

        return null;
    }

    /**
     * Check if Authorize.net is properly configured
     */
    public function isConfigured(): bool
    {
        $apiLoginId = $this->getSetting('api_login_id', '');
        $transactionKey = $this->getSetting('transaction_key', '');
        $publicClientKey = $this->getSetting('public_client_key', '');

        return !empty($apiLoginId) && !empty($transactionKey) && !empty($publicClientKey);
    }

    /**
     * Create a payment using token from Accept.js
     */
    public function createPaymentSession(float $amount, string $currency, array $metadata = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Authorize.net is not configured'];
        }

        // Check if we have a token from Accept.js
        $dataDescriptor = $metadata['data_descriptor'] ?? null;
        $dataValue = $metadata['data_value'] ?? null;

        if (!$dataDescriptor || !$dataValue) {
            // Return configuration for client-side tokenization
            return [
                'success' => true,
                'requires_tokenization' => true,
                'api_login_id' => $this->getSetting('api_login_id'),
                'public_client_key' => $this->getSetting('public_client_key')
            ];
        }

        // Create transaction request
        $request = [
            'createTransactionRequest' => [
                'merchantAuthentication' => [], // Will be added by apiRequest
                'refId' => $metadata['order_id'] ?? uniqid('order_'),
                'transactionRequest' => [
                    'transactionType' => 'authCaptureTransaction',
                    'amount' => number_format($amount, 2, '.', ''),
                    'payment' => [
                        'opaqueData' => [
                            'dataDescriptor' => $dataDescriptor,
                            'dataValue' => $dataValue
                        ]
                    ]
                ]
            ]
        ];

        // Add order info if provided
        if (isset($metadata['order_id'])) {
            $request['createTransactionRequest']['transactionRequest']['order'] = [
                'invoiceNumber' => (string) $metadata['order_id']
            ];
        }

        // Add customer email if provided
        if (isset($metadata['email'])) {
            $request['createTransactionRequest']['transactionRequest']['customer'] = [
                'email' => $metadata['email']
            ];
        }

        // Add billing address if provided
        if (isset($metadata['billing'])) {
            $billing = $metadata['billing'];
            $request['createTransactionRequest']['transactionRequest']['billTo'] = [
                'firstName' => $billing['first_name'] ?? '',
                'lastName' => $billing['last_name'] ?? '',
                'address' => $billing['address'] ?? '',
                'city' => $billing['city'] ?? '',
                'state' => $billing['state'] ?? '',
                'zip' => $billing['zip'] ?? '',
                'country' => $billing['country'] ?? 'US'
            ];
        }

        $result = $this->apiRequest($request['createTransactionRequest']);

        if (!$result) {
            return [
                'success' => false,
                'error' => 'Failed to connect to Authorize.net'
            ];
        }

        // Check response
        $messages = $result['messages'] ?? [];
        $transactionResponse = $result['transactionResponse'] ?? [];

        if ($messages['resultCode'] !== 'Ok') {
            $errorMessage = $messages['message'][0]['text'] ?? 'Transaction failed';
            if (isset($transactionResponse['errors'])) {
                $errorMessage = $transactionResponse['errors'][0]['errorText'] ?? $errorMessage;
            }
            return [
                'success' => false,
                'error' => $errorMessage
            ];
        }

        $responseCode = $transactionResponse['responseCode'] ?? '';

        if ($responseCode === '1') {
            // Approved
            $transId = $transactionResponse['transId'] ?? '';

            $this->log('Payment approved', [
                'trans_id' => $transId,
                'amount' => $amount
            ]);

            return [
                'success' => true,
                'transaction_id' => $transId,
                'auth_code' => $transactionResponse['authCode'] ?? '',
                'status' => 'approved'
            ];
        } elseif ($responseCode === '2') {
            // Declined
            return [
                'success' => false,
                'error' => 'Card declined',
                'status' => 'declined'
            ];
        } elseif ($responseCode === '4') {
            // Held for review
            return [
                'success' => true,
                'transaction_id' => $transactionResponse['transId'] ?? '',
                'status' => 'held_for_review',
                'requires_review' => true
            ];
        }

        return [
            'success' => false,
            'error' => 'Unknown response code: ' . $responseCode
        ];
    }

    /**
     * Verify a transaction
     */
    public function verifyPayment(string $transactionId, float $expectedAmount): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Authorize.net is not configured'];
        }

        $request = [
            'getTransactionDetailsRequest' => [
                'merchantAuthentication' => [],
                'transId' => $transactionId
            ]
        ];

        $result = $this->apiRequest($request['getTransactionDetailsRequest']);

        if (!$result || ($result['messages']['resultCode'] ?? '') !== 'Ok') {
            return [
                'success' => false,
                'error' => 'Failed to retrieve transaction'
            ];
        }

        $transaction = $result['transaction'] ?? [];
        $status = $transaction['transactionStatus'] ?? '';

        // Check if transaction is settled or captured
        $validStatuses = ['settledSuccessfully', 'capturedPendingSettlement', 'authorizedPendingCapture'];

        if (!in_array($status, $validStatuses)) {
            return [
                'success' => false,
                'status' => $status,
                'error' => 'Transaction not completed'
            ];
        }

        $settleAmount = (float) ($transaction['settleAmount'] ?? 0);
        if (abs($settleAmount - $expectedAmount) > 0.01) {
            return [
                'success' => false,
                'error' => 'Amount mismatch',
                'expected' => $expectedAmount,
                'received' => $settleAmount
            ];
        }

        return [
            'success' => true,
            'status' => $status,
            'amount' => $settleAmount,
            'auth_code' => $transaction['authCode'] ?? ''
        ];
    }

    /**
     * Get checkout HTML for Accept.js hosted form
     */
    public function getCheckoutHtml(float $amount, string $currency): string
    {
        return <<<HTML
<div id="authorizenet-payment-form" class="payment-form-content">
    <div class="form-row">
        <label for="authnet-card-number">Card Number</label>
        <input type="text" id="authnet-card-number" class="form-control" placeholder="4111 1111 1111 1111" autocomplete="cc-number">
    </div>
    <div class="form-row form-row-split">
        <div class="form-col">
            <label for="authnet-expiry">Expiration</label>
            <input type="text" id="authnet-expiry" class="form-control" placeholder="MM/YY" autocomplete="cc-exp">
        </div>
        <div class="form-col">
            <label for="authnet-cvv">CVV</label>
            <input type="text" id="authnet-cvv" class="form-control" placeholder="123" autocomplete="cc-csc">
        </div>
    </div>
    <div id="authorizenet-card-errors" class="payment-error" role="alert"></div>
</div>
<input type="hidden" name="authnet_data_descriptor" id="authnet_data_descriptor" value="">
<input type="hidden" name="authnet_data_value" id="authnet_data_value" value="">
HTML;
    }

    /**
     * Get JavaScript needed for checkout
     */
    public function getCheckoutJs(): array
    {
        return [
            'external' => [$this->getAcceptJsUrl()],
            'config' => [
                'apiLoginId' => $this->getSetting('api_login_id'),
                'publicClientKey' => $this->getSetting('public_client_key'),
                'mode' => $this->getSetting('mode', 'sandbox')
            ]
        ];
    }

    /**
     * Handle Authorize.net webhook
     */
    public function handleWebhook(string $payload, array $headers): array
    {
        $data = json_decode($payload, true);
        if (!$data) {
            return ['success' => false, 'error' => 'Invalid payload'];
        }

        // Verify signature if configured
        $signatureKey = $this->getSetting('signature_key', '');
        if (!empty($signatureKey)) {
            $signature = $headers['X-ANET-Signature'] ?? $headers['HTTP_X_ANET_SIGNATURE'] ?? '';
            $expectedSignature = 'sha512=' . strtoupper(hash_hmac('sha512', $payload, $signatureKey));

            if (!hash_equals($expectedSignature, $signature)) {
                return ['success' => false, 'error' => 'Invalid signature'];
            }
        }

        $eventType = $data['eventType'] ?? '';
        $webhookPayload = $data['payload'] ?? [];

        $this->log('Webhook received', [
            'type' => $eventType,
            'id' => $webhookPayload['id'] ?? null
        ]);

        // Handle different event types
        switch ($eventType) {
            case 'net.authorize.payment.authcapture.created':
            case 'net.authorize.payment.capture.created':
                return [
                    'success' => true,
                    'event' => 'payment_completed',
                    'transaction_id' => $webhookPayload['id'] ?? null,
                    'status' => 'completed'
                ];

            case 'net.authorize.payment.refund.created':
                return [
                    'success' => true,
                    'event' => 'refund_completed',
                    'transaction_id' => $webhookPayload['id'] ?? null
                ];

            case 'net.authorize.payment.void.created':
                return [
                    'success' => true,
                    'event' => 'payment_voided',
                    'transaction_id' => $webhookPayload['id'] ?? null
                ];

            case 'net.authorize.payment.fraud.declined':
            case 'net.authorize.payment.fraud.held':
                return [
                    'success' => true,
                    'event' => 'fraud_' . ($eventType === 'net.authorize.payment.fraud.declined' ? 'declined' : 'held'),
                    'transaction_id' => $webhookPayload['id'] ?? null
                ];

            default:
                return [
                    'success' => true,
                    'event' => $eventType,
                    'data' => $webhookPayload
                ];
        }
    }

    /**
     * Process a refund
     */
    public function processRefund(string $transactionId, ?float $amount = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Authorize.net is not configured'];
        }

        // First, get the original transaction to get the card info
        $detailsRequest = [
            'getTransactionDetailsRequest' => [
                'merchantAuthentication' => [],
                'transId' => $transactionId
            ]
        ];

        $details = $this->apiRequest($detailsRequest['getTransactionDetailsRequest']);

        if (!$details || ($details['messages']['resultCode'] ?? '') !== 'Ok') {
            return [
                'success' => false,
                'error' => 'Failed to retrieve original transaction'
            ];
        }

        $transaction = $details['transaction'] ?? [];
        $lastFour = $transaction['payment']['creditCard']['cardNumber'] ?? '';
        $originalAmount = (float) ($transaction['settleAmount'] ?? 0);

        // Build refund request
        $refundAmount = $amount ?? $originalAmount;

        $request = [
            'createTransactionRequest' => [
                'merchantAuthentication' => [],
                'refId' => 'ref_' . $transactionId,
                'transactionRequest' => [
                    'transactionType' => 'refundTransaction',
                    'amount' => number_format($refundAmount, 2, '.', ''),
                    'payment' => [
                        'creditCard' => [
                            'cardNumber' => $lastFour,
                            'expirationDate' => 'XXXX'
                        ]
                    ],
                    'refTransId' => $transactionId
                ]
            ]
        ];

        $result = $this->apiRequest($request['createTransactionRequest']);

        if (!$result) {
            return [
                'success' => false,
                'error' => 'Failed to process refund'
            ];
        }

        $messages = $result['messages'] ?? [];
        $transactionResponse = $result['transactionResponse'] ?? [];

        if ($messages['resultCode'] !== 'Ok' || ($transactionResponse['responseCode'] ?? '') !== '1') {
            $errorMessage = $transactionResponse['errors'][0]['errorText']
                ?? $messages['message'][0]['text']
                ?? 'Refund failed';
            return [
                'success' => false,
                'error' => $errorMessage
            ];
        }

        $this->log('Refund processed', [
            'refund_trans_id' => $transactionResponse['transId'],
            'amount' => $refundAmount
        ]);

        return [
            'success' => true,
            'refund_id' => $transactionResponse['transId'],
            'amount' => $refundAmount,
            'status' => 'approved'
        ];
    }

    /**
     * Get default settings
     */
    public function getDefaultSettings(): array
    {
        return [
            'mode' => 'sandbox',
            'api_login_id' => '',
            'transaction_key' => '',
            'public_client_key' => '',
            'signature_key' => ''
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
                'key' => 'api_login_id',
                'label' => 'API Login ID',
                'type' => 'text',
                'required' => true,
                'help' => 'Your Authorize.net API Login ID from the Merchant Interface'
            ],
            [
                'key' => 'transaction_key',
                'label' => 'Transaction Key',
                'type' => 'password',
                'required' => true,
                'help' => 'Your Authorize.net Transaction Key'
            ],
            [
                'key' => 'public_client_key',
                'label' => 'Public Client Key',
                'type' => 'text',
                'required' => true,
                'help' => 'Your Public Client Key for Accept.js (from API Credentials & Keys)'
            ],
            [
                'key' => 'signature_key',
                'label' => 'Signature Key',
                'type' => 'password',
                'required' => false,
                'help' => 'Optional: For verifying webhook signatures'
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
            'tokenization' => true,
            'fraud_detection' => true,
            'recurring' => true
        ];
    }
}
