<?php

namespace App\Core\Tracking;

/**
 * 17track tracking provider
 * Auto-detects carrier from tracking number — supports 1,200+ carriers worldwide
 * Free tier available. API: https://api.17track.net
 */
class SeventeenTrackProvider implements TrackingProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.17track.net/track/v2.2';

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
        $payload = [];
        foreach ($trackingNumbers as $number) {
            $payload[] = ['number' => $number];
        }

        $response = $this->request('/register', $payload);

        if (isset($response['code']) && $response['code'] === 0) {
            return ['success' => true];
        }

        return [
            'success' => false,
            'error' => $response['data']['errors'][0]['message'] ?? 'Registration failed'
        ];
    }

    public function getStatus(array $trackingNumbers): array
    {
        $payload = [];
        foreach ($trackingNumbers as $number) {
            $payload[] = ['number' => $number];
        }

        $response = $this->request('/gettrackinfo', $payload);

        if (isset($response['code']) && $response['code'] === 0) {
            $results = [];
            foreach ($response['data']['accepted'] ?? [] as $item) {
                $trackInfo = $item['track_info'] ?? [];
                $latestStatus = $trackInfo['latest_status'] ?? [];
                $latestEvent = $trackInfo['latest_event'] ?? null;

                $results[$item['number']] = [
                    'status'      => $latestStatus['status'] ?? 'Unknown',
                    'description' => $latestEvent['description'] ?? null,
                    'location'    => $latestEvent['location'] ?? null,
                    'event_time'  => $latestEvent['time_iso'] ?? null,
                    'carrier'     => $trackInfo['tracking']['providers'][0]['provider']['name'] ?? null,
                ];
            }

            return ['success' => true, 'results' => $results];
        }

        return ['success' => false, 'error' => 'Failed to get tracking info'];
    }

    public function stopTracking(array $trackingNumbers): array
    {
        $payload = [];
        foreach ($trackingNumbers as $number) {
            $payload[] = ['number' => $number];
        }

        $response = $this->request('/deletetrack', $payload);

        return ['success' => isset($response['code']) && $response['code'] === 0];
    }

    public static function getName(): string
    {
        return '17track';
    }

    public static function getUrl(): string
    {
        return 'https://api.17track.net';
    }

    public static function getDescription(): string
    {
        return 'Auto-detects carrier. Supports 1,200+ carriers worldwide including USPS, UPS, FedEx, DHL.';
    }

    private function request(string $endpoint, array $data): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                '17token: ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("17track API error: $error");
            return ['code' => -1, 'data' => ['errors' => [['message' => $error]]]];
        }

        return json_decode($response, true) ?: ['code' => -1];
    }
}
