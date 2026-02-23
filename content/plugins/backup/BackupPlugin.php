<?php
/**
 * Apparix Backup Plugin
 *
 * Full backup solution with tiered backups, Backblaze B2 cloud storage,
 * encrypted .env backup, health notifications, and size estimates.
 *
 * @version 2.0.0
 */

namespace App\Plugins;

use App\Core\Plugins\PluginInterface;
use App\Core\Database;
use App\Core\License;

class BackupPlugin implements PluginInterface
{
    private array $settings = [];
    private string $backupDir;
    private string $baseDir;

    /**
     * Tier definitions — what each tier backs up
     */
    private const TIERS = [
        'essential' => [
            'label' => 'Essential',
            'description' => 'Database, encrypted .env, themes & plugins',
            'min_edition' => ['F', 'S', 'P', 'E', 'D', 'U'], // All editions
            'components' => ['database', 'env_encrypted', 'themes_plugins'],
        ],
        'standard' => [
            'label' => 'Standard',
            'description' => 'Essential + product images & user uploads',
            'min_edition' => ['P', 'E', 'D', 'U'], // Professional+
            'components' => ['database', 'env_encrypted', 'themes_plugins', 'product_images', 'uploads'],
        ],
        'complete' => [
            'label' => 'Complete',
            'description' => 'Standard + full application code & config exports',
            'min_edition' => ['E', 'D', 'U'], // Enterprise+
            'components' => ['database', 'env_encrypted', 'themes_plugins', 'product_images', 'uploads', 'code', 'config_exports'],
        ],
    ];

    public function __construct()
    {
        $this->baseDir = dirname(dirname(dirname(__DIR__)));
        $this->backupDir = $this->baseDir . '/storage/backups';
        $this->loadSettings();
    }

    // ========================================================================
    // PluginInterface Implementation
    // ========================================================================

    public function getName(): string
    {
        return 'Apparix Backup';
    }

    public function getSlug(): string
    {
        return 'backup';
    }

    public function getVersion(): string
    {
        return '2.0.0';
    }

    public function getType(): string
    {
        return 'utility';
    }

    public function getDescription(): string
    {
        return 'Full backup solution with Backblaze B2 cloud storage, tiered backups, and health notifications.';
    }

    public function getAuthor(): string
    {
        return 'Apparix';
    }

    public function init(): void
    {
        $this->initialize();
    }

    public function onActivate(): void
    {
        $this->initialize();
    }

    public function onDeactivate(): void
    {
        // Nothing to clean up
    }

    public function validateSettings(array $settings): array
    {
        return []; // Validation handled internally
    }

    public function getDefaultSettings(): array
    {
        return [
            'backup_tier' => 'essential',
            'retention_count' => 5,
            'auto_backup' => false,
            'backup_schedule' => 'daily',
            'last_backup' => null,
            'b2_enabled' => false,
            'b2_key_id' => '',
            'b2_application_key' => '',
            'b2_bucket_name' => '',
            'b2_endpoint' => '',
            'b2_auto_upload' => false,
            'b2_retention_count' => 10,
            'cloud_upload_status' => null,
            'cloud_upload_error' => null,
            'last_cloud_upload' => null,
            'notification_stale_days' => 7,
            'last_error' => null,
            'last_error_time' => null,
        ];
    }

    public function getSettingsSchema(): array
    {
        return []; // We provide a custom settings view
    }

    /**
     * Initialize the plugin
     */
    public function initialize(): void
    {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        $htaccess = $this->backupDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Order Deny,Allow\nDeny from all\n");
        }

        $index = $this->backupDir . '/index.php';
        if (!file_exists($index)) {
            file_put_contents($index, "<?php // Silence is golden\n");
        }
    }

    /**
     * Get settings view — returns HTML string
     * Handles POST actions and downloads before rendering
     */
    public function getSettingsView(): string
    {
        // Handle downloads (these send headers and exit)
        if (isset($_GET['download'])) {
            $this->handleLocalDownload();
        }
        if (isset($_GET['download_cloud'])) {
            $this->handleCloudDownload();
        }

        // Handle POST actions
        $viewMessage = null;
        $viewError = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            // Verify CSRF
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $viewError = 'Invalid security token. Please try again.';
            } else {
                [$viewMessage, $viewError] = $this->handleSettingsAction($_POST);
            }
        }

        // Render view via output buffering
        $backupPlugin = $this;
        ob_start();
        include __DIR__ . '/views/admin-settings.php';
        return ob_get_clean();
    }

    /**
     * Handle webhook (not used by this plugin)
     */
    public function handleWebhook(string $payload, array $headers): array
    {
        return ['success' => false, 'error' => 'Not implemented'];
    }

    // ========================================================================
    // Settings
    // ========================================================================

    private function loadSettings(): void
    {
        try {
            $db = Database::getInstance();
            $plugin = $db->selectOne(
                "SELECT settings FROM plugins WHERE slug = ?",
                [$this->getSlug()]
            );

            if ($plugin && !empty($plugin['settings'])) {
                $this->settings = json_decode($plugin['settings'], true) ?: [];
            }
        } catch (\Exception $e) {
            $this->settings = [];
        }

        $this->settings = array_merge($this->getDefaultSettings(), $this->settings);
    }

    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function saveSettings(array $settings): bool
    {
        try {
            $db = Database::getInstance();
            $this->settings = array_merge($this->settings, $settings);

            $db->query(
                "UPDATE plugins SET settings = ? WHERE slug = ?",
                [json_encode($this->settings), $this->getSlug()]
            );

            return true;
        } catch (\Exception $e) {
            $this->log("Failed to save settings: " . $e->getMessage(), 'error');
            return false;
        }
    }

    // ========================================================================
    // Action Handling (called from getSettingsView)
    // ========================================================================

    private function handleSettingsAction(array $post): array
    {
        $message = null;
        $error = null;

        switch ($post['action'] ?? '') {
            case 'create_backup':
                $uploadToCloud = !empty($post['upload_to_cloud']);
                $result = $this->createBackup(false);
                if ($result['success']) {
                    $message = "Backup created successfully! Size: {$result['size_formatted']} (Tier: {$result['tier']})";
                    // Auto-upload to B2 if requested
                    if ($uploadToCloud && $this->isB2Configured()) {
                        $uploadResult = $this->uploadToB2($result['filepath']);
                        if ($uploadResult['success']) {
                            $message .= ' — Uploaded to cloud storage.';
                        } else {
                            $error = "Backup created but cloud upload failed: " . $uploadResult['error'];
                        }
                    }
                } else {
                    $error = "Backup failed: " . implode(', ', $result['errors']);
                }
                break;

            case 'delete_backup':
                $filename = $post['filename'] ?? '';
                if ($this->deleteBackup($filename)) {
                    $message = "Backup deleted successfully.";
                } else {
                    $error = "Failed to delete backup.";
                }
                break;

            case 'upload_to_cloud':
                $filename = $post['filename'] ?? '';
                $filepath = $this->getBackupPath($filename);
                if ($filepath) {
                    $result = $this->uploadToB2($filepath);
                    if ($result['success']) {
                        $message = "Backup uploaded to cloud storage.";
                    } else {
                        $error = "Upload failed: " . $result['error'];
                    }
                } else {
                    $error = "Backup file not found.";
                }
                break;

            case 'delete_cloud_backup':
                $key = $post['cloud_key'] ?? '';
                if ($key) {
                    $result = $this->deleteCloudBackup($key);
                    if ($result['success']) {
                        $message = "Cloud backup deleted.";
                    } else {
                        $error = "Failed to delete cloud backup: " . $result['error'];
                    }
                }
                break;

            case 'test_b2':
                $result = $this->testB2Connection();
                if ($result['success']) {
                    $message = "Backblaze B2 connection successful!";
                } else {
                    $error = "B2 connection failed: " . $result['error'];
                }
                break;

            case 'save_settings':
                $newSettings = [
                    'backup_tier' => in_array($post['backup_tier'] ?? '', ['essential', 'standard', 'complete'])
                        ? $post['backup_tier'] : 'essential',
                    'retention_count' => max(1, min(30, (int)($post['retention_count'] ?? 5))),
                    'auto_backup' => isset($post['auto_backup']),
                    'backup_schedule' => in_array($post['backup_schedule'] ?? '', ['hourly', 'daily', 'weekly'])
                        ? $post['backup_schedule'] : 'daily',
                    'notification_stale_days' => max(1, min(90, (int)($post['notification_stale_days'] ?? 7))),
                    'b2_enabled' => isset($post['b2_enabled']),
                    'b2_key_id' => trim($post['b2_key_id'] ?? ''),
                    'b2_application_key' => trim($post['b2_application_key'] ?? ''),
                    'b2_bucket_name' => trim($post['b2_bucket_name'] ?? ''),
                    'b2_endpoint' => trim($post['b2_endpoint'] ?? ''),
                    'b2_auto_upload' => isset($post['b2_auto_upload']),
                    'b2_retention_count' => max(1, min(100, (int)($post['b2_retention_count'] ?? 10))),
                ];
                if ($this->saveSettings($newSettings)) {
                    $message = "Settings saved successfully.";
                } else {
                    $error = "Failed to save settings.";
                }
                break;
        }

        return [$message, $error];
    }

    private function handleLocalDownload(): void
    {
        $filename = basename($_GET['download']);
        $filepath = $this->getBackupPath($filename);

        if ($filepath) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            header('Cache-Control: no-cache, must-revalidate');
            readfile($filepath);
            exit;
        }
    }

    private function handleCloudDownload(): void
    {
        $key = basename($_GET['download_cloud']);
        if (!$this->isB2Configured()) {
            return;
        }

        $result = $this->downloadFromB2($key);
        if ($result['success'] && file_exists($result['path'])) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $key . '"');
            header('Content-Length: ' . filesize($result['path']));
            header('Cache-Control: no-cache, must-revalidate');
            readfile($result['path']);
            @unlink($result['path']); // Clean up temp file
            exit;
        }
    }

    // ========================================================================
    // Tier System
    // ========================================================================

    /**
     * Get all tier configurations
     */
    public function getTierConfig(): array
    {
        return self::TIERS;
    }

    /**
     * Get the highest tier available for the current license
     */
    public function getAvailableTier(): string
    {
        $editionCode = License::getEditionCode();

        // Check from highest to lowest
        foreach (array_reverse(self::TIERS) as $tierKey => $tier) {
            if (in_array($editionCode, $tier['min_edition'])) {
                return $tierKey;
            }
        }

        return 'essential';
    }

    /**
     * Check if a specific tier is available for the current license
     */
    public function isTierAvailable(string $tier): bool
    {
        $editionCode = License::getEditionCode();
        return isset(self::TIERS[$tier]) && in_array($editionCode, self::TIERS[$tier]['min_edition']);
    }

    /**
     * Get the effective tier (user's selected tier, capped by license)
     */
    public function getEffectiveTier(): string
    {
        $selected = $this->getSetting('backup_tier', 'essential');
        if ($this->isTierAvailable($selected)) {
            return $selected;
        }
        return $this->getAvailableTier();
    }

    /**
     * Estimate the size for a given tier
     */
    public function estimateTierSize(string $tier): array
    {
        $components = self::TIERS[$tier]['components'] ?? ['database'];
        $sizes = [];
        $total = 0;

        foreach ($components as $component) {
            $size = $this->estimateComponentSize($component);
            $sizes[$component] = $size;
            $total += $size;
        }

        // Estimate compressed size (rough 60% compression for mixed content)
        $compressedEstimate = (int)($total * 0.4);

        return [
            'components' => $sizes,
            'total_raw' => $total,
            'total_compressed' => $compressedEstimate,
            'total_formatted' => $this->formatBytes($total),
            'compressed_formatted' => $this->formatBytes($compressedEstimate),
        ];
    }

    private function estimateComponentSize(string $component): int
    {
        switch ($component) {
            case 'database':
                return $this->estimateDatabaseSize();
            case 'env_encrypted':
                $envFile = $this->baseDir . '/.env';
                return file_exists($envFile) ? filesize($envFile) + 100 : 1024;
            case 'themes_plugins':
                return $this->estimateDirectorySize($this->baseDir . '/content');
            case 'product_images':
                return $this->estimateDirectorySize($this->baseDir . '/public/assets/images/products');
            case 'uploads':
                $size = $this->estimateDirectorySize($this->baseDir . '/public/assets/images/uploads');
                $size += $this->estimateDirectorySize($this->baseDir . '/public/uploads');
                return $size;
            case 'code':
                $size = 0;
                foreach (['app', 'config', 'cron', 'database', 'install', 'public/assets/css', 'public/assets/js'] as $dir) {
                    $size += $this->estimateDirectorySize($this->baseDir . '/' . $dir);
                }
                return $size;
            case 'config_exports':
                return 50 * 1024; // ~50KB for JSON config exports
            default:
                return 0;
        }
    }

    private function estimateDatabaseSize(): int
    {
        try {
            $db = Database::getInstance();
            $result = $db->selectOne(
                "SELECT SUM(data_length + index_length) as total_size
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()"
            );
            return (int)($result['total_size'] ?? 0);
        } catch (\Exception $e) {
            return 5 * 1024 * 1024; // Default 5MB estimate
        }
    }

    public function estimateDirectorySize(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $size = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Exception $e) {
            // Permission error, return what we have
        }

        return $size;
    }

    // ========================================================================
    // Backblaze B2 Integration
    // ========================================================================

    public function isB2Configured(): bool
    {
        return $this->getSetting('b2_enabled')
            && !empty($this->getSetting('b2_key_id'))
            && !empty($this->getSetting('b2_application_key'))
            && !empty($this->getSetting('b2_bucket_name'))
            && !empty($this->getSetting('b2_endpoint'));
    }

    private function getB2Client(): ?BackblazeS3Client
    {
        if (!$this->isB2Configured()) {
            return null;
        }

        require_once __DIR__ . '/BackblazeS3Client.php';

        return new BackblazeS3Client(
            $this->getSetting('b2_key_id'),
            $this->getSetting('b2_application_key'),
            $this->getSetting('b2_bucket_name'),
            $this->getSetting('b2_endpoint')
        );
    }

    public function testB2Connection(): array
    {
        $client = $this->getB2Client();
        if (!$client) {
            return ['success' => false, 'error' => 'B2 not configured. Please fill in all fields and save first.'];
        }

        return $client->testConnection();
    }

    public function uploadToB2(string $localFilePath): array
    {
        $client = $this->getB2Client();
        if (!$client) {
            return ['success' => false, 'error' => 'B2 not configured'];
        }

        $filename = basename($localFilePath);
        $objectKey = 'apparix-backups/' . $filename;

        $this->log("Uploading to B2: {$filename}");

        $result = $client->putObject($objectKey, $localFilePath);

        if ($result['success']) {
            $this->saveSettings([
                'last_cloud_upload' => date('Y-m-d H:i:s'),
                'cloud_upload_status' => 'success',
                'cloud_upload_error' => null,
            ]);
            $this->log("B2 upload successful: {$filename}");

            // Apply cloud retention
            $this->applyCloudRetentionPolicy();
        } else {
            $this->saveSettings([
                'cloud_upload_status' => 'failed',
                'cloud_upload_error' => $result['error'],
                'last_error' => 'Cloud upload failed: ' . $result['error'],
                'last_error_time' => date('Y-m-d H:i:s'),
            ]);
            $this->log("B2 upload failed: " . $result['error'], 'error');
        }

        return $result;
    }

    public function listCloudBackups(): array
    {
        $client = $this->getB2Client();
        if (!$client) {
            return [];
        }

        $result = $client->listObjects('apparix-backups/', 100);

        if (!$result['success']) {
            return [];
        }

        $backups = [];
        foreach ($result['objects'] as $obj) {
            $key = $obj['key'];
            $filename = basename($key);

            // Only include backup ZIPs
            if (!str_ends_with($filename, '.zip')) {
                continue;
            }

            $backups[] = [
                'key' => $key,
                'filename' => $filename,
                'size' => $obj['size'],
                'size_formatted' => $this->formatBytes($obj['size']),
                'last_modified' => $obj['last_modified'],
                'date' => date('Y-m-d', strtotime($obj['last_modified'])),
            ];
        }

        // Sort newest first
        usort($backups, function ($a, $b) {
            return strtotime($b['last_modified']) - strtotime($a['last_modified']);
        });

        return $backups;
    }

    public function downloadFromB2(string $objectKey): array
    {
        $client = $this->getB2Client();
        if (!$client) {
            return ['success' => false, 'error' => 'B2 not configured'];
        }

        // If just a filename, prepend the prefix
        if (strpos($objectKey, '/') === false) {
            $objectKey = 'apparix-backups/' . $objectKey;
        }

        $tempPath = $this->backupDir . '/cloud_download_' . basename($objectKey);

        $result = $client->getObject($objectKey, $tempPath);
        if ($result['success']) {
            $result['path'] = $tempPath;
        }

        return $result;
    }

    public function deleteCloudBackup(string $objectKey): array
    {
        $client = $this->getB2Client();
        if (!$client) {
            return ['success' => false, 'error' => 'B2 not configured'];
        }

        if (strpos($objectKey, '/') === false) {
            $objectKey = 'apparix-backups/' . $objectKey;
        }

        return $client->deleteObject($objectKey);
    }

    public function applyCloudRetentionPolicy(): void
    {
        $retentionCount = (int)$this->getSetting('b2_retention_count', 10);
        if ($retentionCount <= 0) {
            return;
        }

        $cloudBackups = $this->listCloudBackups();
        if (count($cloudBackups) <= $retentionCount) {
            return;
        }

        // Delete oldest backups beyond retention count
        $toDelete = array_slice($cloudBackups, $retentionCount);
        foreach ($toDelete as $backup) {
            $this->deleteCloudBackup($backup['key']);
            $this->log("Cloud retention: deleted {$backup['filename']}");
        }
    }

    /**
     * Get total cloud storage usage
     */
    public function getCloudStorageInfo(): array
    {
        $backups = $this->listCloudBackups();
        $totalSize = 0;
        foreach ($backups as $b) {
            $totalSize += $b['size'];
        }

        return [
            'count' => count($backups),
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'free_tier_limit' => 10 * 1024 * 1024 * 1024, // 10 GB
            'usage_percent' => $totalSize > 0 ? round(($totalSize / (10 * 1024 * 1024 * 1024)) * 100, 1) : 0,
        ];
    }

    // ========================================================================
    // Notifications
    // ========================================================================

    /**
     * Get active notifications/warnings
     */
    public function getNotifications(): array
    {
        $notifications = [];

        // Stale backup warning
        $lastBackup = $this->getSetting('last_backup');
        $staleDays = (int)$this->getSetting('notification_stale_days', 7);

        if (!$lastBackup) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'No Backups',
                'message' => 'No backups have been created yet. Create your first backup to protect your data.',
            ];
        } elseif (strtotime($lastBackup) < strtotime("-{$staleDays} days")) {
            $daysAgo = (int)((time() - strtotime($lastBackup)) / 86400);
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Stale Backup',
                'message' => "Your last backup was {$daysAgo} days ago. Consider creating a fresh backup.",
            ];
        }

        // B2 upload failure
        if ($this->getSetting('cloud_upload_status') === 'failed') {
            $notifications[] = [
                'type' => 'error',
                'title' => 'Cloud Upload Failed',
                'message' => 'Last cloud upload failed: ' . ($this->getSetting('cloud_upload_error') ?: 'Unknown error'),
            ];
        }

        // Low disk space
        if (is_dir($this->backupDir)) {
            $freeSpace = @disk_free_space($this->backupDir);
            if ($freeSpace !== false && $freeSpace < 200 * 1024 * 1024) { // < 200MB
                $notifications[] = [
                    'type' => 'warning',
                    'title' => 'Low Disk Space',
                    'message' => 'Only ' . $this->formatBytes($freeSpace) . ' free disk space remaining. Backups may fail.',
                ];
            }
        }

        // Recent error
        $lastError = $this->getSetting('last_error');
        $lastErrorTime = $this->getSetting('last_error_time');
        if ($lastError && $lastErrorTime && strtotime($lastErrorTime) > strtotime('-24 hours')) {
            $notifications[] = [
                'type' => 'error',
                'title' => 'Recent Error',
                'message' => $lastError,
            ];
        }

        return $notifications;
    }

    // ========================================================================
    // Backup Creation (Tier-Driven)
    // ========================================================================

    /**
     * Create a backup based on the effective tier
     */
    public function createBackup(bool $cliMode = false): array
    {
        $startTime = microtime(true);
        $timestamp = date('Y-m-d_H-i-s');
        $tier = $this->getEffectiveTier();
        $backupName = "backup_{$timestamp}";
        $tempDir = $this->backupDir . "/{$backupName}";
        $lockFile = $this->backupDir . '/.backup.lock';
        $result = [
            'success' => false,
            'filename' => null,
            'filepath' => null,
            'size' => 0,
            'size_formatted' => '0 B',
            'duration' => 0,
            'tier' => $tier,
            'details' => [],
            'errors' => [],
        ];

        try {
            // Check for concurrent backup
            if (file_exists($lockFile)) {
                $lockTime = filemtime($lockFile);
                if ((time() - $lockTime) > 3600) {
                    unlink($lockFile);
                    $this->log("Removed stale backup lock file", 'warning');
                } else {
                    throw new \Exception("Another backup is currently in progress. Please wait.");
                }
            }

            file_put_contents($lockFile, date('Y-m-d H:i:s') . ' - PID: ' . getmypid());

            // Check disk space
            $freeSpace = disk_free_space($this->backupDir);
            $minRequired = 100 * 1024 * 1024;
            if ($freeSpace < $minRequired) {
                throw new \Exception("Insufficient disk space. Required: 100MB, Available: " . $this->formatBytes($freeSpace));
            }

            if (!mkdir($tempDir, 0755, true)) {
                throw new \Exception("Failed to create temporary backup directory");
            }

            $components = self::TIERS[$tier]['components'];

            // Execute each component
            foreach ($components as $component) {
                $this->yieldControl();
                $componentResult = $this->backupComponent($component, $tempDir, $cliMode);
                $result['details'][$component] = $componentResult;
                if (isset($componentResult['error'])) {
                    $result['errors'][] = "{$component}: " . $componentResult['error'];
                }
            }

            // Create manifest
            $this->createManifest($tempDir, $result['details'], $tier);

            // Compress
            $this->yieldControl();
            $archivePath = $this->compressBackup($tempDir, $backupName);

            if ($archivePath && file_exists($archivePath)) {
                $result['success'] = true;
                $result['filename'] = basename($archivePath);
                $result['filepath'] = $archivePath;
                $result['size'] = filesize($archivePath);
                $result['size_formatted'] = $this->formatBytes($result['size']);
            }

            $this->deleteDirectory($tempDir);
            $this->applyRetentionPolicy();
            $this->saveSettings([
                'last_backup' => date('Y-m-d H:i:s'),
                'last_error' => null,
                'last_error_time' => null,
            ]);

        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
            $this->log("Backup failed: " . $e->getMessage(), 'error');
            $this->saveSettings([
                'last_error' => $e->getMessage(),
                'last_error_time' => date('Y-m-d H:i:s'),
            ]);

            if (is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
        } finally {
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
        }

        $result['duration'] = round(microtime(true) - $startTime, 2);
        $this->log("Backup completed ({$tier} tier) in {$result['duration']}s - " . ($result['success'] ? 'SUCCESS' : 'FAILED'));

        return $result;
    }

    /**
     * Execute a single backup component
     */
    private function backupComponent(string $component, string $tempDir, bool $cliMode): array
    {
        switch ($component) {
            case 'database':
                return $this->backupDatabase($tempDir);
            case 'env_encrypted':
                return $this->backupEnvEncrypted($tempDir);
            case 'themes_plugins':
                return $this->backupContentDirectory($tempDir);
            case 'product_images':
                return $this->backupProductImages($tempDir);
            case 'uploads':
                return $this->backupUploads($tempDir);
            case 'code':
                return $this->backupCode($tempDir, $cliMode);
            case 'config_exports':
                $configDir = $tempDir . '/config';
                if (!is_dir($configDir)) {
                    mkdir($configDir, 0755, true);
                }
                $this->exportSettings($configDir);
                return ['success' => true, 'files' => 1];
            default:
                return ['success' => false, 'error' => 'Unknown component: ' . $component];
        }
    }

    /**
     * Backup .env file encrypted with AES-256-CBC using APP_SECRET
     */
    private function backupEnvEncrypted(string $tempDir): array
    {
        $envFile = $this->baseDir . '/.env';
        if (!file_exists($envFile)) {
            return ['success' => false, 'error' => '.env file not found'];
        }

        try {
            $envContent = file_get_contents($envFile);

            // Get encryption key from APP_SECRET or fallback
            $secret = $_ENV['APP_SECRET'] ?? $_ENV['APP_KEY'] ?? 'apparix-backup-default-key';
            $key = hash('sha256', $secret, true);
            $iv = openssl_random_pseudo_bytes(16);

            $encrypted = openssl_encrypt($envContent, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            if ($encrypted === false) {
                throw new \Exception('Encryption failed');
            }

            // Store IV + encrypted data
            $outputPath = $tempDir . '/.env.encrypted';
            file_put_contents($outputPath, $iv . $encrypted);

            // Also store a note about decryption
            file_put_contents($tempDir . '/ENV_DECRYPT_README.txt',
                "The .env.encrypted file is encrypted with AES-256-CBC.\n" .
                "To decrypt, use your APP_SECRET from your .env file.\n" .
                "The first 16 bytes of the file are the IV.\n" .
                "Key = SHA-256 hash of your APP_SECRET.\n"
            );

            return [
                'success' => true,
                'size' => filesize($outputPath),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Backup content directory (themes and plugins)
     */
    private function backupContentDirectory(string $tempDir): array
    {
        $contentDir = $this->baseDir . '/content';
        if (!is_dir($contentDir)) {
            return ['success' => true, 'files' => 0];
        }

        $destDir = $tempDir . '/content';
        $result = $this->copyDirectoryRecursive($contentDir, $destDir);
        $result['success'] = true;
        return $result;
    }

    /**
     * Backup product images
     */
    private function backupProductImages(string $tempDir): array
    {
        $imagesDir = $this->baseDir . '/public/assets/images/products';
        if (!is_dir($imagesDir)) {
            return ['success' => true, 'files' => 0, 'size' => 0];
        }

        $destDir = $tempDir . '/images/products';
        $result = $this->copyDirectoryRecursive($imagesDir, $destDir, true);
        $result['success'] = true;
        return $result;
    }

    /**
     * Backup user uploads
     */
    private function backupUploads(string $tempDir): array
    {
        $totalFiles = 0;
        $totalSize = 0;

        $uploadDirs = [
            $this->baseDir . '/public/assets/images/uploads' => $tempDir . '/uploads/images',
            $this->baseDir . '/public/uploads' => $tempDir . '/uploads/public',
        ];

        foreach ($uploadDirs as $source => $dest) {
            if (is_dir($source)) {
                $r = $this->copyDirectoryRecursive($source, $dest, true);
                $totalFiles += $r['files'];
                $totalSize += $r['size'];
            }
        }

        return ['success' => true, 'files' => $totalFiles, 'size' => $totalSize];
    }

    /**
     * Recursively copy a directory
     */
    private function copyDirectoryRecursive(string $source, string $dest, bool $includeMedia = false): array
    {
        $result = ['files' => 0, 'size' => 0];
        $excludeExts = ['log', 'lock'];

        if (!$includeMedia) {
            $excludeExts = array_merge($excludeExts, [
                'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico',
                'mp4', 'avi', 'mov', 'webm', 'mp3', 'wav', 'ogg',
                'zip', 'tar', 'gz', 'rar', 'pdf',
            ]);
        }

        $excludeDirs = ['node_modules', 'vendor', '.git', '.claude', 'storage'];

        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $iterator = new \DirectoryIterator($source);
        foreach ($iterator as $item) {
            if ($item->isDot()) {
                continue;
            }

            $name = $item->getFilename();
            $sourcePath = $item->getPathname();
            $destPath = $dest . '/' . $name;

            if ($item->isDir()) {
                if (in_array($name, $excludeDirs)) {
                    continue;
                }
                $sub = $this->copyDirectoryRecursive($sourcePath, $destPath, $includeMedia);
                $result['files'] += $sub['files'];
                $result['size'] += $sub['size'];
            } else {
                $ext = strtolower($item->getExtension());
                if (in_array($ext, $excludeExts)) {
                    continue;
                }

                copy($sourcePath, $destPath);
                $result['files']++;
                $result['size'] += $item->getSize();
            }

            if ($result['files'] % 50 === 0) {
                $this->yieldControl();
            }
        }

        return $result;
    }

    // ========================================================================
    // Database Backup
    // ========================================================================

    private function backupDatabase(string $tempDir): array
    {
        $result = ['success' => false, 'tables' => 0, 'size' => 0];

        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();

            $sqlFile = $tempDir . '/database.sql';
            $handle = fopen($sqlFile, 'w');
            if (!$handle) {
                throw new \Exception("Cannot create database dump file");
            }

            fwrite($handle, "-- Apparix Database Backup\n");
            fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Database: " . ($_ENV['DB_NAME'] ?? 'unknown') . "\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
            $result['tables'] = count($tables);

            foreach ($tables as $table) {
                $this->yieldControl();

                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
                fwrite($handle, "-- Table: {$table}\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($handle, $createStmt['Create Table'] . ";\n\n");

                $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
                $rowCount = $countStmt->fetchColumn();

                if ($rowCount > 0) {
                    $chunkSize = 500;
                    $offset = 0;

                    while ($offset < $rowCount) {
                        $rows = $pdo->query(
                            "SELECT * FROM `{$table}` LIMIT {$chunkSize} OFFSET {$offset}"
                        )->fetchAll(\PDO::FETCH_ASSOC);

                        if (empty($rows)) {
                            break;
                        }

                        foreach ($rows as $row) {
                            $values = array_map(function ($value) use ($pdo) {
                                if ($value === null) {
                                    return 'NULL';
                                }
                                return $pdo->quote($value);
                            }, $row);

                            $columns = array_map(function ($col) {
                                return "`{$col}`";
                            }, array_keys($row));

                            fwrite($handle,
                                "INSERT INTO `{$table}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n"
                            );
                        }

                        $offset += $chunkSize;
                        if ($offset < $rowCount) {
                            $this->yieldControl();
                        }
                    }
                    fwrite($handle, "\n");
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);

            $result['success'] = true;
            $result['size'] = filesize($sqlFile);

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            $this->log("Database backup error: " . $e->getMessage(), 'error');
        }

        return $result;
    }

    // ========================================================================
    // Code Backup (Complete tier)
    // ========================================================================

    private function backupCode(string $tempDir, bool $cliMode = false): array
    {
        $result = ['success' => false, 'files' => 0, 'size' => 0];
        $codeDir = $tempDir . '/code';

        try {
            mkdir($codeDir, 0755, true);

            $directories = [
                'app', 'config', 'cron', 'database', 'install', 'newsletter', 'scripts',
                'public/assets/css', 'public/assets/js',
            ];

            $files = [
                'public/index.php', 'public/404.php',
                'composer.json', 'composer.lock',
                '.htaccess', 'version.php',
            ];

            foreach ($directories as $dir) {
                $sourcePath = $this->baseDir . '/' . $dir;
                if (is_dir($sourcePath)) {
                    $destPath = $codeDir . '/' . $dir;
                    $copyResult = $this->copyDirectoryRecursive($sourcePath, $destPath);
                    $result['files'] += $copyResult['files'];
                    $result['size'] += $copyResult['size'];
                }
                $this->yieldControl();
            }

            foreach ($files as $file) {
                $sourcePath = $this->baseDir . '/' . $file;
                if (file_exists($sourcePath)) {
                    $destDir = $codeDir . '/' . dirname($file);
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    copy($sourcePath, $codeDir . '/' . $file);
                    $result['files']++;
                    $result['size'] += filesize($sourcePath);
                }
            }

            $result['success'] = true;
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    // ========================================================================
    // Config Export
    // ========================================================================

    private function exportSettings(string $configDir): void
    {
        try {
            $db = Database::getInstance();

            $settings = $db->select("SELECT * FROM settings");
            file_put_contents($configDir . '/settings.json', json_encode($settings, JSON_PRETTY_PRINT));

            $themes = $db->select("SELECT * FROM themes");
            file_put_contents($configDir . '/themes.json', json_encode($themes, JSON_PRETTY_PRINT));

            $plugins = $db->select("SELECT slug, name, is_active, settings FROM plugins");
            file_put_contents($configDir . '/plugins.json', json_encode($plugins, JSON_PRETTY_PRINT));

            $shipping = [
                'methods' => $db->select("SELECT * FROM shipping_methods"),
                'zones' => $db->select("SELECT * FROM shipping_zones"),
                'rates' => $db->select("SELECT * FROM shipping_rates"),
            ];
            file_put_contents($configDir . '/shipping.json', json_encode($shipping, JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            $this->log("Settings export error: " . $e->getMessage(), 'warning');
        }
    }

    // ========================================================================
    // Backup Management
    // ========================================================================

    private function compressBackup(string $tempDir, string $backupName): ?string
    {
        $archivePath = $this->backupDir . "/{$backupName}.zip";

        try {
            $zip = new \ZipArchive();
            if ($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \Exception("Cannot create ZIP archive");
            }

            $this->addDirectoryToZip($zip, $tempDir, '');
            $zip->close();

            return $archivePath;
        } catch (\Exception $e) {
            $this->log("Compression error: " . $e->getMessage(), 'error');
            return null;
        }
    }

    private function addDirectoryToZip(\ZipArchive $zip, string $dir, string $zipPath): void
    {
        $iterator = new \DirectoryIterator($dir);
        foreach ($iterator as $item) {
            if ($item->isDot()) {
                continue;
            }

            $filePath = $item->getPathname();
            $localPath = $zipPath ? $zipPath . '/' . $item->getFilename() : $item->getFilename();

            if ($item->isDir()) {
                $zip->addEmptyDir($localPath);
                $this->addDirectoryToZip($zip, $filePath, $localPath);
            } else {
                $zip->addFile($filePath, $localPath);
            }
        }
    }

    private function applyRetentionPolicy(): void
    {
        $retentionCount = (int)$this->getSetting('retention_count', 5);
        if ($retentionCount <= 0) {
            return;
        }

        $backups = $this->listBackups();
        if (count($backups) <= $retentionCount) {
            return;
        }

        usort($backups, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        $toDelete = array_slice($backups, $retentionCount);
        foreach ($toDelete as $backup) {
            $this->deleteBackup($backup['filename']);
        }

        $this->log("Retention policy applied: deleted " . count($toDelete) . " old backup(s)");
    }

    public function listBackups(): array
    {
        $backups = [];
        if (!is_dir($this->backupDir)) {
            return $backups;
        }

        $files = glob($this->backupDir . '/backup_*.zip');
        foreach ($files as $file) {
            $filename = basename($file);
            preg_match('/backup_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.zip/', $filename, $matches);

            $backups[] = [
                'filename' => $filename,
                'filepath' => $file,
                'size' => filesize($file),
                'size_formatted' => $this->formatBytes(filesize($file)),
                'created_at' => isset($matches[1]) ? str_replace('_', ' ', str_replace('-', ':', substr($matches[1], 11))) : filemtime($file),
                'created_date' => isset($matches[1]) ? substr($matches[1], 0, 10) : date('Y-m-d', filemtime($file)),
            ];
        }

        usort($backups, function ($a, $b) {
            return filemtime($b['filepath']) - filemtime($a['filepath']);
        });

        return $backups;
    }

    public function deleteBackup(string $filename): bool
    {
        $filename = basename($filename);

        if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.zip$/', $filename)) {
            $this->log("Invalid backup filename format for deletion: {$filename}", 'warning');
            return false;
        }

        $filepath = $this->backupDir . '/' . $filename;
        $realBackupDir = realpath($this->backupDir);
        $realFilepath = realpath($filepath);

        if ($realFilepath === false || strpos($realFilepath, $realBackupDir) !== 0) {
            $this->log("Path traversal attempt detected: {$filename}", 'warning');
            return false;
        }

        if (file_exists($filepath)) {
            return unlink($filepath);
        }

        return false;
    }

    public function getBackupPath(string $filename): ?string
    {
        $filename = basename($filename);

        if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.zip$/', $filename)) {
            return null;
        }

        $filepath = $this->backupDir . '/' . $filename;
        $realBackupDir = realpath($this->backupDir);
        $realFilepath = realpath($filepath);

        if ($realFilepath === false || strpos($realFilepath, $realBackupDir) !== 0) {
            return null;
        }

        return file_exists($filepath) ? $filepath : null;
    }

    public function getStorageInfo(): array
    {
        $backups = $this->listBackups();
        $totalSize = array_sum(array_column($backups, 'size'));

        return [
            'backup_count' => count($backups),
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'backup_dir' => $this->backupDir,
            'disk_free' => disk_free_space($this->backupDir),
            'disk_free_formatted' => $this->formatBytes(disk_free_space($this->backupDir)),
        ];
    }

    private function createManifest(string $tempDir, array $details, string $tier): void
    {
        $manifest = [
            'created_at' => date('Y-m-d H:i:s'),
            'apparix_version' => $this->getApparixVersion(),
            'php_version' => PHP_VERSION,
            'backup_plugin_version' => '2.0.0',
            'tier' => $tier,
            'contents' => $details,
        ];

        file_put_contents($tempDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
    }

    public function shouldRunScheduledBackup(): bool
    {
        if (!$this->getSetting('auto_backup', false)) {
            return false;
        }

        $lastBackup = $this->getSetting('last_backup');
        if (!$lastBackup) {
            return true;
        }

        $lastBackupTime = strtotime($lastBackup);
        $schedule = $this->getSetting('backup_schedule', 'daily');

        switch ($schedule) {
            case 'hourly':
                return (time() - $lastBackupTime) >= 3600;
            case 'daily':
                return (time() - $lastBackupTime) >= 86400;
            case 'weekly':
                return (time() - $lastBackupTime) >= 604800;
            default:
                return false;
        }
    }

    // ========================================================================
    // Utilities
    // ========================================================================

    private function yieldControl(): void
    {
        usleep(10000);
        clearstatcache();
    }

    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        return rmdir($dir);
    }

    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }

    private function getApparixVersion(): string
    {
        $versionFile = $this->baseDir . '/version.php';
        if (file_exists($versionFile)) {
            include $versionFile;
            return defined('APPARIX_VERSION') ? APPARIX_VERSION : '1.0.0';
        }
        return '1.0.0';
    }

    private function log(string $message, string $level = 'info'): void
    {
        $logFile = $this->baseDir . '/storage/logs/backup.log';
        $timestamp = date('Y-m-d H:i:s');
        $entry = "[{$timestamp}] [{$level}] {$message}\n";

        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
