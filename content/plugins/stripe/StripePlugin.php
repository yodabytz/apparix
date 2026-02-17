<?php

namespace App\Plugins;

use App\Core\Plugins\AbstractPaymentProvider;
use App\Core\Plugins\PaymentProviderInterface;

/**
 * Stripe Payment Provider Plugin
 *
 * Wraps the existing Stripe integration into the plugin system
 */
class StripePlugin extends AbstractPaymentProvider implements PaymentProviderInterface
{
    public function getSlug(): string
    {
        return 'stripe';
    }

    public function getName(): string
    {
        return 'Stripe Payments';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Accept credit and debit card payments with Stripe';
    }

    public function getAuthor(): string
    {
        return 'Apparix';
    }

    public function getIcon(): string
    {
        return '/content/plugins/stripe/assets/stripe-logo.svg';
    }

    public function getCheckoutLabel(): string
    {
        return 'Credit or Debit Card';
    }

    /**
     * Get the appropriate API key based on mode
     */
    private function getSecretKey(): string
    {
        $mode = $this->getSetting('mode', 'test');

        if ($mode === 'live') {
            return $this->getSetting('live_secret_key', $_ENV['STRIPE_SECRET_KEY'] ?? '');
        }

        return $this->getSetting('test_secret_key', $_ENV['STRIPE_SECRET_KEY'] ?? '');
    }

    /**
     * Get the appropriate publishable key based on mode
     */
    private function getPublicKey(): string
    {
        $mode = $this->getSetting('mode', 'test');

        if ($mode === 'live') {
            return $this->getSetting('live_public_key', $_ENV['STRIPE_PUBLIC_KEY'] ?? '');
        }

        return $this->getSetting('test_public_key', $_ENV['STRIPE_PUBLIC_KEY'] ?? '');
    }

    /**
     * Check if Stripe is properly configured
     */
    public function isConfigured(): bool
    {
        $secretKey = $this->getSecretKey();
        $publicKey = $this->getPublicKey();

        return !empty($secretKey) && !empty($publicKey);
    }

    /**
     * Initialize Stripe SDK
     */
    private function initStripe(): void
    {
        \Stripe\Stripe::setApiKey($this->getSecretKey());
    }

    /**
     * Create a payment intent
     */
    public function createPaymentSession(float $amount, string $currency, array $metadata = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Stripe is not configured'];
        }

        try {
            $this->initStripe();

            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $this->toCents($amount),
                'currency' => strtolower($currency),
                'metadata' => $metadata,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            $this->log('Payment intent created', [
                'intent_id' => $paymentIntent->id,
                'amount' => $amount
            ]);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->log('Payment intent creation failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify a payment was successful
     */
    public function verifyPayment(string $transactionId, float $expectedAmount): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Stripe is not configured'];
        }

        try {
            $this->initStripe();

            $paymentIntent = \Stripe\PaymentIntent::retrieve($transactionId);

            if ($paymentIntent->status !== 'succeeded') {
                return [
                    'success' => false,
                    'status' => $paymentIntent->status,
                    'error' => 'Payment not completed'
                ];
            }

            // Verify amount matches
            $paidAmount = $this->fromCents($paymentIntent->amount_received);
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
                'status' => $paymentIntent->status,
                'amount' => $paidAmount,
                'currency' => strtoupper($paymentIntent->currency),
                'payment_method' => $paymentIntent->payment_method
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get checkout HTML for Stripe Elements
     */
    public function getCheckoutHtml(float $amount, string $currency): string
    {
        $publicKey = $this->getPublicKey();

        return <<<HTML
<div id="stripe-payment-form" class="payment-form-content">
    <div id="stripe-card-element" class="stripe-element"></div>
    <div id="stripe-card-errors" class="payment-error" role="alert"></div>
</div>
<input type="hidden" name="payment_intent_id" id="stripe_payment_intent_id" value="">
HTML;
    }

    /**
     * Get JavaScript needed for checkout
     */
    public function getCheckoutJs(): array
    {
        return [
            'external' => ['https://js.stripe.com/v3/'],
            'config' => [
                'publicKey' => $this->getPublicKey()
            ]
        ];
    }

    /**
     * Handle Stripe webhook
     */
    public function handleWebhook(string $payload, array $headers): array
    {
        $webhookSecret = $this->getSetting('webhook_secret', '');
        $signature = $headers['HTTP_STRIPE_SIGNATURE'] ?? $headers['Stripe-Signature'] ?? '';

        if (empty($webhookSecret)) {
            // No webhook secret configured, try to process anyway
            $event = json_decode($payload);
            if (!$event) {
                return ['success' => false, 'error' => 'Invalid payload'];
            }
        } else {
            try {
                $event = \Stripe\Webhook::constructEvent(
                    $payload,
                    $signature,
                    $webhookSecret
                );
            } catch (\Exception $e) {
                return ['success' => false, 'error' => 'Webhook verification failed: ' . $e->getMessage()];
            }
        }

        $this->log('Webhook received', [
            'type' => $event->type,
            'id' => $event->id
        ]);

        return [
            'success' => true,
            'event' => $event->type,
            'data' => (array) $event->data->object
        ];
    }

    /**
     * Process a refund
     */
    public function processRefund(string $transactionId, ?float $amount = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Stripe is not configured'];
        }

        try {
            $this->initStripe();

            $refundParams = ['payment_intent' => $transactionId];

            if ($amount !== null) {
                $refundParams['amount'] = $this->toCents($amount);
            }

            $refund = \Stripe\Refund::create($refundParams);

            $this->log('Refund processed', [
                'refund_id' => $refund->id,
                'amount' => $amount
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $this->fromCents($refund->amount),
                'status' => $refund->status
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get default settings
     */
    public function getDefaultSettings(): array
    {
        return [
            'mode' => 'test',
            'test_public_key' => $_ENV['STRIPE_PUBLIC_KEY'] ?? '',
            'test_secret_key' => $_ENV['STRIPE_SECRET_KEY'] ?? '',
            'live_public_key' => '',
            'live_secret_key' => '',
            'webhook_secret' => ''
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
                'options' => ['test', 'live'],
                'default' => 'test',
                'help' => 'Use test mode for development, live mode for production'
            ],
            [
                'key' => 'test_public_key',
                'label' => 'Test Publishable Key',
                'type' => 'text',
                'help' => 'Your Stripe test publishable key (pk_test_...)'
            ],
            [
                'key' => 'test_secret_key',
                'label' => 'Test Secret Key',
                'type' => 'password',
                'help' => 'Your Stripe test secret key (sk_test_...)'
            ],
            [
                'key' => 'live_public_key',
                'label' => 'Live Publishable Key',
                'type' => 'text',
                'help' => 'Your Stripe live publishable key (pk_live_...)'
            ],
            [
                'key' => 'live_secret_key',
                'label' => 'Live Secret Key',
                'type' => 'password',
                'help' => 'Your Stripe live secret key (sk_live_...)'
            ],
            [
                'key' => 'webhook_secret',
                'label' => 'Webhook Signing Secret',
                'type' => 'password',
                'help' => 'Optional: Webhook secret for verifying webhook events (whsec_...)'
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
            'recurring' => true,
            'saved_cards' => true,
            'apple_pay' => true,
            'google_pay' => true
        ];
    }
}
