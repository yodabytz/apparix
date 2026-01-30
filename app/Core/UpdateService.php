<?php

namespace App\Core;

/**
 * UpdateService - Handles checking for and installing software updates
 *
 * This service connects to the Apparix update server to check for new versions
 * and can automatically download and install updates.
 */
class UpdateService
{
    private string $updateServer;
    private string $currentVersion;
    private string $licenseKey;
    private string $domain;
    private string $tempDir;
    private string $backupDir;

    public function __construct()
    {
        $versionInfo = $this->getVersionInfo();
        $this->updateServer = $versionInfo['update_server'] ?? 'https://apparix.vibrixmedia.com';
        $this->currentVersion = $versionInfo['version'] ?? '1.0.0';
        $this->licenseKey = $_ENV['LICENSE_KEY'] ?? '';
        $this->domain = $this->getCurrentDomain();
        $this->tempDir = BASE_PATH . '/storage/updates_temp';
        $this->backupDir = BASE_PATH . '/storage/backups';
    }

    /**
     * Get current version information
     */
    public function getVersionInfo(): array
    {
        $versionFile = BASE_PATH . '/version.php';
        if (file_exists($versionFile)) {
            return include $versionFile;
        }
        return [
            'version' => '1.0.0',
            'product' => 'Apparix E-Commerce Platform',
            'update_server' => 'https://apparix.vibrixmedia.com'
        ];
    }

    /**
     * Check for available updates
     */
    public function checkForUpdates(): array
    {
        if (empty($this->licenseKey)) {
            return [
                'success' => false,
                'error' => 'No license key configured'
            ];
        }

        $url = $this->updateServer . '/api/updates/check';

        $data = [
            'license_key' => $this->licenseKey,
            'current_version' => $this->currentVersion,
            'domain' => $this->domain,
            'php_version' => PHP_VERSION
        ];

        $response = $this->makeRequest($url, $data);

        if (!$response['success']) {
            return $response;
        }

        return $response;
    }

    /**
     * Download and install an update
     */
    public function installUpdate(string $targetVersion): array
    {
        try {
            // Step 1: Create backup
            $backupResult = $this->createBackup();
            if (!$backupResult['success']) {
                return $backupResult;
            }

            // Step 2: Download update
            $downloadResult = $this->downloadUpdate($targetVersion);
            if (!$downloadResult['success']) {
                return $downloadResult;
            }

            // Step 3: Extract update
            $extractResult = $this->extractUpdate($downloadResult['file']);
            if (!$extractResult['success']) {
                $this->cleanupTemp();
                return $extractResult;
            }

            // Step 4: Apply update
            $applyResult = $this->applyUpdate($extractResult['path']);
            if (!$applyResult['success']) {
                $this->restoreBackup($backupResult['backup_path']);
                $this->cleanupTemp();
                return $applyResult;
            }

            // Step 5: Update version file
            $this->updateVersionFile($targetVersion);

            // Step 6: Run post-update migrations
            $this->runMigrations();

            // Step 7: Cleanup
            $this->cleanupTemp();

            // Step 8: Report success
            $this->reportUpdateStatus($targetVersion, 'installed');

            return [
                'success' => true,
                'message' => 'Successfully updated to version ' . $targetVersion,
                'version' => $targetVersion
            ];

        } catch (\Exception $e) {
            $this->reportUpdateStatus($targetVersion, 'failed', $e->getMessage());
            return [
                'success' => false,
                'error' => 'Update failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Download the update file
     */
    private function downloadUpdate(string $targetVersion): array
    {
        $url = $this->updateServer . '/api/updates/download';

        $data = [
            'license_key' => $this->licenseKey,
            'domain' => $this->domain,
            'target_version' => $targetVersion,
            'current_version' => $this->currentVersion
        ];

        // Ensure temp directory exists
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }

        $tempFile = $this->tempDir . '/update-' . $targetVersion . '.tar.gz';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        // Check if we got JSON (error) or binary (file)
        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode($response, true);
            return [
                'success' => false,
                'error' => $data['error'] ?? 'Download failed'
            ];
        }

        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'Download failed with HTTP code: ' . $httpCode
            ];
        }

        // Save the file
        if (file_put_contents($tempFile, $response) === false) {
            return [
                'success' => false,
                'error' => 'Failed to save update file'
            ];
        }

        return [
            'success' => true,
            'file' => $tempFile
        ];
    }

    /**
     * Extract the update archive using PharData (PHP built-in)
     */
    private function extractUpdate(string $archivePath): array
    {
        $extractPath = $this->tempDir . '/extracted';

        if (!is_dir($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        try {
            $phar = new \PharData($archivePath);
            $phar->extractTo($extractPath, null, true);

            return [
                'success' => true,
                'path' => $extractPath
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to extract update: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Apply the update (copy files)
     */
    private function applyUpdate(string $sourcePath): array
    {
        // Find the actual source directory (might be nested)
        $dirs = glob($sourcePath . '/*', GLOB_ONLYDIR);
        if (count($dirs) === 1) {
            $sourcePath = $dirs[0];
        }

        // Files/directories to exclude from update
        $exclude = [
            '.env',
            '.env.example',
            'storage/logs',
            'storage/sessions',
            'storage/uploads',
            'storage/downloads',
            'storage/updates',
            'storage/updates_temp',
            'storage/backups',
            'public/assets/images/products',
            'public/assets/images/uploads',
            'tools/generate-license.php',
            '.git',
            '.gitignore'
        ];

        // Recursively copy files
        $this->copyDirectory($sourcePath, BASE_PATH, $exclude);

        return ['success' => true];
    }

    /**
     * Recursively copy directory
     */
    private function copyDirectory(string $source, string $dest, array $exclude = []): void
    {
        $dir = opendir($source);

        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcPath = $source . '/' . $file;
            $destPath = $dest . '/' . $file;
            $relativePath = str_replace(BASE_PATH . '/', '', $destPath);

            // Check exclusions
            $skip = false;
            foreach ($exclude as $pattern) {
                if (strpos($relativePath, $pattern) === 0) {
                    $skip = true;
                    break;
                }
            }

            if ($skip) {
                continue;
            }

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $destPath, $exclude);
            } else {
                copy($srcPath, $destPath);
                chmod($destPath, 0644);
            }
        }

        closedir($dir);
    }

    /**
     * Create a backup before updating using PharData
     */
    private function createBackup(): array
    {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        $backupName = 'backup-' . $this->currentVersion . '-' . date('Y-m-d-His');
        $backupPath = $this->backupDir . '/' . $backupName . '.tar';
        $gzPath = $backupPath . '.gz';

        try {
            $phar = new \PharData($backupPath);

            // Add critical directories
            $dirsToBackup = ['app', 'version.php'];

            foreach ($dirsToBackup as $item) {
                $fullPath = BASE_PATH . '/' . $item;
                if (is_dir($fullPath)) {
                    $phar->buildFromDirectory($fullPath);
                } elseif (is_file($fullPath)) {
                    $phar->addFile($fullPath, $item);
                }
            }

            // Compress to gzip
            $phar->compress(\Phar::GZ);

            // Remove uncompressed tar
            if (file_exists($backupPath)) {
                unlink($backupPath);
            }

            return [
                'success' => true,
                'backup_path' => $gzPath
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to create backup: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Restore from backup
     */
    private function restoreBackup(string $backupPath): bool
    {
        if (!file_exists($backupPath)) {
            return false;
        }

        try {
            $phar = new \PharData($backupPath);
            $phar->extractTo(BASE_PATH, null, true);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update the version file
     */
    private function updateVersionFile(string $newVersion): void
    {
        $parts = explode('.', $newVersion);

        $content = "<?php\n";
        $content .= "/**\n";
        $content .= " * Apparix Version Information\n";
        $content .= " * This file is automatically updated during the update process\n";
        $content .= " */\n";
        $content .= "return [\n";
        $content .= "    'version' => '" . $newVersion . "',\n";
        $content .= "    'version_major' => " . ($parts[0] ?? 1) . ",\n";
        $content .= "    'version_minor' => " . ($parts[1] ?? 0) . ",\n";
        $content .= "    'version_patch' => " . ($parts[2] ?? 0) . ",\n";
        $content .= "    'release_date' => '" . date('Y-m-d') . "',\n";
        $content .= "    'product' => 'Apparix E-Commerce Platform',\n";
        $content .= "    'update_server' => '" . $this->updateServer . "'\n";
        $content .= "];\n";

        file_put_contents(BASE_PATH . '/version.php', $content);
    }

    /**
     * Run database migrations
     */
    private function runMigrations(): void
    {
        $migrationsDir = BASE_PATH . '/database/migrations';
        if (!is_dir($migrationsDir)) {
            return;
        }

        // Get all migration files
        $files = glob($migrationsDir . '/*.sql');
        sort($files);

        $db = Database::getInstance();

        // Create migrations table if not exists
        $db->query("CREATE TABLE IF NOT EXISTS migrations (
            id INT PRIMARY KEY AUTO_INCREMENT,
            migration VARCHAR(255) NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY idx_migration (migration)
        )");

        foreach ($files as $file) {
            $migration = basename($file);

            // Check if already executed
            $exists = $db->selectOne(
                "SELECT id FROM migrations WHERE migration = ?",
                [$migration]
            );

            if ($exists) {
                continue;
            }

            // Execute migration
            $sql = file_get_contents($file);
            try {
                // Split by semicolon and execute each statement
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $db->query($statement);
                    }
                }

                // Record migration
                $db->insert(
                    "INSERT INTO migrations (migration) VALUES (?)",
                    [$migration]
                );
            } catch (\Exception $e) {
                // Log but don't fail on migration errors
                error_log("Migration error in {$migration}: " . $e->getMessage());
            }
        }
    }

    /**
     * Cleanup temporary files
     */
    private function cleanupTemp(): void
    {
        if (is_dir($this->tempDir)) {
            $this->deleteDirectory($this->tempDir);
        }
    }

    /**
     * Recursively delete directory
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * Report update status to server
     */
    private function reportUpdateStatus(string $version, string $status, string $errorMessage = ''): void
    {
        $url = $this->updateServer . '/api/updates/report';

        $data = [
            'license_key' => $this->licenseKey,
            'domain' => $this->domain,
            'version' => $version,
            'status' => $status,
            'error_message' => $errorMessage
        ];

        // Non-blocking - we don't care about the response
        $this->makeRequest($url, $data);
    }

    /**
     * Make HTTP request
     */
    private function makeRequest(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error' => 'Connection error: ' . $error
            ];
        }

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            return [
                'success' => false,
                'error' => $data['error'] ?? 'Server error (HTTP ' . $httpCode . ')'
            ];
        }

        return $data ?: ['success' => false, 'error' => 'Invalid response'];
    }

    /**
     * Get current domain
     */
    private function getCurrentDomain(): string
    {
        $domain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $domain = preg_replace('/:\d+$/', '', $domain);
        $domain = preg_replace('/^www\./', '', strtolower($domain));
        return $domain;
    }

    /**
     * Get list of available backups
     */
    public function getBackups(): array
    {
        if (!is_dir($this->backupDir)) {
            return [];
        }

        $backups = [];
        $files = glob($this->backupDir . '/*.tar.gz');

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'created' => filemtime($file)
            ];
        }

        // Sort by date, newest first
        usort($backups, fn($a, $b) => $b['created'] - $a['created']);

        return $backups;
    }

    /**
     * Delete old backups (keep last N)
     */
    public function cleanupBackups(int $keepCount = 5): int
    {
        $backups = $this->getBackups();
        $deleted = 0;

        if (count($backups) <= $keepCount) {
            return 0;
        }

        $toDelete = array_slice($backups, $keepCount);

        foreach ($toDelete as $backup) {
            if (unlink($backup['path'])) {
                $deleted++;
            }
        }

        return $deleted;
    }
}
