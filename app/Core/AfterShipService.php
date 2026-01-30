<?php

namespace App\Core;

/**
 * AfterShip Integration Service
 * Handles tracking registration and status polling
 */
class AfterShipService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.aftership.com/v4';

    public function __construct()
    {
        $this->apiKey = $_ENV['AFTERSHIP_API_KEY'] ?? '';
    }

    /**
     * Check if AfterShip is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Map our carrier codes to AfterShip slug
     */
    public function getAfterShipSlug(string $carrier): string
    {
        $mapping = [
            'usps' => 'usps',
            'ups' => 'ups',
            'fedex' => 'fedex',
            'dhl' => 'dhl',
            'dhl_express' => 'dhl',
            'royal_mail' => 'royal-mail',
            'canada_post' => 'canada-post',
            'an_post' => 'an-post',
            'australia_post' => 'australia-post',
        ];

        return $mapping[$carrier] ?? $carrier;
    }

    /**
     * Register a new tracking with AfterShip
     */
    public function createTracking(string $trackingNumber, string $carrier, array $orderInfo = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'AfterShip not configured'];
        }

        $slug = $this->getAfterShipSlug($carrier);

        $payload = [
            'tracking' => [
                'tracking_number' => $trackingNumber,
                'slug' => $slug,
            ]
        ];

        // Add optional order info
        if (!empty($orderInfo['order_id'])) {
            $payload['tracking']['order_id'] = $orderInfo['order_id'];
        }
        if (!empty($orderInfo['order_number'])) {
            $payload['tracking']['order_id_path'] = '/admin/orders/view?id=' . $orderInfo['order_id'];
            $payload['tracking']['custom_fields'] = [
                'order_number' => $orderInfo['order_number']
            ];
        }
        if (!empty($orderInfo['customer_email'])) {
            $payload['tracking']['emails'] = [$orderInfo['customer_email']];
        }
        if (!empty($orderInfo['customer_name'])) {
            $payload['tracking']['title'] = $orderInfo['customer_name'];
        }

        $response = $this->request('POST', '/trackings', $payload);

        if (isset($response['data']['tracking'])) {
            return [
                'success' => true,
                'tracking_id' => $response['data']['tracking']['id'],
                'tag' => $response['data']['tracking']['tag'] ?? 'Pending'
            ];
        }

        // Check if already exists (error 4003)
        if (isset($response['meta']['code']) && $response['meta']['code'] === 4003) {
            return ['success' => true, 'already_exists' => true];
        }

        return [
            'success' => false,
            'error' => $response['meta']['message'] ?? 'Unknown error'
        ];
    }

    /**
     * Get tracking status from AfterShip
     */
    public function getTracking(string $trackingNumber, string $carrier): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'AfterShip not configured'];
        }

        $slug = $this->getAfterShipSlug($carrier);
        $response = $this->request('GET', "/trackings/{$slug}/{$trackingNumber}");

        if (isset($response['data']['tracking'])) {
            $tracking = $response['data']['tracking'];
            return [
                'success' => true,
                'tag' => $tracking['tag'],
                'subtag' => $tracking['subtag'] ?? null,
                'subtag_message' => $tracking['subtag_message'] ?? null,
                'checkpoints' => $tracking['checkpoints'] ?? [],
                'expected_delivery' => $tracking['expected_delivery'] ?? null,
                'shipment_type' => $tracking['shipment_type'] ?? null,
            ];
        }

        return [
            'success' => false,
            'error' => $response['meta']['message'] ?? 'Tracking not found'
        ];
    }

    /**
     * Map AfterShip tag to our order status
     */
    public function tagToOrderStatus(string $tag): ?string
    {
        $mapping = [
            'Delivered' => 'delivered',
            'OutForDelivery' => 'shipped',
            'InTransit' => 'shipped',
            'InfoReceived' => 'shipped',
            'Pending' => 'shipped',
            'Exception' => null,
            'AttemptFail' => null,
            'Expired' => null,
        ];

        return $mapping[$tag] ?? null;
    }

    /**
     * Make API request to AfterShip
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;

        if ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'as-api-key: ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("AfterShip API error: $error");
            return ['meta' => ['code' => 0, 'message' => $error]];
        }

        $decoded = json_decode($response, true);
        if (!$decoded) {
            error_log("AfterShip API invalid response: $response");
            return ['meta' => ['code' => $httpCode, 'message' => 'Invalid response']];
        }

        return $decoded;
    }
}
