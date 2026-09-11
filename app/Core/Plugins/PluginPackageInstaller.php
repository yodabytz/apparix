<?php

namespace App\Core\Plugins;

use ZipArchive;

/**
 * Validates, stages, and atomically replaces one plugin directory.
 */
class PluginPackageInstaller
{
    private const MAX_FILES = 2000;
    private const MAX_BYTES = 52428800;

    public function install(string $zipPath, ?string $expectedSlug = null, ?string $expectedVersion = null): array
    {
        if (!class_exists(ZipArchive::class)) {
            return $this->fail('PHP ZIP support is required to install plugins.');
        }
        if (!is_file($zipPath) || !is_readable($zipPath)) {
            return $this->fail('Plugin package is not readable.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return $this->fail('Failed to open plugin ZIP package.');
        }

        try {
            $package = $this->inspect($zip);
            if (!$package['success']) {
                return $package;
            }

            $manifest = $package['manifest'];
            $slug = $manifest['slug'];
            $version = $manifest['version'];
            if ($expectedSlug !== null && !hash_equals($expectedSlug, $slug)) {
                return $this->fail('The package does not match the requested plugin.');
            }
            if ($expectedVersion !== null && !hash_equals($expectedVersion, $version)) {
                return $this->fail('The package version does not match the requested update.');
            }

            $pluginsDir = BASE_PATH . '/content/plugins';
            $workDir = BASE_PATH . '/storage/plugin_updates';
            $backupsDir = BASE_PATH . '/storage/plugin_backups';
            foreach ([$pluginsDir, $workDir, $backupsDir] as $dir) {
                if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
                    return $this->fail('Unable to create a plugin update directory.');
                }
                if (!is_writable($dir)) {
                    return $this->fail('Plugin update directory is not writable: ' . ltrim(str_replace(BASE_PATH, '', $dir), '/'));
                }
            }

            $operationId = bin2hex(random_bytes(8));
            $stageRoot = $workDir . '/stage-' . $operationId;
            $stagePlugin = $stageRoot . '/' . $slug;
            if (!@mkdir($stagePlugin, 0755, true)) {
                return $this->fail('Unable to stage the plugin package.');
            }

            $result = $this->extract($zip, $package['entries'], $stagePlugin);
            if (!$result['success']) {
                $this->removeDirectory($stageRoot);
                return $result;
            }
            $result = $this->validateStage($stagePlugin, $manifest);
            if (!$result['success']) {
                $this->removeDirectory($stageRoot);
                return $result;
            }
            $this->normalizePermissions($stagePlugin);

            $target = $pluginsDir . '/' . $slug;
            $backup = null;
            if (is_dir($target)) {
                $currentVersion = $this->installedVersion($target);
                if ($expectedVersion !== null && $currentVersion !== null && version_compare($version, $currentVersion, '<=')) {
                    $this->removeDirectory($stageRoot);
                    return $this->fail('The plugin update must be newer than the installed version.');
                }
                $backup = $backupsDir . '/' . $slug . '-' . ($currentVersion ?: 'unknown') . '-' . date('Ymd-His') . '-' . $operationId;
                if (!@rename($target, $backup)) {
                    $this->removeDirectory($stageRoot);
                    return $this->fail('Unable to create the plugin rollback backup.');
                }
            }

            if (!@rename($stagePlugin, $target)) {
                if ($backup !== null) {
                    @rename($backup, $target);
                }
                $this->removeDirectory($stageRoot);
                return $this->fail('Unable to install the staged plugin. The previous version was restored.');
            }

            $this->removeDirectory($stageRoot);
            $this->pruneBackups($slug, 5);
            return [
                'success' => true,
                'manifest' => $manifest,
                'slug' => $slug,
                'version' => $version,
                'backup_path' => $backup,
                'target_path' => $target,
            ];
        } finally {
            $zip->close();
        }
    }

    public function restore(array $install): bool
    {
        $target = $install['target_path'] ?? null;
        $backup = $install['backup_path'] ?? null;
        if (!is_string($target) || !is_string($backup) || !is_dir($backup)) {
            return false;
        }

        $failed = $target . '.failed-' . bin2hex(random_bytes(4));
        if (is_dir($target) && !@rename($target, $failed)) {
            return false;
        }
        if (!@rename($backup, $target)) {
            if (is_dir($failed)) {
                @rename($failed, $target);
            }
            return false;
        }
        $this->removeDirectory($failed);
        return true;
    }

    private function inspect(ZipArchive $zip): array
    {
        if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_FILES) {
            return $this->fail('Plugin package contains an invalid number of files.');
        }

        $entries = [];
        $manifestIndexes = [];
        $totalBytes = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!is_array($stat) || !isset($stat['name'])) {
                return $this->fail('Plugin package contains an unreadable entry.');
            }
            $name = $this->normalizeEntryName((string)$stat['name']);
            if ($name === null) {
                return $this->fail('Plugin package contains an unsafe file path.');
            }
            if ($this->isSymlink($zip, $i)) {
                return $this->fail('Plugin packages cannot contain symbolic links.');
            }
            $totalBytes += (int)($stat['size'] ?? 0);
            if ($totalBytes > self::MAX_BYTES) {
                return $this->fail('Plugin package is too large after extraction.');
            }
            $entries[$i] = ['index' => $i, 'name' => $name, 'size' => (int)($stat['size'] ?? 0)];
            if (basename(rtrim($name, '/')) === 'plugin.json') {
                $manifestIndexes[] = $i;
            }
        }

        if (count($manifestIndexes) !== 1) {
            return $this->fail('Plugin package must contain exactly one plugin.json manifest.');
        }
        $manifestIndex = $manifestIndexes[0];
        $raw = $zip->getFromIndex($manifestIndex);
        $manifest = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($manifest)) {
            return $this->fail('Plugin manifest is not valid JSON.');
        }
        $valid = $this->validateManifest($manifest);
        if (!$valid['success']) {
            return $valid;
        }

        $prefix = dirname($entries[$manifestIndex]['name']);
        $prefix = $prefix === '.' ? '' : rtrim($prefix, '/') . '/';
        $packageEntries = [];
        $seenPaths = [];
        foreach ($entries as $entry) {
            if ($prefix !== '' && !str_starts_with($entry['name'], $prefix)) {
                return $this->fail('Plugin package contains files outside its plugin directory.');
            }
            $relative = $prefix === '' ? $entry['name'] : substr($entry['name'], strlen($prefix));
            if ($relative === '' || $relative === false) {
                continue;
            }
            $pathKey = rtrim($relative, '/');
            if (isset($seenPaths[$pathKey])) {
                return $this->fail('Plugin package contains duplicate file paths.');
            }
            $seenPaths[$pathKey] = true;
            $entry['relative'] = $relative;
            $packageEntries[] = $entry;
        }

        return ['success' => true, 'manifest' => $manifest, 'entries' => $packageEntries];
    }

    private function validateManifest(array $manifest): array
    {
        foreach (['slug', 'name', 'version', 'type'] as $field) {
            if (!is_string($manifest[$field] ?? null) || trim($manifest[$field]) === '') {
                return $this->fail('Plugin manifest is missing required field: ' . $field);
            }
        }
        if (!preg_match('/^[a-z0-9][a-z0-9-]{0,99}$/', $manifest['slug'])) {
            return $this->fail('Plugin manifest contains an invalid slug.');
        }
        if (!preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $manifest['version'])) {
            return $this->fail('Plugin manifest contains an invalid semantic version.');
        }
        $types = ['payment', 'shipping', 'analytics', 'marketing', 'utility', 'marketplace'];
        if (!in_array($manifest['type'], $types, true)) {
            return $this->fail('Plugin manifest contains an unsupported type.');
        }
        return ['success' => true];
    }

    private function extract(ZipArchive $zip, array $entries, string $stage): array
    {
        foreach ($entries as $entry) {
            $target = $stage . '/' . $entry['relative'];
            if (str_ends_with($entry['relative'], '/')) {
                if (!is_dir($target) && !@mkdir($target, 0755, true)) {
                    return $this->fail('Unable to create a staged plugin directory.');
                }
                continue;
            }
            if (!is_dir(dirname($target)) && !@mkdir(dirname($target), 0755, true)) {
                return $this->fail('Unable to create a staged plugin directory.');
            }
            $contents = $zip->getFromIndex($entry['index']);
            if (!is_string($contents) || strlen($contents) !== $entry['size']) {
                return $this->fail('Unable to read a file from the plugin package.');
            }
            if (@file_put_contents($target, $contents, LOCK_EX) === false) {
                return $this->fail('Unable to write a staged plugin file.');
            }
        }
        return ['success' => true];
    }

    private function validateStage(string $path, array $manifest): array
    {
        $manifestPath = $path . '/plugin.json';
        if (!is_file($manifestPath)) {
            return $this->fail('Plugin manifest must be at the package root.');
        }
        $stored = json_decode((string)file_get_contents($manifestPath), true);
        if (!is_array($stored) || $stored['slug'] !== $manifest['slug'] || $stored['version'] !== $manifest['version']) {
            return $this->fail('Staged plugin manifest failed integrity validation.');
        }

        $phpCount = 0;
        $lintAvailable = function_exists('exec')
            && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions') ?: '')), true);
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }
            $phpCount++;
            if ($file->getSize() < 1) {
                return $this->fail('Plugin package contains invalid PHP: ' . $file->getFilename());
            }
            if ($lintAvailable) {
                $output = [];
                $exitCode = 0;
                exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $output, $exitCode);
                if ($exitCode !== 0) {
                    return $this->fail('Plugin package contains invalid PHP: ' . $file->getFilename());
                }
            } else {
                $sample = file_get_contents($file->getPathname(), false, null, 0, min(4096, $file->getSize()));
                if (!is_string($sample) || !str_contains($sample, '<?php') || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $sample)) {
                    return $this->fail('Plugin package contains invalid PHP: ' . $file->getFilename());
                }
            }
        }
        return $phpCount > 0 ? ['success' => true] : $this->fail('Plugin package does not contain a PHP plugin class.');
    }

    private function normalizeEntryName(string $name): ?string
    {
        if ($name === '' || str_contains($name, "\0")) {
            return null;
        }
        $name = str_replace('\\', '/', $name);
        if (str_starts_with($name, '/') || preg_match('/^[A-Za-z]:\//', $name)) {
            return null;
        }
        foreach (explode('/', rtrim($name, '/')) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
        }
        return $name;
    }

    private function isSymlink(ZipArchive $zip, int $index): bool
    {
        $opsys = 0;
        $attributes = 0;
        return $zip->getExternalAttributesIndex($index, $opsys, $attributes)
            && ((($attributes >> 16) & 0xF000) === 0xA000);
    }

    private function installedVersion(string $path): ?string
    {
        $manifest = json_decode((string)@file_get_contents($path . '/plugin.json'), true);
        return is_string($manifest['version'] ?? null) ? $manifest['version'] : null;
    }

    private function normalizePermissions(string $dir): void
    {
        @chmod($dir, 0755);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file) {
            @chmod($file->getPathname(), $file->isDir() ? 0755 : 0644);
        }
    }

    private function pruneBackups(string $slug, int $keep): void
    {
        $backups = glob(BASE_PATH . '/storage/plugin_backups/' . $slug . '-*', GLOB_ONLYDIR) ?: [];
        usort($backups, static fn(string $a, string $b): int => filemtime($b) <=> filemtime($a));
        foreach (array_slice($backups, $keep) as $backup) {
            $this->removeDirectory($backup);
        }
    }

    private function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }
        return @rmdir($dir);
    }

    private function fail(string $error): array
    {
        return ['success' => false, 'error' => $error];
    }
}
