<?php
/**
 * Image Optimization Script
 * Converts PNGs/JPGs to WebP and updates database paths
 */

define('BASE_PATH', dirname(__DIR__));

// Load environment variables
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance();
$baseDir = '/var/www/www.apparix.vibrixmedia.com/public';
$converted = 0;
$failed = 0;
$skipped = 0;

echo "Starting image optimization...\n\n";

// Get all product images from database
$images = $db->select("SELECT id, image_path FROM product_images WHERE image_path IS NOT NULL");

foreach ($images as $image) {
    $relativePath = $image['image_path'];
    $fullPath = $baseDir . $relativePath;

    // Skip if file doesn't exist
    if (!file_exists($fullPath)) {
        echo "SKIP (not found): {$relativePath}\n";
        $skipped++;
        continue;
    }

    // Get file info
    $pathInfo = pathinfo($fullPath);
    $ext = strtolower($pathInfo['extension'] ?? '');

    // Skip if already WebP or is a video
    if ($ext === 'webp' || in_array($ext, ['mp4', 'mov', 'avi', 'webm'])) {
        $skipped++;
        continue;
    }

    // Only process PNG and JPG
    if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
        $skipped++;
        continue;
    }

    // Get file size
    $fileSize = filesize($fullPath);

    // New WebP path
    $webpFilename = $pathInfo['filename'] . '.webp';
    $webpFullPath = $pathInfo['dirname'] . '/' . $webpFilename;
    $webpRelativePath = dirname($relativePath) . '/' . $webpFilename;

    // Convert using GD library (more reliable than ImageMagick for WebP)
    $success = false;
    $imageInfo = @getimagesize($fullPath);

    if ($imageInfo) {
        $mimeType = $imageInfo['mime'];
        $source = null;

        switch ($mimeType) {
            case 'image/jpeg':
                $source = @imagecreatefromjpeg($fullPath);
                break;
            case 'image/png':
                $source = @imagecreatefrompng($fullPath);
                break;
        }

        if ($source) {
            // Preserve transparency for PNGs
            if ($mimeType === 'image/png') {
                imagepalettetotruecolor($source);
                imagealphablending($source, true);
                imagesavealpha($source, true);
            }

            // Save as WebP with 85% quality
            $success = imagewebp($source, $webpFullPath, 85);
            imagedestroy($source);
        }
    }

    if ($success && file_exists($webpFullPath)) {
        $newSize = filesize($webpFullPath);
        $savings = round((1 - ($newSize / $fileSize)) * 100, 1);

        // Update database
        $db->update(
            "UPDATE product_images SET image_path = ? WHERE id = ?",
            [$webpRelativePath, $image['id']]
        );

        // Delete original file
        unlink($fullPath);

        // Also convert thumbnail if it exists
        $thumbPath = $pathInfo['dirname'] . '/thumb-' . $pathInfo['basename'];
        $thumbWebpPath = $pathInfo['dirname'] . '/thumb-' . $pathInfo['filename'] . '.webp';
        if (file_exists($thumbPath)) {
            $thumbInfo = @getimagesize($thumbPath);
            if ($thumbInfo) {
                $thumbSource = null;
                switch ($thumbInfo['mime']) {
                    case 'image/jpeg':
                        $thumbSource = @imagecreatefromjpeg($thumbPath);
                        break;
                    case 'image/png':
                        $thumbSource = @imagecreatefrompng($thumbPath);
                        break;
                }
                if ($thumbSource) {
                    if ($thumbInfo['mime'] === 'image/png') {
                        imagepalettetotruecolor($thumbSource);
                        imagealphablending($thumbSource, true);
                        imagesavealpha($thumbSource, true);
                    }
                    imagewebp($thumbSource, $thumbWebpPath, 85);
                    imagedestroy($thumbSource);
                    unlink($thumbPath);
                }
            }
        }

        $converted++;
        $sizeKB = round($fileSize / 1024);
        $newSizeKB = round($newSize / 1024);
        echo "OK: {$pathInfo['basename']} -> {$webpFilename} ({$sizeKB}KB -> {$newSizeKB}KB, saved {$savings}%)\n";
    } else {
        $failed++;
        echo "FAIL: {$relativePath}\n";
    }
}

echo "\n=== Summary ===\n";
echo "Converted: {$converted}\n";
echo "Failed: {$failed}\n";
echo "Skipped: {$skipped}\n";
