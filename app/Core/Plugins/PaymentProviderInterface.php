<?php

namespace App\Core\Plugins;

/**
 * Interface for payment provider plugins
 */
interface PaymentProviderInterface extends PluginInterface
{
    /**
     * Get the payment provider's icon/logo path
     */
    public function getIcon(): string;

    /**
     * Get display name for checkout (e.g., "Credit or Debit Card")
     */
    public function getCheckoutLabel(): string;

    /**
     * Check if the provider is properly configured
     */
    public function isConfigured(): bool;

    /**
     * Create a payment session/intent
     * Returns array with client-side data needed for checkout
     *
     * @param float $amount Amount in dollars
     * @param string $currency Currency code (USD, EUR, etc.)
     * @param array $metadata Order metadata (order_id, customer_email, etc.)
     * @return array ['success' => bool, 'client_secret' => string, 'session_id' => string, ...]
     */
    public function createPaymentSession(float $amount, string $currency, array $metadata = []): array;

    /**
     * Verify a payment was successful
     *
     * @param string $transactionId The provider's transaction/payment ID
     * @param float $expectedAmount Expected amount for verification
     * @return array ['success' => bool, 'status' => string, 'error' => string|null]
     */
    public function verifyPayment(string $transactionId, float $expectedAmount): array;

    /**
     * Get the checkout form HTML for this provider
     * This is injected into the checkout page
     *
     * @param float $amount Total amount
     * @param string $currency Currency code
     * @return string HTML for the payment form
     */
    public function getCheckoutHtml(float $amount, string $currency): string;

    /**
     * Get JavaScript needed for checkout
     * Returns URL to external SDK or inline JS
     */
    public function getCheckoutJs(): array;

    /**
     * Handle webhook from the payment provider
     *
     * @param string $payload Raw request body
     * @param array $headers Request headers
     * @return array ['success' => bool, 'event' => string, 'data' => array]
     */
    public function handleWebhook(string $payload, array $headers): array;

    /**
     * Process a refund
     *
     * @param string $transactionId Original transaction ID
     * @param float|null $amount Amount to refund (null = full refund)
     * @return array ['success' => bool, 'refund_id' => string, 'error' => string|null]
     */
    public function processRefund(string $transactionId, ?float $amount = null): array;

    /**
     * Get supported features
     * Returns array of feature flags
     */
    public function getSupportedFeatures(): array;

    /**
     * Check if provider supports a specific feature
     */
    public function supportsFeature(string $feature): bool;
}
