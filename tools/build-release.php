#!/usr/bin/env php
<?php
/**
 * Apparix release builder.
 *
 * Creates a deterministic update package for the Apparix update system and can
 * optionally publish the release row to the local `releases` table.
 *
 * This script intentionally excludes customer/site data that UpdateService also
 * protects during installs: .env, storage data, uploads, customer themes/plugins,
 * vendor, node_modules, and development metadata.
 */

declare(strict_types=1);

$basePath = realpath(getcwd());
if ($basePath === false || !is_file($basePath . '/version.php')) {
    $basePath = realpath(dirname(__DIR__));
}
if ($basePath === false || !is_file($basePath . '/version.php')) {
    // Support running the staged file from /tmp while targeting Apparix explicitly.
    $basePath = realpath('/var/www/apparix.app');
}
if ($basePath === false || !is_file($basePath . '/version.php')) {
    fwrite(STDERR, "Unable to locate Apparix base path. Run from the Apparix project or pass --base=/path.\n");
    exit(1);
}

$options = getopt('', [
    'base::',
    'version:',
    'notes-file::',
    'notes::',
    'changelog-file::',
    'changelog::',
    'release-type::',
    'min-php::',
    'min-edition::',
    'output-dir::',
    'bundle-plugin::',
    'dry-run',
    'rebuild',
    'publish',
    'skip-php-lint',
    'verbose',
    'help',
]);

if (isset($options['base'])) {
    $basePath = realpath((string)$options['base']);
    if ($basePath === false || !is_file($basePath . '/version.php')) {
        fail('Invalid --base path or missing version.php');
    }
}

if (isset($options['help']) || empty($options['version'])) {
    echo <<<HELP
Apparix Release Builder

Usage:
  php tools/build-release.php --version=1.2.8 [options]

Options:
  --base=/path             Apparix project root (default: current project)
  --version=X.Y.Z          Target release version (required)
  --notes-file=path        Release notes markdown/text
  --notes="text"           Release notes inline fallback
  --changelog-file=path    Changelog markdown/text
  --changelog="text"       Changelog inline fallback
  --release-type=stable    stable, beta, or alpha (default: stable)
  --min-php=8.3            Minimum PHP version (default: composer requirement/current)
  --min-edition=S          Minimum edition S/P/E/D/U (default: S)
  --output-dir=path        Package output directory (default: storage/updates)
  --bundle-plugin=slug     Include a changed built-in plugin (currently: stripe)
  --dry-run                Validate and list package details, do not write package
  --rebuild                Rebuild the package for the currently installed version
  --publish                Insert/update release row in the local releases table
  --skip-php-lint          Skip php -l checks
  --verbose                Print progress while building

Examples:
  php tools/build-release.php --version=1.2.8 --notes-file=release-notes/1.2.8.md
  php tools/build-release.php --version=1.2.8 --dry-run
  php tools/build-release.php --version=1.2.8 --publish

HELP;
    exit(empty($options['version']) ? 1 : 0);
}

$version = trim((string)$options['version']);
if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
    fail('Version must be semantic X.Y.Z, e.g. 1.2.8');
}

$releaseType = strtolower((string)($options['release-type'] ?? 'stable'));
if (!in_array($releaseType, ['stable', 'beta', 'alpha'], true)) {
    fail('--release-type must be stable, beta, or alpha');
}

$minEdition = strtoupper((string)($options['min-edition'] ?? 'S'));
if (!in_array($minEdition, ['S', 'P', 'E', 'D', 'U'], true)) {
    fail('--min-edition must be S, P, E, D, or U');
}

$currentInfo = include $basePath . '/version.php';
$currentVersion = (string)($currentInfo['version'] ?? '0.0.0');
if (version_compare($version, $currentVersion, '<') || ($version === $currentVersion && !array_key_exists('rebuild', $options))) {
    fail("Target version {$version} must be greater than current version {$currentVersion}");
}

$minPhp = (string)($options['min-php'] ?? detectMinimumPhp($basePath));
$outputDir = isset($options['output-dir'])
    ? absolutize((string)$options['output-dir'], $basePath)
    : $basePath . '/storage/updates';
$dryRun = array_key_exists('dry-run', $options);
$publish = array_key_exists('publish', $options);
$skipPhpLint = array_key_exists('skip-php-lint', $options);
$verbose = array_key_exists('verbose', $options);

$releaseNotes = loadTextOption($options, 'notes', 'notes-file', $basePath) ?: "Apparix {$version} release.";
$changelog = loadTextOption($options, 'changelog', 'changelog-file', $basePath) ?: $releaseNotes;

$exclude = [
    '.env', '.env.example', '.git', '.gitignore', '.claude', '.claudeignore',
    'node_modules', 'vendor', 'storage/.installed', 'storage/logs',
    'storage/sessions', 'storage/uploads', 'storage/downloads', 'storage/updates',
    'storage/updates_temp', 'storage/backups', 'storage/cache', 'storage/security',
    'storage/plugin_updates', 'storage/plugin_backups',
    'public/assets/images/products', 'public/assets/images/uploads',
    'public/assets/images/categories', 'public/assets/images/newsletter',
    'public/assets/images/branding', 'public/uploads', 'public/content',
    'content/themes', 'tests', 'tools/generate-license.php',
    'content/plugins', 'content/.plugin-updates',
];

$requiredDirs = ['app', 'public', 'database/migrations'];
foreach ($requiredDirs as $dir) {
    if (!is_dir($basePath . '/' . $dir)) {
        fail("Missing required directory: {$dir}");
    }
}

$files = collectFiles($basePath, $exclude);
$bundledPaths = [];
if (!empty($options['bundle-plugin'])) {
    $requestedPlugins = array_filter(array_map('trim', explode(',', (string)$options['bundle-plugin'])));
    $allowedBundledPlugins = ['stripe'];
    foreach ($requestedPlugins as $pluginSlug) {
        if (!in_array($pluginSlug, $allowedBundledPlugins, true)) {
            fail("Plugin {$pluginSlug} is not a core-bundled plugin");
        }
        $bundledPaths[] = 'content/plugins/' . $pluginSlug;
    }
}
foreach ($bundledPaths as $bundledPath) {
    $bundledBase = $basePath . '/' . $bundledPath;
    if (!is_dir($bundledBase)) {
        fail("Missing bundled path: {$bundledPath}");
    }
    foreach (collectFiles($bundledBase, []) as $bundledFile) {
        $files[] = $bundledPath . '/' . $bundledFile;
    }
}
$files = array_values(array_unique($files));
sort($files);
if (empty($files)) {
    fail('No files collected for release package');
}

if (!$skipPhpLint) {
    lintPhpFiles($basePath, $files);
}

$packageName = "apparix-{$version}.tar.gz";
$packagePath = rtrim($outputDir, '/') . '/' . $packageName;

$summary = [
    'base_path' => $basePath,
    'current_version' => $currentVersion,
    'target_version' => $version,
    'release_type' => $releaseType,
    'min_php_version' => $minPhp,
    'min_edition' => $minEdition,
    'file_count' => count($files),
    'output' => $packagePath,
    'dry_run' => $dryRun,
    'publish' => $publish,
];

if ($dryRun) {
    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true)) {
    fail("Unable to create output directory: {$outputDir}");
}
if (!is_writable($outputDir)) {
    fail("Output directory is not writable: {$outputDir}");
}

$tmpRoot = sys_get_temp_dir() . '/apparix-release-' . $version . '-' . bin2hex(random_bytes(4));
$tmpProject = $tmpRoot . '/apparix-' . $version;
mkdir($tmpProject, 0755, true);

try {
    if ($verbose) fwrite(STDERR, "Copying " . count($files) . " files...\n");
    $copied = 0;
    foreach ($files as $relative) {
        $source = $basePath . '/' . $relative;
        $dest = $tmpProject . '/' . $relative;
        $destDir = dirname($dest);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        if (!copy($source, $dest)) {
            throw new RuntimeException("Failed to copy {$relative}");
        }
        chmod($dest, 0644);
        $copied++;
        if ($verbose && $copied % 50 === 0) fwrite(STDERR, "  copied {$copied}\n");
    }

    if ($verbose) fwrite(STDERR, "Writing generated version.php...\n");
    writeVersionFile($tmpProject . '/version.php', $version, (string)($currentInfo['product'] ?? 'Apparix E-Commerce Platform'), (string)($currentInfo['update_server'] ?? 'https://apparix.app'));

    @unlink($packagePath);
    if ($verbose) fwrite(STDERR, "Creating archive {$packagePath}...\n");
    createTarGzPackage($tmpRoot, 'apparix-' . $version, $packagePath);
    if (!chmod($packagePath, 0640)) {
        throw new RuntimeException("Unable to secure package permissions: {$packagePath}");
    }

    if ($verbose) fwrite(STDERR, "Hashing package...\n");
    $hash = hash_file('sha256', $packagePath);
    $size = filesize($packagePath);
    if ($hash === false || $size === false || $size <= 0) {
        throw new RuntimeException('Package hash/size validation failed');
    }

    $summary['sha256'] = $hash;
    $summary['file_size'] = $size;
    $summary['file_size_formatted'] = formatBytes((int)$size);

    if ($publish) {
        publishRelease($basePath, $version, $releaseType, $releaseNotes, $changelog, $packageName, $hash, (int)$size, $minPhp, $minEdition);
        publishStatusMetadata($version, $releaseType, $releaseNotes, $changelog);
        $summary['published'] = true;
    }

    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} finally {
    deleteDirectory($tmpRoot);
}

function fail(string $message): void
{
    fwrite(STDERR, "ERROR: {$message}\n");
    exit(1);
}

function detectMinimumPhp(string $basePath): string
{
    $composer = $basePath . '/composer.json';
    if (is_file($composer)) {
        $data = json_decode((string)file_get_contents($composer), true);
        $require = $data['require']['php'] ?? null;
        if (is_string($require) && preg_match('/(\d+\.\d+)/', $require, $m)) {
            return $m[1];
        }
    }
    return PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
}

function absolutize(string $path, string $basePath): string
{
    if (str_starts_with($path, '/')) {
        return $path;
    }
    return $basePath . '/' . $path;
}

function loadTextOption(array $options, string $inlineKey, string $fileKey, string $basePath): string
{
    if (isset($options[$fileKey])) {
        $path = absolutize((string)$options[$fileKey], $basePath);
        if (!is_readable($path)) {
            fail("Cannot read --{$fileKey}: {$path}");
        }
        return trim((string)file_get_contents($path));
    }
    return trim((string)($options[$inlineKey] ?? ''));
}

function collectFiles(string $basePath, array $exclude): array
{
    $files = [];
    $directory = new RecursiveDirectoryIterator(
        $basePath,
        FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO
    );

    $filter = new RecursiveCallbackFilterIterator(
        $directory,
        function (SplFileInfo $item) use ($basePath, $exclude): bool {
            $relative = ltrim(str_replace($basePath, '', $item->getPathname()), '/');
            if ($relative === '') {
                return true;
            }
            if (shouldExclude($relative, $exclude)) {
                return false;
            }
            if ($item->isDir() && !$item->isReadable()) {
                // Unreadable data dirs should be explicitly excluded. If one is not,
                // skip it instead of crashing the release dry-run.
                return false;
            }
            return true;
        }
    );

    $iterator = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $item) {
        if (!$item->isFile() || !$item->isReadable()) {
            continue;
        }
        $relative = ltrim(str_replace($basePath, '', $item->getPathname()), '/');
        $files[] = $relative;
    }
    sort($files);
    return $files;
}

function shouldExclude(string $relative, array $exclude): bool
{
    foreach ($exclude as $pattern) {
        if ($relative === $pattern || str_starts_with($relative, $pattern . '/')) {
            return true;
        }
    }
    return false;
}

function lintPhpFiles(string $basePath, array $files): void
{
    $phpFiles = array_values(array_filter($files, fn($file) => str_ends_with($file, '.php')));
    foreach ($phpFiles as $file) {
        $cmd = 'php -l ' . escapeshellarg($basePath . '/' . $file) . ' 2>&1';
        exec($cmd, $output, $code);
        if ($code !== 0) {
            fail("PHP syntax check failed for {$file}: " . implode("\n", $output));
        }
    }
}

function writeVersionFile(string $path, string $version, string $product, string $updateServer): void
{
    [$major, $minor, $patch] = array_map('intval', explode('.', $version));
    $content = "<?php\n" .
        "/**\n" .
        " * Apparix Version Information\n" .
        " * This file is automatically updated during the update process\n" .
        " */\n" .
        "return [\n" .
        "    'version' => '{$version}',\n" .
        "    'version_major' => {$major},\n" .
        "    'version_minor' => {$minor},\n" .
        "    'version_patch' => {$patch},\n" .
        "    'release_date' => '" . date('Y-m-d') . "',\n" .
        "    'product' => '" . addslashes($product) . "',\n" .
        "    'update_server' => '" . addslashes($updateServer) . "'\n" .
        "];\n";
    file_put_contents($path, $content);
}

function createTarGzPackage(string $workingDir, string $sourceDirName, string $packagePath): void
{
    $cmd = sprintf(
        'tar -C %s -czf %s %s 2>&1',
        escapeshellarg($workingDir),
        escapeshellarg($packagePath),
        escapeshellarg($sourceDirName)
    );
    exec($cmd, $output, $code);
    if ($code !== 0) {
        throw new RuntimeException('tar package creation failed: ' . implode("\n", $output));
    }
}

function publishRelease(string $basePath, string $version, string $releaseType, string $releaseNotes, string $changelog, string $packageName, string $hash, int $size, string $minPhp, string $minEdition): void
{
    loadEnvFile($basePath . '/.env');
    require_once $basePath . '/vendor/autoload.php';
    $parts = array_map('intval', explode('.', $version));
    $db = App\Core\Database::getInstance();
    $pdo = $db->getConnection();
    $sql = "INSERT INTO releases
        (version, version_major, version_minor, version_patch, release_type, release_notes, changelog, min_php_version, min_edition, update_file, file_hash, file_size, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            release_type = VALUES(release_type),
            release_notes = VALUES(release_notes),
            changelog = VALUES(changelog),
            min_php_version = VALUES(min_php_version),
            min_edition = VALUES(min_edition),
            update_file = VALUES(update_file),
            file_hash = VALUES(file_hash),
            file_size = VALUES(file_size),
            is_active = 1";
    $pdo->prepare($sql)->execute([
        $version, $parts[0], $parts[1], $parts[2], $releaseType, $releaseNotes,
        $changelog, $minPhp, $minEdition, $packageName, $hash, $size,
    ]);
}

function publishStatusMetadata(string $version, string $releaseType, string $releaseNotes, string $changelog): void
{
    $statusFile = $_ENV['APPARIX_STATUS_RELEASE_FILE'] ?? '/var/www/status.apparix.app/public/release.json';
    $statusDir = dirname($statusFile);

    // Customer installations do not host the central Apparix status site.
    if (!is_dir($statusDir)) {
        return;
    }
    if (!is_writable($statusDir)) {
        throw new RuntimeException("Status metadata directory is not writable: {$statusDir}");
    }

    $payload = json_encode([
        'version' => $version,
        'release_type' => $releaseType,
        'release_date' => date('Y-m-d'),
        'release_notes' => $releaseNotes,
        'changelog' => $changelog,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        throw new RuntimeException('Unable to encode status metadata');
    }

    $tempFile = $statusFile . '.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($tempFile, $payload . "\n", LOCK_EX) === false) {
        throw new RuntimeException("Unable to write status metadata: {$tempFile}");
    }
    chmod($tempFile, 0644);
    if (!rename($tempFile, $statusFile)) {
        @unlink($tempFile);
        throw new RuntimeException("Unable to publish status metadata: {$statusFile}");
    }
}

function loadEnvFile(string $envPath): void
{
    if (!is_readable($envPath)) {
        fail("Cannot read .env for database publishing: {$envPath}");
    }
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines ?: [] as $line) {
        if (strpos($line, '=') !== false && strpos(ltrim($line), '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

function deleteDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}

function formatBytes(int $bytes): string
{
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}
