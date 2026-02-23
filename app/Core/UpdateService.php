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
        $this->updateServer = $versionInfo['update_server'] ?? 'https://apparix.app';
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
            'update_server' => 'https://apparix.app'
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
     * Check for updates with caching (for background/dashboard checks)
     * Only contacts the update server at most once per interval.
     * Returns cached result otherwise.
     */
    public function checkForUpdatesCached(int $cacheSeconds = 43200): ?array
    {
        $cacheFile = BASE_PATH . '/storage/cache/update_check.json';

        // Check if cache exists and is fresh
        if (file_exists($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached && isset($cached['checked_at']) && (time() - $cached['checked_at']) < $cacheSeconds) {
                return $cached;
            }
        }

        // Perform fresh check
        $result = $this->checkForUpdates();

        // Cache the result (even failures, to avoid hammering the server)
        $result['checked_at'] = time();
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        file_put_contents($cacheFile, json_encode($result));

        return $result;
    }

    /**
     * Clear the update check cache (e.g., after installing an update)
     */
    public function clearUpdateCache(): void
    {
        $cacheFile = BASE_PATH . '/storage/cache/update_check.json';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    /**
     * Download and install an update
     */
    public function installUpdate(string $targetVersion): array
    {
        // Suppress PHP warnings from polluting the response buffer
        $previousErrorReporting = error_reporting();
        error_reporting(E_ERROR | E_PARSE);

        try {
            // Step 0: Pre-flight checks
            $preflightResult = $this->preflightCheck();
            if (!$preflightResult['success']) {
                error_reporting($previousErrorReporting);
                return $preflightResult;
            }

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

            // Step 8: Clear update cache so dashboard reflects new version
            $this->clearUpdateCache();

            // Step 9: Report success
            $this->reportUpdateStatus($targetVersion, 'installed');

            error_reporting($previousErrorReporting);

            return [
                'success' => true,
                'message' => 'Successfully updated to version ' . $targetVersion,
                'version' => $targetVersion
            ];

        } catch (\Exception $e) {
            error_reporting($previousErrorReporting);
            $this->reportUpdateStatus($targetVersion, 'failed', $e->getMessage());
            return [
                'success' => false,
                'error' => 'Update failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Pre-flight checks to ensure the environment is ready for updates
     */
    private function preflightCheck(): array
    {
        $errors = [];

        // Check BASE_PATH is writable
        if (!is_writable(BASE_PATH)) {
            $errors[] = 'Application directory is not writable';
        }

        // Check key subdirectories are writable
        $writableDirs = ['app', 'public', 'public/assets/css', 'public/assets/js'];
        foreach ($writableDirs as $dir) {
            $fullPath = BASE_PATH . '/' . $dir;
            if (is_dir($fullPath) && !is_writable($fullPath)) {
                $errors[] = $dir . '/ is not writable';
            }
        }

        // Ensure required storage directories exist
        $storageDirs = [
            $this->tempDir,
            $this->backupDir,
            BASE_PATH . '/storage/cache',
        ];
        foreach ($storageDirs as $dir) {
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0755, true)) {
                    $errors[] = 'Cannot create directory: ' . basename($dir);
                }
            } elseif (!is_writable($dir)) {
                $errors[] = basename($dir) . '/ is not writable';
            }
        }

        // Check PHP extensions
        if (!class_exists('PharData')) {
            $errors[] = 'PHP Phar extension is required but not available';
        }
        if (!function_exists('curl_init')) {
            $errors[] = 'PHP cURL extension is required but not available';
        }

        // Check disk space (need at least 100MB free)
        $freeSpace = @disk_free_space(BASE_PATH);
        if ($freeSpace !== false && $freeSpace < 104857600) {
            $errors[] = 'Insufficient disk space (need at least 100MB free)';
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'error' => 'Pre-flight check failed: ' . implode('; ', $errors)
            ];
        }

        return ['success' => true];
    }

    /**
     * Download the update file with hash verification
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
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HEADER => true
        ]);

        $fullResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headers = substr($fullResponse, 0, $headerSize);
        $response = substr($fullResponse, $headerSize);

        // Check if we got JSON (error) or binary (file)
        if (strpos($headers, 'application/json') !== false) {
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

        // Verify file hash if provided by server
        if (preg_match('/X-File-Hash:\s*(\S+)/i', $headers, $matches)) {
            $expectedHash = trim($matches[1]);
            $actualHash = hash_file('sha256', $tempFile);
            if ($actualHash !== $expectedHash) {
                unlink($tempFile);
                return [
                    'success' => false,
                    'error' => 'File integrity check failed. The download may be corrupted.'
                ];
            }
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
     * Apply the update (copy files, protecting customer data)
     */
    private function applyUpdate(string $sourcePath): array
    {
        // Detect if update files are nested in a single subdirectory
        // Only descend if that single dir contains typical app structure
        $dirs = glob($sourcePath . '/*', GLOB_ONLYDIR);
        $files = glob($sourcePath . '/*');
        if (count($dirs) === 1 && count($files) === 1) {
            // Only one item and it's a directory — likely a wrapper dir
            $sourcePath = $dirs[0];
        }

        // Files/directories to NEVER overwrite during updates
        // These contain customer-specific data, settings, and uploads
        $exclude = [
            // Environment & configuration
            '.env',
            '.env.example',
            'config/database.php',

            // Customer data directories
            'storage/.installed',
            'storage/logs',
            'storage/sessions',
            'storage/uploads',
            'storage/downloads',
            'storage/updates',
            'storage/updates_temp',
            'storage/backups',
            'storage/cache',

            // Customer uploaded content
            'public/assets/images/products',
            'public/assets/images/uploads',

            // Customer-installed plugins and themes
            'content/plugins',
            'content/themes',

            // Composer dependencies (customer may have different versions)
            'vendor',

            // Development/build files
            'tools/generate-license.php',
            '.git',
            '.gitignore',
            '.claudeignore',
            'node_modules',
        ];

        // Pre-flight: check that BASE_PATH is writable
        if (!is_writable(BASE_PATH)) {
            return [
                'success' => false,
                'error' => 'Installation directory is not writable. Please check file permissions.'
            ];
        }

        // Recursively copy files, collecting any failures
        $failures = [];
        $this->copyDirectory($sourcePath, BASE_PATH, $exclude, $failures);

        if (!empty($failures)) {
            $count = count($failures);
            $sample = array_slice($failures, 0, 3);
            $msg = "Failed to update {$count} file(s) due to permissions: " . implode(', ', $sample);
            if ($count > 3) {
                $msg .= " and " . ($count - 3) . " more";
            }
            $msg .= ". Please ensure the web server has write access to the application directory.";
            return [
                'success' => false,
                'error' => $msg
            ];
        }

        return ['success' => true];
    }

    /**
     * Recursively copy directory, tracking failures instead of emitting warnings
     */
    private function copyDirectory(string $source, string $dest, array $exclude = [], array &$failures = []): void
    {
        $dir = @opendir($source);
        if (!$dir) {
            $failures[] = str_replace(BASE_PATH . '/', '', $dest) . '/ (cannot read source)';
            return;
        }

        if (!is_dir($dest)) {
            if (!@mkdir($dest, 0755, true)) {
                $failures[] = str_replace(BASE_PATH . '/', '', $dest) . '/ (cannot create directory)';
                closedir($dir);
                return;
            }
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
                if ($relativePath === $pattern || strpos($relativePath, $pattern . '/') === 0) {
                    $skip = true;
                    break;
                }
            }

            if ($skip) {
                continue;
            }

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $destPath, $exclude, $failures);
            } else {
                if (!@copy($srcPath, $destPath)) {
                    $failures[] = $relativePath;
                } else {
                    @chmod($destPath, 0644);
                }
            }
        }

        closedir($dir);
    }

    /**
     * Create a backup before updating
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

            // Back up all critical directories and files that the update will change
            $itemsToBackup = [
                'app',
                'public/index.php',
                'public/assets/css',
                'public/assets/js',
                'database/migrations',
                'config',
                'version.php',
            ];

            foreach ($itemsToBackup as $item) {
                $fullPath = BASE_PATH . '/' . $item;
                if (is_dir($fullPath)) {
                    $this->addDirectoryToArchive($phar, $fullPath, $item);
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
     * Recursively add a directory to a PharData archive
     */
    private function addDirectoryToArchive(\PharData $phar, string $dirPath, string $localPrefix): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $realPath = $file->getRealPath();
            $relativePath = $localPrefix . '/' . $iterator->getSubPathName();

            if ($file->isDir()) {
                $phar->addEmptyDir($relativePath);
            } else {
                $phar->addFile($realPath, $relativePath);
            }
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
     * Run database migrations using mysql CLI for reliable multi-statement support
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
        $pdo = $db->getConnection();

        // Create migrations table if not exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
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

            // Execute migration via mysql CLI for reliable multi-statement support
            // This handles PREPARE/EXECUTE, DELIMITER changes, etc.
            try {
                $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
                $dbName = $_ENV['DB_NAME'] ?? '';
                $dbUser = $_ENV['DB_USER'] ?? '';
                $dbPass = $_ENV['DB_PASS'] ?? '';

                $cmd = sprintf(
                    'mysql -h %s -u %s -p%s %s < %s 2>&1',
                    escapeshellarg($dbHost),
                    escapeshellarg($dbUser),
                    escapeshellarg($dbPass),
                    escapeshellarg($dbName),
                    escapeshellarg($file)
                );

                $output = [];
                $exitCode = 0;
                exec($cmd, $output, $exitCode);

                if ($exitCode !== 0) {
                    $errorMsg = implode("\n", $output);
                    error_log("Migration error in {$migration} (exit code {$exitCode}): {$errorMsg}");
                    continue;
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
