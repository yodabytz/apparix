<?php

namespace App\Core\Shipping;

/**
 * FedEx REST API Integration
 * Handles OAuth authentication and rate requests
 */
class FedExService
{
    private string $clientId;
    private string $clientSecret;
    private string $accountNumber;
    private string $baseUrl;
    private ?string $accessToken = null;
    private ?int $tokenExpiry = null;

    // Cache file for token persistence
    private string $tokenCacheFile;

    public function __construct()
    {
        $this->clientId = $_ENV['FEDEX_CLIENT_ID'] ?? '';
        $this->clientSecret = $_ENV['FEDEX_CLIENT_SECRET'] ?? '';
        $this->accountNumber = $_ENV['FEDEX_ACCOUNT_NUMBER'] ?? '';

        // Use sandbox or production based on env
        $useSandbox = ($_ENV['FEDEX_SANDBOX'] ?? 'true') === 'true';
        $this->baseUrl = $useSandbox
            ? 'https://apis-sandbox.fedex.com'
            : 'https://apis.fedex.com';

        $this->tokenCacheFile = sys_get_temp_dir() . '/fedex_token_' . md5($this->clientId) . '.json';

        // Load cached token if available
        $this->loadCachedToken();
    }

    /**
     * Check if service is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Get OAuth access token (cached)
     */
    private function getAccessToken(): ?string
    {
        // Return cached token if still valid
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        // Request new token
        $response = $this->httpPost('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ], false, true);

        if (isset($response['access_token'])) {
            $this->accessToken = $response['access_token'];
            $this->tokenExpiry = time() + ($response['expires_in'] ?? 3600) - 60; // 1 min buffer
            $this->saveCachedToken();
            return $this->accessToken;
        }

        error_log('FedEx OAuth failed: ' . json_encode($response));
        return null;
    }

    /**
     * Load token from cache file
     */
    private function loadCachedToken(): void
    {
        if (file_exists($this->tokenCacheFile)) {
            $data = json_decode(file_get_contents($this->tokenCacheFile), true);
            if ($data && isset($data['token']) && isset($data['expiry']) && time() < $data['expiry']) {
                $this->accessToken = $data['token'];
                $this->tokenExpiry = $data['expiry'];
            }
        }
    }

    /**
     * Save token to cache file
     */
    private function saveCachedToken(): void
    {
        file_put_contents($this->tokenCacheFile, json_encode([
            'token' => $this->accessToken,
            'expiry' => $this->tokenExpiry
        ]));
    }

    /**
     * Get shipping rates from FedEx
     *
     * @param array $origin Origin address
     * @param array $destination Destination address
     * @param array $packages Package details (weight, dimensions)
     * @return array Rate options or error
     */
    public function getRates(array $origin, array $destination, array $packages): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'FedEx not configured'];
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'error' => 'Failed to authenticate with FedEx'];
        }

        // Build rate request payload
        $payload = [
            'accountNumber' => [
                'value' => $this->accountNumber
            ],
            'requestedShipment' => [
                'shipper' => [
                    'address' => [
                        'streetLines' => array_filter([$origin['address_line1'], $origin['address_line2'] ?? '']),
                        'city' => $origin['city'],
                        'stateOrProvinceCode' => $origin['state'],
                        'postalCode' => $origin['postal_code'],
                        'countryCode' => $origin['country'] ?? 'US'
                    ]
                ],
                'recipient' => [
                    'address' => [
                        'streetLines' => array_filter([$destination['address_line1'] ?? '', $destination['address_line2'] ?? '']),
                        'city' => $destination['city'] ?? '',
                        'stateOrProvinceCode' => $destination['state'] ?? '',
                        'postalCode' => $destination['postal_code'],
                        'countryCode' => $destination['country'] ?? 'US',
                        'residential' => $destination['residential'] ?? true
                    ]
                ],
                'pickupType' => 'DROPOFF_AT_FEDEX_LOCATION',
                'rateRequestType' => ['ACCOUNT', 'LIST'],
                'requestedPackageLineItems' => $this->buildPackages($packages)
            ]
        ];

        $response = $this->httpPost('/rate/v1/rates/quotes', $payload, true);

        if (isset($response['output']['rateReplyDetails'])) {
            return $this->parseRateResponse($response['output']['rateReplyDetails']);
        }

        // Handle errors
        $error = $response['errors'][0]['message'] ?? 'Unknown FedEx error';
        error_log('FedEx rate error: ' . json_encode($response));

        return ['success' => false, 'error' => $error];
    }

    /**
     * Build package line items for rate request
     */
    private function buildPackages(array $packages): array
    {
        $items = [];

        foreach ($packages as $pkg) {
            $item = [
                'weight' => [
                    'units' => 'LB',
                    'value' => max(0.1, round(($pkg['weight_oz'] ?? 16) / 16, 2)) // Convert oz to lbs
                ]
            ];

            // Add dimensions if provided
            if (!empty($pkg['length']) && !empty($pkg['width']) && !empty($pkg['height'])) {
                $item['dimensions'] = [
                    'length' => (int)$pkg['length'],
                    'width' => (int)$pkg['width'],
                    'height' => (int)$pkg['height'],
                    'units' => 'IN'
                ];
            }

            $items[] = $item;
        }

        return $items ?: [[
            'weight' => ['units' => 'LB', 'value' => 1]
        ]];
    }

    /**
     * Parse FedEx rate response into standard format
     */
    private function parseRateResponse(array $rateDetails): array
    {
        $rates = [];

        $serviceNames = [
            'FEDEX_GROUND' => 'FedEx Ground',
            'GROUND_HOME_DELIVERY' => 'FedEx Home Delivery',
            'FEDEX_EXPRESS_SAVER' => 'FedEx Express Saver',
            'FEDEX_2_DAY' => 'FedEx 2Day',
            'FEDEX_2_DAY_AM' => 'FedEx 2Day AM',
            'STANDARD_OVERNIGHT' => 'FedEx Standard Overnight',
            'PRIORITY_OVERNIGHT' => 'FedEx Priority Overnight',
            'FIRST_OVERNIGHT' => 'FedEx First Overnight',
            'FEDEX_INTERNATIONAL_PRIORITY' => 'FedEx International Priority',
            'FEDEX_INTERNATIONAL_ECONOMY' => 'FedEx International Economy',
            'INTERNATIONAL_FIRST' => 'FedEx International First',
        ];

        foreach ($rateDetails as $detail) {
            $serviceType = $detail['serviceType'] ?? '';
            $serviceName = $serviceNames[$serviceType] ?? $serviceType;

            // Get the rated shipment details
            $ratedPackages = $detail['ratedShipmentDetails'] ?? [];

            foreach ($ratedPackages as $rated) {
                // Prefer account rate, fall back to list rate
                $totalCharge = $rated['totalNetCharge'] ?? $rated['totalNetFedExCharge'] ?? null;

                if ($totalCharge) {
                    $rates[] = [
                        'service_code' => $serviceType,
                        'service_name' => $serviceName,
                        'rate' => (float)$totalCharge,
                        'currency' => $rated['currency'] ?? 'USD',
                        'delivery_days' => $detail['commit']['dateDetail']['dayOfWeek'] ?? null,
                        'delivery_date' => $detail['commit']['dateDetail']['dayFormat'] ?? null,
                        'carrier' => 'FedEx'
                    ];
                    break; // Only take first (account) rate per service
                }
            }
        }

        // Sort by rate
        usort($rates, fn($a, $b) => $a['rate'] <=> $b['rate']);

        return [
            'success' => true,
            'rates' => $rates
        ];
    }

    /**
     * HTTP POST request helper
     */
    private function httpPost(string $endpoint, array $data, bool $json = true, bool $isAuth = false): array
    {
        $url = $this->baseUrl . $endpoint;

        $headers = [];

        if ($isAuth) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $body = http_build_query($data);
        } else {
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
            $body = json_encode($data);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_ENCODING => '' // Auto-decode gzip/deflate
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("FedEx curl error: $error");
            return ['error' => $error];
        }

        return json_decode($response, true) ?? ['error' => 'Invalid JSON response'];
    }
}
