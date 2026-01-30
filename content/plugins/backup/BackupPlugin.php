<?php
/**
 * Apparix Backup Plugin
 *
 * Lightweight backup solution for code, settings, and database.
 * Excludes large files (images, uploads) to minimize resource usage.
 *
 * @version 1.0.0
 */

namespace Plugins\Backup;

use App\Core\Plugins\PluginInterface;
use App\Core\Database;

class BackupPlugin implements PluginInterface
{
    private array $settings = [];
    private string $backupDir;
    private string $baseDir;
    private array $excludeDirs = [
        'node_modules',
        'vendor',
        'storage/backups',
        'storage/logs',
        'storage/sessions',
        'storage/cache',
        'public/assets/images/products',
        'public/assets/images/uploads',
        'public/uploads',
        '.git',
        '.claude',
    ];

    private array $excludeExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico',
        'mp4', 'avi', 'mov', 'webm',
        'mp3', 'wav', 'ogg',
        'zip', 'tar', 'gz', 'rar',
        'pdf',
    ];

    public function __construct()
    {
        $this->baseDir = dirname(dirname(dirname(__DIR__)));
        $this->backupDir = $this->baseDir . '/storage/backups';
        $this->loadSettings();
    }

    /**
     * Get plugin name
     */
    public function getName(): string
    {
        return 'Apparix Backup';
    }

    /**
     * Get plugin slug
     */
    public function getSlug(): string
    {
        return 'backup';
    }

    /**
     * Initialize the plugin
     */
    public function initialize(): void
    {
        // Ensure backup directory exists and is writable
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        // Create .htaccess to protect backups from web access
        $htaccess = $this->backupDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Order Deny,Allow\nDeny from all\n");
        }

        // Create index.php to prevent directory listing
        $index = $this->backupDir . '/index.php';
        if (!file_exists($index)) {
            file_put_contents($index, "<?php // Silence is golden\n");
        }
    }

    /**
     * Load plugin settings from database
     */
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

        // Set defaults
        $this->settings = array_merge([
            'retention_count' => 5,
            'include_database' => true,
            'include_code' => true,
            'include_config' => true,
            'auto_backup' => false,
            'backup_schedule' => 'daily',
            'last_backup' => null,
            'compression' => true,
        ], $this->settings);
    }

    /**
     * Get a setting value
     */
    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Save settings to database
     */
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

    /**
     * Create a full backup
     *
     * @param bool $cliMode Whether running from CLI (allows more time)
     * @return array Result with success status and details
     */
    public function createBackup(bool $cliMode = false): array
    {
        $startTime = microtime(true);
        $timestamp = date('Y-m-d_H-i-s');
        $backupName = "backup_{$timestamp}";
        $tempDir = $this->backupDir . "/{$backupName}";
        $lockFile = $this->backupDir . '/.backup.lock';
        $result = [
            'success' => false,
            'filename' => null,
            'size' => 0,
            'duration' => 0,
            'details' => [],
            'errors' => [],
        ];

        try {
            // Check for concurrent backup (lock file)
            if (file_exists($lockFile)) {
                $lockTime = filemtime($lockFile);
                // If lock is older than 1 hour, it's stale - remove it
                if ((time() - $lockTime) > 3600) {
                    unlink($lockFile);
                    $this->log("Removed stale backup lock file", 'warning');
                } else {
                    throw new \Exception("Another backup is currently in progress. Please wait.");
                }
            }

            // Create lock file
            file_put_contents($lockFile, date('Y-m-d H:i:s') . ' - PID: ' . getmypid());

            // Check available disk space (require at least 100MB free)
            $freeSpace = disk_free_space($this->backupDir);
            $minRequired = 100 * 1024 * 1024; // 100MB

            if ($freeSpace < $minRequired) {
                throw new \Exception("Insufficient disk space. Required: 100MB, Available: " . $this->formatBytes($freeSpace));
            }

            // Create temporary directory for backup files
            if (!mkdir($tempDir, 0755, true)) {
                throw new \Exception("Failed to create temporary backup directory");
            }

            // Backup database
            if ($this->settings['include_database']) {
                $this->yieldControl(); // Let other processes run
                $dbResult = $this->backupDatabase($tempDir);
                $result['details']['database'] = $dbResult;
                if (!$dbResult['success']) {
                    $result['errors'][] = "Database backup failed: " . ($dbResult['error'] ?? 'Unknown error');
                }
            }

            // Backup configuration files
            if ($this->settings['include_config']) {
                $this->yieldControl();
                $configResult = $this->backupConfig($tempDir);
                $result['details']['config'] = $configResult;
            }

            // Backup code files
            if ($this->settings['include_code']) {
                $this->yieldControl();
                $codeResult = $this->backupCode($tempDir, $cliMode);
                $result['details']['code'] = $codeResult;
            }

            // Create backup manifest
            $this->createManifest($tempDir, $result['details']);

            // Compress the backup
            $this->yieldControl();
            $archivePath = $this->compressBackup($tempDir, $backupName);

            if ($archivePath && file_exists($archivePath)) {
                $result['success'] = true;
                $result['filename'] = basename($archivePath);
                $result['filepath'] = $archivePath;
                $result['size'] = filesize($archivePath);
                $result['size_formatted'] = $this->formatBytes($result['size']);
            }

            // Clean up temp directory
            $this->deleteDirectory($tempDir);

            // Apply retention policy
            $this->applyRetentionPolicy();

            // Update last backup time
            $this->saveSettings(['last_backup' => date('Y-m-d H:i:s')]);

        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
            $this->log("Backup failed: " . $e->getMessage(), 'error');

            // Clean up on failure
            if (is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
        } finally {
            // Always remove lock file when done
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
        }

        $result['duration'] = round(microtime(true) - $startTime, 2);
        $this->log("Backup completed in {$result['duration']}s - " . ($result['success'] ? 'SUCCESS' : 'FAILED'));

        return $result;
    }

    /**
     * Backup database using PHP (no mysqldump dependency)
     */
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

            // Write header
            fwrite($handle, "-- Apparix Database Backup\n");
            fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Database: " . ($_ENV['DB_NAME'] ?? 'unknown') . "\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            // Get all tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
            $result['tables'] = count($tables);

            foreach ($tables as $table) {
                $this->yieldControl(); // Prevent hogging CPU

                // Get create table statement
                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
                fwrite($handle, "-- Table: {$table}\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($handle, $createStmt['Create Table'] . ";\n\n");

                // Get row count for progress
                $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
                $rowCount = $countStmt->fetchColumn();

                if ($rowCount > 0) {
                    // Export data in chunks to avoid memory issues
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
                            $values = array_map(function($value) use ($pdo) {
                                if ($value === null) {
                                    return 'NULL';
                                }
                                return $pdo->quote($value);
                            }, $row);

                            $columns = array_map(function($col) {
                                return "`{$col}`";
                            }, array_keys($row));

                            fwrite($handle,
                                "INSERT INTO `{$table}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n"
                            );
                        }

                        $offset += $chunkSize;

                        // Yield control between chunks
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

    /**
     * Backup configuration files
     */
    private function backupConfig(string $tempDir): array
    {
        $result = ['success' => false, 'files' => 0];
        $configDir = $tempDir . '/config';

        try {
            mkdir($configDir, 0755, true);

            // Backup .env (sanitized - remove sensitive data)
            $envFile = $this->baseDir . '/.env';
            if (file_exists($envFile)) {
                $envContent = file_get_contents($envFile);
                // Mask sensitive values
                $envContent = preg_replace(
                    '/(PASSWORD|SECRET|KEY|TOKEN)=.*/i',
                    '$1=***REDACTED***',
                    $envContent
                );
                file_put_contents($configDir . '/.env.backup', $envContent);
                $result['files']++;
            }

            // Backup composer.json
            $composerFile = $this->baseDir . '/composer.json';
            if (file_exists($composerFile)) {
                copy($composerFile, $configDir . '/composer.json');
                $result['files']++;
            }

            // Backup package.json
            $packageFile = $this->baseDir . '/package.json';
            if (file_exists($packageFile)) {
                copy($packageFile, $configDir . '/package.json');
                $result['files']++;
            }

            // Export settings from database
            $this->exportSettings($configDir);
            $result['files']++;

            $result['success'] = true;

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Export database settings to JSON
     */
    private function exportSettings(string $configDir): void
    {
        try {
            $db = Database::getInstance();

            // Export settings table
            $settings = $db->select("SELECT * FROM settings");
            file_put_contents(
                $configDir . '/settings.json',
                json_encode($settings, JSON_PRETTY_PRINT)
            );

            // Export themes
            $themes = $db->select("SELECT * FROM themes");
            file_put_contents(
                $configDir . '/themes.json',
                json_encode($themes, JSON_PRETTY_PRINT)
            );

            // Export plugins
            $plugins = $db->select("SELECT slug, name, is_active, settings FROM plugins");
            file_put_contents(
                $configDir . '/plugins.json',
                json_encode($plugins, JSON_PRETTY_PRINT)
            );

            // Export shipping zones/rates
            $shipping = [
                'methods' => $db->select("SELECT * FROM shipping_methods"),
                'zones' => $db->select("SELECT * FROM shipping_zones"),
                'rates' => $db->select("SELECT * FROM shipping_rates"),
            ];
            file_put_contents(
                $configDir . '/shipping.json',
                json_encode($shipping, JSON_PRETTY_PRINT)
            );

        } catch (\Exception $e) {
            $this->log("Settings export error: " . $e->getMessage(), 'warning');
        }
    }

    /**
     * Backup code files (excluding large directories and media)
     */
    private function backupCode(string $tempDir, bool $cliMode = false): array
    {
        $result = ['success' => false, 'files' => 0, 'size' => 0];
        $codeDir = $tempDir . '/code';

        try {
            mkdir($codeDir, 0755, true);

            // Directories to backup
            $directories = [
                'app',
                'config',
                'cron',
                'database',
                'install',
                'newsletter',
                'public/assets/css',
                'public/assets/js',
                'scripts',
                'content/plugins',
                'content/themes',
            ];

            // Individual files to backup
            $files = [
                'public/index.php',
                'public/404.php',
                'composer.json',
                'composer.lock',
                'package.json',
                '.htaccess',
                'version.php',
            ];

            // Copy directories
            foreach ($directories as $dir) {
                $sourcePath = $this->baseDir . '/' . $dir;
                if (is_dir($sourcePath)) {
                    $destPath = $codeDir . '/' . $dir;
                    $copyResult = $this->copyDirectory($sourcePath, $destPath, $cliMode);
                    $result['files'] += $copyResult['files'];
                    $result['size'] += $copyResult['size'];
                }
                $this->yieldControl();
            }

            // Copy individual files
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

    /**
     * Recursively copy a directory, excluding specified paths and extensions
     */
    private function copyDirectory(string $source, string $dest, bool $cliMode = false): array
    {
        $result = ['files' => 0, 'size' => 0];

        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $iterator = new \DirectoryIterator($source);

        foreach ($iterator as $item) {
            if ($item->isDot()) {
                continue;
            }

            $sourcePath = $item->getPathname();
            $destPath = $dest . '/' . $item->getFilename();
            $relativePath = str_replace($this->baseDir . '/', '', $sourcePath);

            // Check if path should be excluded
            if ($this->shouldExclude($relativePath)) {
                continue;
            }

            if ($item->isDir()) {
                $subResult = $this->copyDirectory($sourcePath, $destPath, $cliMode);
                $result['files'] += $subResult['files'];
                $result['size'] += $subResult['size'];
            } else {
                // Check file extension
                $ext = strtolower($item->getExtension());
                if (in_array($ext, $this->excludeExtensions)) {
                    continue;
                }

                // Skip large files (> 5MB) unless in CLI mode
                $fileSize = $item->getSize();
                if (!$cliMode && $fileSize > 5 * 1024 * 1024) {
                    continue;
                }

                copy($sourcePath, $destPath);
                $result['files']++;
                $result['size'] += $fileSize;
            }

            // Yield control every 50 files
            if ($result['files'] % 50 === 0) {
                $this->yieldControl();
            }
        }

        return $result;
    }

    /**
     * Check if a path should be excluded from backup
     */
    private function shouldExclude(string $relativePath): bool
    {
        foreach ($this->excludeDirs as $excludeDir) {
            if (strpos($relativePath, $excludeDir) === 0 ||
                strpos($relativePath, '/' . $excludeDir) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Create backup manifest file
     */
    private function createManifest(string $tempDir, array $details): void
    {
        $manifest = [
            'created_at' => date('Y-m-d H:i:s'),
            'apparix_version' => $this->getApparixVersion(),
            'php_version' => PHP_VERSION,
            'backup_plugin_version' => '1.0.0',
            'contents' => $details,
            'excluded_directories' => $this->excludeDirs,
            'excluded_extensions' => $this->excludeExtensions,
        ];

        file_put_contents(
            $tempDir . '/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Compress backup directory into archive
     */
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

    /**
     * Recursively add directory to ZIP archive
     */
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

    /**
     * Apply retention policy - keep only N most recent backups
     */
    private function applyRetentionPolicy(): void
    {
        $retentionCount = (int) $this->getSetting('retention_count', 5);

        if ($retentionCount <= 0) {
            return; // No retention policy
        }

        $backups = $this->listBackups();

        if (count($backups) <= $retentionCount) {
            return;
        }

        // Sort by date descending (newest first)
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        // Delete old backups
        $toDelete = array_slice($backups, $retentionCount);

        foreach ($toDelete as $backup) {
            $this->deleteBackup($backup['filename']);
        }

        $this->log("Retention policy applied: deleted " . count($toDelete) . " old backup(s)");
    }

    /**
     * List all backups
     */
    public function listBackups(): array
    {
        $backups = [];

        if (!is_dir($this->backupDir)) {
            return $backups;
        }

        $files = glob($this->backupDir . '/backup_*.zip');

        foreach ($files as $file) {
            $filename = basename($file);

            // Extract timestamp from filename
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

        // Sort by newest first
        usort($backups, function($a, $b) {
            return filemtime($b['filepath']) - filemtime($a['filepath']);
        });

        return $backups;
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup(string $filename): bool
    {
        // Sanitize filename - only allow our backup format
        $filename = basename($filename);

        // Validate filename format (backup_YYYY-MM-DD_HH-MM-SS.zip)
        if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.zip$/', $filename)) {
            $this->log("Invalid backup filename format for deletion: {$filename}", 'warning');
            return false;
        }

        $filepath = $this->backupDir . '/' . $filename;

        // Double-check path is within backup directory
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

    /**
     * Get backup file path for download
     */
    public function getBackupPath(string $filename): ?string
    {
        // Sanitize filename - only allow our backup format
        $filename = basename($filename);

        // Validate filename format (backup_YYYY-MM-DD_HH-MM-SS.zip)
        if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.zip$/', $filename)) {
            $this->log("Invalid backup filename format for download: {$filename}", 'warning');
            return null;
        }

        $filepath = $this->backupDir . '/' . $filename;

        // Double-check path is within backup directory
        $realBackupDir = realpath($this->backupDir);
        $realFilepath = realpath($filepath);

        if ($realFilepath === false || strpos($realFilepath, $realBackupDir) !== 0) {
            $this->log("Path traversal attempt detected: {$filename}", 'warning');
            return null;
        }

        if (file_exists($filepath)) {
            return $filepath;
        }

        return null;
    }

    /**
     * Get storage usage info
     */
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

    /**
     * Yield control to prevent hogging resources
     */
    private function yieldControl(): void
    {
        // Small sleep to let other processes run
        usleep(10000); // 10ms

        // Clear stat cache to free memory
        clearstatcache();
    }

    /**
     * Delete directory recursively
     */
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

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }

    /**
     * Get Apparix version
     */
    private function getApparixVersion(): string
    {
        $versionFile = $this->baseDir . '/version.php';
        if (file_exists($versionFile)) {
            include $versionFile;
            return defined('APPARIX_VERSION') ? APPARIX_VERSION : '1.0.0';
        }
        return '1.0.0';
    }

    /**
     * Log message
     */
    private function log(string $message, string $level = 'info'): void
    {
        $logFile = $this->baseDir . '/storage/logs/backup.log';
        $timestamp = date('Y-m-d H:i:s');
        $entry = "[{$timestamp}] [{$level}] {$message}\n";

        // Ensure log directory exists
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Check if backup should run based on schedule
     */
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

    /**
     * Get settings view path
     */
    public function getSettingsView(): string
    {
        return __DIR__ . '/views/admin-settings.php';
    }

    /**
     * Handle webhook (not used by this plugin)
     */
    public function handleWebhook(string $payload, array $headers): array
    {
        return ['success' => false, 'error' => 'Not implemented'];
    }
}
