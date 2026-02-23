<?php
/**
 * Lightweight Backblaze B2 S3-Compatible API Client
 *
 * Uses the S3-compatible API with AWS Signature v4 signing.
 * No external dependencies — pure PHP + curl.
 *
 * @version 2.0.0
 */

namespace Plugins\Backup;

class BackblazeS3Client
{
    private string $keyId;
    private string $applicationKey;
    private string $bucketName;
    private string $endpoint;
    private string $region;

    /**
     * @param string $keyId       B2 Application Key ID
     * @param string $applicationKey  B2 Application Key
     * @param string $bucketName  B2 Bucket Name
     * @param string $endpoint    S3-compatible endpoint (e.g. s3.us-west-004.backblazeb2.com)
     */
    public function __construct(string $keyId, string $applicationKey, string $bucketName, string $endpoint)
    {
        $this->keyId = $keyId;
        $this->applicationKey = $applicationKey;
        $this->bucketName = $bucketName;

        // Normalize endpoint — strip protocol and trailing slash
        $this->endpoint = rtrim(preg_replace('#^https?://#', '', $endpoint), '/');

        // Extract region from endpoint (e.g. s3.us-west-004.backblazeb2.com -> us-west-004)
        if (preg_match('/s3\.([a-z0-9-]+)\.backblazeb2\.com/', $this->endpoint, $m)) {
            $this->region = $m[1];
        } else {
            $this->region = 'us-east-005'; // fallback
        }
    }

    /**
     * Test the connection by listing up to 1 object
     *
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function testConnection(): array
    {
        try {
            $result = $this->listObjects('', 1);
            return $result;
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Upload a file to B2
     *
     * @param string $objectKey  The key (path) in the bucket
     * @param string $filePath   Local file path to upload
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function putObject(string $objectKey, string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['success' => false, 'error' => 'File not found or not readable: ' . $filePath];
        }

        $fileSize = filesize($filePath);
        $contentHash = hash_file('sha256', $filePath);
        $contentType = 'application/octet-stream';

        $headers = [
            'Content-Type' => $contentType,
            'Content-Length' => (string)$fileSize,
            'x-amz-content-sha256' => $contentHash,
        ];

        $url = $this->buildUrl($objectKey);
        $signedHeaders = $this->signRequest('PUT', $objectKey, '', $headers, $contentHash);

        $fh = fopen($filePath, 'rb');
        if (!$fh) {
            return ['success' => false, 'error' => 'Cannot open file for reading'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $fh,
            CURLOPT_INFILESIZE => $fileSize,
            CURLOPT_HTTPHEADER => $this->formatHeaders($signedHeaders),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 600, // 10 minutes for large uploads
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        fclose($fh);

        if ($curlError) {
            return ['success' => false, 'error' => 'Upload failed: ' . $curlError];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'size' => $fileSize];
        }

        $errorMsg = $this->parseErrorResponse($response);
        return ['success' => false, 'error' => "Upload failed (HTTP {$httpCode}): {$errorMsg}"];
    }

    /**
     * Download a file from B2
     *
     * @param string $objectKey  The key (path) in the bucket
     * @param string $destPath   Local destination file path
     * @return array ['success' => bool, 'error' => string|null, 'size' => int]
     */
    public function getObject(string $objectKey, string $destPath): array
    {
        $destDir = dirname($destPath);
        if (!is_dir($destDir) || !is_writable($destDir)) {
            return ['success' => false, 'error' => 'Destination directory not writable'];
        }

        $headers = [
            'x-amz-content-sha256' => 'UNSIGNED-PAYLOAD',
        ];

        $url = $this->buildUrl($objectKey);
        $signedHeaders = $this->signRequest('GET', $objectKey, '', $headers, 'UNSIGNED-PAYLOAD');

        $fh = fopen($destPath, 'wb');
        if (!$fh) {
            return ['success' => false, 'error' => 'Cannot create destination file'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_FILE => $fh,
            CURLOPT_HTTPHEADER => $this->formatHeaders($signedHeaders),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 600,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        fclose($fh);

        if ($curlError) {
            @unlink($destPath);
            return ['success' => false, 'error' => 'Download failed: ' . $curlError];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'size' => filesize($destPath)];
        }

        @unlink($destPath);
        return ['success' => false, 'error' => "Download failed (HTTP {$httpCode})"];
    }

    /**
     * Delete an object from B2
     *
     * @param string $objectKey  The key (path) in the bucket
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function deleteObject(string $objectKey): array
    {
        $headers = [
            'x-amz-content-sha256' => hash('sha256', ''),
        ];

        $url = $this->buildUrl($objectKey);
        $signedHeaders = $this->signRequest('DELETE', $objectKey, '', $headers, hash('sha256', ''));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => $this->formatHeaders($signedHeaders),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'error' => 'Delete failed: ' . $curlError];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true];
        }

        $errorMsg = $this->parseErrorResponse($response);
        return ['success' => false, 'error' => "Delete failed (HTTP {$httpCode}): {$errorMsg}"];
    }

    /**
     * List objects in the bucket with optional prefix
     *
     * @param string $prefix   Key prefix filter
     * @param int    $maxKeys  Maximum number of keys to return
     * @return array ['success' => bool, 'objects' => array, 'error' => string|null]
     */
    public function listObjects(string $prefix = '', int $maxKeys = 1000): array
    {
        $queryParams = 'list-type=2&max-keys=' . $maxKeys;
        if ($prefix) {
            $queryParams .= '&prefix=' . rawurlencode($prefix);
        }

        $headers = [
            'x-amz-content-sha256' => hash('sha256', ''),
        ];

        $url = $this->buildUrl('', $queryParams);
        $signedHeaders = $this->signRequest('GET', '/', $queryParams, $headers, hash('sha256', ''));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($signedHeaders),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'error' => 'List failed: ' . $curlError, 'objects' => []];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $errorMsg = $this->parseErrorResponse($response);
            return ['success' => false, 'error' => "List failed (HTTP {$httpCode}): {$errorMsg}", 'objects' => []];
        }

        // Parse XML response
        $objects = [];
        try {
            $xml = new \SimpleXMLElement($response);
            foreach ($xml->Contents as $item) {
                $objects[] = [
                    'key' => (string)$item->Key,
                    'size' => (int)$item->Size,
                    'last_modified' => (string)$item->LastModified,
                    'etag' => trim((string)$item->ETag, '"'),
                ];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to parse response: ' . $e->getMessage(), 'objects' => []];
        }

        return ['success' => true, 'objects' => $objects];
    }

    /**
     * Build the full URL for an S3 request
     */
    private function buildUrl(string $objectKey = '', string $queryString = ''): string
    {
        $path = '/' . $this->bucketName;
        if ($objectKey) {
            $path .= '/' . ltrim($objectKey, '/');
        }

        $url = 'https://' . $this->endpoint . $path;
        if ($queryString) {
            $url .= '?' . $queryString;
        }

        return $url;
    }

    /**
     * Sign a request using AWS Signature v4
     *
     * @return array Merged headers including Authorization
     */
    private function signRequest(string $method, string $objectKey, string $queryString, array $headers, string $payloadHash): array
    {
        $now = new \DateTime('UTC');
        $dateStamp = $now->format('Ymd');
        $amzDate = $now->format('Ymd\THis\Z');

        // Build canonical URI
        $canonicalUri = '/' . $this->bucketName;
        if ($objectKey && $objectKey !== '/') {
            $canonicalUri .= '/' . ltrim($objectKey, '/');
        }

        // Add required headers
        $headers['Host'] = $this->endpoint;
        $headers['x-amz-date'] = $amzDate;

        // Sort headers by lowercase key
        $sortedHeaders = [];
        foreach ($headers as $k => $v) {
            $sortedHeaders[strtolower($k)] = trim($v);
        }
        ksort($sortedHeaders);

        // Build canonical headers and signed headers list
        $canonicalHeaders = '';
        $signedHeadersList = [];
        foreach ($sortedHeaders as $k => $v) {
            $canonicalHeaders .= $k . ':' . $v . "\n";
            $signedHeadersList[] = $k;
        }
        $signedHeaders = implode(';', $signedHeadersList);

        // Parse and sort query string
        $canonicalQueryString = '';
        if ($queryString) {
            $params = [];
            foreach (explode('&', $queryString) as $param) {
                $parts = explode('=', $param, 2);
                $params[rawurlencode($parts[0])] = isset($parts[1]) ? rawurlencode(rawurldecode($parts[1])) : '';
            }
            ksort($params);
            $pairs = [];
            foreach ($params as $k => $v) {
                $pairs[] = $k . '=' . $v;
            }
            $canonicalQueryString = implode('&', $pairs);
        }

        // Build canonical request
        $canonicalRequest = implode("\n", [
            $method,
            $canonicalUri,
            $canonicalQueryString,
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        // Build string to sign
        $scope = $dateStamp . '/' . $this->region . '/s3/aws4_request';
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $scope,
            hash('sha256', $canonicalRequest),
        ]);

        // Derive signing key
        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $this->applicationKey, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        // Calculate signature
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        // Build authorization header
        $authorization = sprintf(
            'AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            $this->keyId,
            $scope,
            $signedHeaders,
            $signature
        );

        $headers['Authorization'] = $authorization;
        $headers['x-amz-date'] = $amzDate;

        return $headers;
    }

    /**
     * Format headers array into curl header strings
     */
    private function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            $formatted[] = $key . ': ' . $value;
        }
        return $formatted;
    }

    /**
     * Parse an S3 XML error response
     */
    private function parseErrorResponse(?string $response): string
    {
        if (!$response) {
            return 'No response body';
        }

        try {
            $xml = @simplexml_load_string($response);
            if ($xml && isset($xml->Message)) {
                return (string)$xml->Message;
            }
            if ($xml && isset($xml->Code)) {
                return (string)$xml->Code;
            }
        } catch (\Exception $e) {
            // Not XML, return raw
        }

        // Truncate if too long
        if (strlen($response) > 200) {
            return substr($response, 0, 200) . '...';
        }

        return $response;
    }
}
