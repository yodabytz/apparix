<?php

$root = sys_get_temp_dir() . '/apparix-plugin-installer-' . bin2hex(random_bytes(5));
define('BASE_PATH', $root);
require dirname(__DIR__) . '/app/Core/Plugins/PluginPackageInstaller.php';

use App\Core\Plugins\PluginPackageInstaller;

function assertTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function removeTestDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($dir);
}

function makePackage(string $path, string $slug, string $version, ?string $unsafeEntry = null): void
{
    $zip = new ZipArchive();
    assertTest($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 'Could not create test ZIP.');
    $manifest = [
        'slug' => $slug,
        'name' => 'Installer Test',
        'version' => $version,
        'type' => 'utility',
    ];
    $zip->addFromString($slug . '/plugin.json', json_encode($manifest));
    $zip->addFromString($slug . '/InstallerTestPlugin.php', "<?php\nnamespace App\\Plugins;\nclass InstallerTestPlugin {}\n");
    if ($unsafeEntry !== null) {
        $zip->addFromString($unsafeEntry, "<?php echo 'unsafe';");
    }
    $zip->close();
}

try {
    mkdir($root . '/content/plugins/installer-test', 0755, true);
    mkdir($root . '/storage', 0755, true);
    file_put_contents(
        $root . '/content/plugins/installer-test/plugin.json',
        json_encode(['slug' => 'installer-test', 'name' => 'Installer Test', 'version' => '1.0.0', 'type' => 'utility'])
    );
    file_put_contents($root . '/content/plugins/installer-test/InstallerTestPlugin.php', "<?php\nclass OldPlugin {}\n");

    $validZip = $root . '/valid.zip';
    makePackage($validZip, 'installer-test', '1.1.0');
    $installer = new PluginPackageInstaller();
    $result = $installer->install($validZip, 'installer-test', '1.1.0');
    assertTest($result['success'] === true, 'Valid plugin update was rejected: ' . ($result['error'] ?? 'unknown'));
    $installedManifest = json_decode(file_get_contents($root . '/content/plugins/installer-test/plugin.json'), true);
    assertTest($installedManifest['version'] === '1.1.0', 'Installed version is incorrect.');
    assertTest(is_dir($result['backup_path']), 'Rollback backup was not created.');
    assertTest((fileperms($root . '/content/plugins/installer-test/plugin.json') & 0777) === 0644, 'File permissions are incorrect.');
    assertTest($installer->restore($result), 'Rollback failed.');
    $restoredManifest = json_decode(file_get_contents($root . '/content/plugins/installer-test/plugin.json'), true);
    assertTest($restoredManifest['version'] === '1.0.0', 'Rollback did not restore the previous version.');

    $unsafeZip = $root . '/unsafe.zip';
    makePackage($unsafeZip, 'installer-test', '1.1.0', '../escape.php');
    $unsafe = $installer->install($unsafeZip, 'installer-test', '1.1.0');
    assertTest($unsafe['success'] === false, 'Path traversal package was accepted.');
    assertTest(!file_exists($root . '/escape.php'), 'Path traversal wrote outside the plugin directory.');

    $wrongSlugZip = $root . '/wrong-slug.zip';
    makePackage($wrongSlugZip, 'different-plugin', '1.1.0');
    $wrongSlug = $installer->install($wrongSlugZip, 'installer-test', '1.1.0');
    assertTest($wrongSlug['success'] === false, 'Mismatched plugin slug was accepted.');

    $invalidZip = $root . '/invalid-php.zip';
    makePackage($invalidZip, 'installer-test', '1.1.0');
    $zip = new ZipArchive();
    $zip->open($invalidZip);
    $zip->addFromString('installer-test/broken.php', "<?php function broken( {");
    $zip->close();
    $invalid = $installer->install($invalidZip, 'installer-test', '1.1.0');
    assertTest($invalid['success'] === false, 'Invalid PHP package was accepted.');
    $unchanged = json_decode(file_get_contents($root . '/content/plugins/installer-test/plugin.json'), true);
    assertTest($unchanged['version'] === '1.0.0', 'Invalid package changed the installed plugin.');

    echo "PluginPackageInstaller tests passed.\n";
} finally {
    removeTestDirectory($root);
}
