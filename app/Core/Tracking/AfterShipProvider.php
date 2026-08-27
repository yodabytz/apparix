<?php

namespace App\Core\Tracking;

/**
 * AfterShip tracking provider
 * Supports 1,100+ carriers. Paid plans start at $11/month.
 * API: https://www.aftership.com/
 */
class AfterShipProvider implements TrackingProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.aftership.com/v4';

    private array $carrierMapping = [
        'usps' => 'usps', 'ups' => 'ups', 'fedex' => 'fedex',
        'dhl' => 'dhl', 'dhl_express' => 'dhl',
        'royal_mail' => 'royal-mail', 'evri' => 'myhermes-uk',
        'canada_post' => 'canada-post',
        'an_post' => 'an-post', 'australia_post' => 'australia-post',
    ];

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function register(array $trackingNumbers): array
    {
        // AfterShip auto-registers on first lookup via detect endpoint
        // We register explicitly for better tracking
        foreach ($trackingNumbers as $number) {
            $payload = [
                'tracking' => [
                    'tracking_number' => $number,
                ]
            ];

            $response = $this->request('POST', '/trackings', $payload);

            // 4003 = already exists, which is fine
            if (!isset($response['data']['tracking']) &&
                (!isset($response['meta']['code']) || $response['meta']['code'] !== 4003)) {
                // Non-critical — continue with others
            }
        }

        return ['success' => true];
    }

    public function getStatus(array $trackingNumbers): array
    {
        $results = [];

        foreach ($trackingNumbers as $number) {
            // Try detect endpoint first (no carrier needed)
            $response = $this->request('GET', "/trackings/{$number}");

            // If not found without slug, try with detect
            if (!isset($response['data']['tracking'])) {
                $detectResponse = $this->request('POST', '/trackings/detect', [
                    'tracking' => ['tracking_number' => $number]
                ]);
                if (isset($detectResponse['data']['tracking'])) {
                    $response = $detectResponse;
                }
            }

            if (isset($response['data']['tracking'])) {
                $tracking = $response['data']['tracking'];
                $checkpoints = $tracking['checkpoints'] ?? [];
                $latest = end($checkpoints) ?: null;

                $results[$number] = [
                    'status'      => $this->normalizeTag($tracking['tag'] ?? 'Pending'),
                    'description' => $latest['message'] ?? $tracking['subtag_message'] ?? null,
                    'location'    => $latest ? trim(($latest['city'] ?? '') . ', ' . ($latest['state'] ?? '') . ', ' . ($latest['country_name'] ?? ''), ', ') : null,
                    'event_time'  => $latest['checkpoint_time'] ?? null,
                    'carrier'     => $tracking['slug'] ?? null,
                ];
            } else {
                $results[$number] = [
                    'status'      => 'NotFound',
                    'description' => null,
                    'location'    => null,
                    'event_time'  => null,
                    'carrier'     => null,
                ];
            }

            usleep(300000); // Rate limit
        }

        return ['success' => true, 'results' => $results];
    }

    public function stopTracking(array $trackingNumbers): array
    {
        foreach ($trackingNumbers as $number) {
            $this->request('DELETE', "/trackings/{$number}");
        }
        return ['success' => true];
    }

    public static function getName(): string
    {
        return 'AfterShip';
    }

    public static function getUrl(): string
    {
        return 'https://www.aftership.com';
    }

    public static function getDescription(): string
    {
        return 'Supports 1,100+ carriers. Paid plans required for tracking API access.';
    }

    /**
     * Normalize AfterShip tags to our standard status values
     */
    private function normalizeTag(string $tag): string
    {
        $mapping = [
            'Delivered'      => 'Delivered',
            'OutForDelivery' => 'OutForDelivery',
            'InTransit'      => 'InTransit',
            'InfoReceived'   => 'InfoReceived',
            'Pending'        => 'InfoReceived',
            'AttemptFail'    => 'Exception',
            'Exception'      => 'Exception',
            'Expired'        => 'NotFound',
        ];

        return $mapping[$tag] ?? 'Unknown';
    }

    private function request(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'as-api-key: ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CUSTOMREQUEST  => $method,
        ]);

        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("AfterShip API error: $error");
            return ['meta' => ['code' => 0, 'message' => $error]];
        }

        return json_decode($response, true) ?: ['meta' => ['code' => 0, 'message' => 'Invalid response']];
    }
}
