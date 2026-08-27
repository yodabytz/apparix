#!/usr/bin/env php
<?php
/**
 * Google Merchant Feed Generator
 *
 * Generates XML feed for Google Shopping with proper color, size, gender, and age_group attributes.
 *
 * Cron: Run daily at 3 AM
 * 0 3 * * * php /var/www/SITEPATH/scripts/generate-google-feed.php >> /var/log/google-feed.log 2>&1
 */

// Only run via CLI
if (php_sapi_name() !== 'cli') {
    exit('This script must be run from the command line.');
}

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Core/GoogleMerchantItemId.php';

// Load environment variables (same parser as index.php)
$envFile = BASE_PATH . '/.env';
if (!file_exists($envFile)) {
    die("Error: .env file not found\n");
}

$env = [];
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
}

if (empty($env['DB_HOST']) || empty($env['DB_NAME']) || empty($env['DB_USER'])) {
    die("Error: Database configuration missing in .env\n");
}

// Database connection
try {
    $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4";
    $db = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Get store configuration
$storeUrl = rtrim($env['APP_URL'] ?? 'https://example.com', '/');
$storeName = $env['APP_NAME'] ?? 'Store';
$currency = $env['CURRENCY'] ?? 'USD';

// Google category mappings
$categoryMappings = [
    'sweater' => 'Apparel & Accessories > Clothing > Shirts & Tops > Sweaters',
    'cardigan' => 'Apparel & Accessories > Clothing > Shirts & Tops > Sweaters',
    'poncho' => 'Apparel & Accessories > Clothing > Outerwear > Capes & Ponchos',
    'cape' => 'Apparel & Accessories > Clothing > Outerwear > Capes & Ponchos',
    'coat' => 'Apparel & Accessories > Clothing > Outerwear > Coats & Jackets',
    'jacket' => 'Apparel & Accessories > Clothing > Outerwear > Coats & Jackets',
    'dress' => 'Apparel & Accessories > Clothing > Dresses',
    'shirt' => 'Apparel & Accessories > Clothing > Shirts & Tops',
    't-shirt' => 'Apparel & Accessories > Clothing > Shirts & Tops',
    'pant' => 'Apparel & Accessories > Clothing > Pants',
    'scarf' => 'Apparel & Accessories > Clothing Accessories > Scarves & Shawls',
    'shawl' => 'Apparel & Accessories > Clothing Accessories > Scarves & Shawls',
    'wrap' => 'Apparel & Accessories > Clothing Accessories > Scarves & Shawls',
    'hat' => 'Apparel & Accessories > Clothing Accessories > Hats',
    'headband' => 'Apparel & Accessories > Clothing Accessories > Hair Accessories > Headbands',
    'glove' => 'Apparel & Accessories > Clothing Accessories > Gloves & Mittens',
    'mitten' => 'Apparel & Accessories > Clothing Accessories > Gloves & Mittens',
    'sock' => 'Apparel & Accessories > Clothing > Underwear & Socks > Socks',
    'backpack' => 'Apparel & Accessories > Handbags, Wallets & Cases > Backpacks',
    'tote' => 'Apparel & Accessories > Handbags, Wallets & Cases > Handbags',
    'bag' => 'Apparel & Accessories > Handbags, Wallets & Cases > Handbags',
    'purse' => 'Apparel & Accessories > Handbags, Wallets & Cases > Wallets & Money Clips',
    'wallet' => 'Apparel & Accessories > Handbags, Wallets & Cases > Wallets & Money Clips',
    'necklace' => 'Apparel & Accessories > Jewelry > Necklaces',
    'pendant' => 'Apparel & Accessories > Jewelry > Necklaces',
    'bracelet' => 'Apparel & Accessories > Jewelry > Bracelets',
    'bangle' => 'Apparel & Accessories > Jewelry > Bracelets',
    'earring' => 'Apparel & Accessories > Jewelry > Earrings',
    'ring' => 'Apparel & Accessories > Jewelry > Rings',
    'brooch' => 'Apparel & Accessories > Jewelry > Brooches & Pins',
    'pin' => 'Apparel & Accessories > Jewelry > Brooches & Pins',
    'blanket' => 'Home & Garden > Linens & Bedding > Bedding > Blankets & Throws',
    'throw' => 'Home & Garden > Linens & Bedding > Bedding > Blankets & Throws',
    'pillow' => 'Home & Garden > Linens & Bedding > Bedding > Pillows',
    'cushion' => 'Home & Garden > Linens & Bedding > Bedding > Pillows',
    'candle' => 'Home & Garden > Decor > Candles',
    'mug' => 'Home & Garden > Kitchen & Dining > Tableware > Drinkware > Mugs',
    'ornament' => 'Home & Garden > Decor > Seasonal & Holiday Decorations',
    'software' => 'Software',
    'plugin' => 'Software > Computer Software',
];

// Color detection from product name/description
$colorKeywords = [
    'black', 'white', 'red', 'blue', 'green', 'yellow', 'orange', 'purple',
    'pink', 'brown', 'gray', 'grey', 'navy', 'cream', 'ivory', 'beige',
    'charcoal', 'burgundy', 'maroon', 'teal', 'coral', 'gold', 'silver',
    'tan', 'khaki', 'olive', 'emerald', 'rose', 'lavender', 'mauve',
    'oatmeal', 'natural', 'bone', 'pewter', 'bronze', 'copper',
    'smoke gray', 'light blue', 'dark blue', 'dark green', 'light green',
    'ocean blue', 'sky blue', 'royal blue', 'forest green', 'wine',
    'multicolor', 'multi-color',
];

// Female/male keywords for gender detection
$femaleKeywords = ['women', 'womens', "women's", 'ladies', 'lady', 'female', 'her ', 'girls', 'feminine', 'ladies\''];
$maleKeywords = ['men\'s', 'mens', "men's", 'male', 'his ', 'boys', 'gentleman', 'masculine', 'men '];

/**
 * Detect gender from product name and description
 */
function detectGender($name, $description = '') {
    global $femaleKeywords, $maleKeywords;

    $text = strtolower($name . ' ' . $description);

    foreach ($femaleKeywords as $keyword) {
        if (strpos($text, $keyword) !== false) {
            return 'female';
        }
    }

    foreach ($maleKeywords as $keyword) {
        if (strpos($text, $keyword) !== false) {
            return 'male';
        }
    }

    return 'unisex';
}

/**
 * Detect color from product name and description
 */
function detectColor($name, $description = '', $googleColor = null) {
    global $colorKeywords;

    // Use google_color column if set
    if (!empty($googleColor)) {
        return $googleColor;
    }

    $text = strtolower($name . ' ' . $description);
    $found = [];

    // Check multi-word colors first
    $multiWordColors = array_filter($colorKeywords, function($c) { return strpos($c, ' ') !== false || strpos($c, '-') !== false; });
    foreach ($multiWordColors as $color) {
        if (strpos($text, $color) !== false) {
            $found[] = ucwords($color);
        }
    }

    // Then single-word colors
    $singleWordColors = array_filter($colorKeywords, function($c) { return strpos($c, ' ') === false && strpos($c, '-') === false; });
    foreach ($singleWordColors as $color) {
        // Word boundary check to avoid false positives (e.g., "golden" matching "gold")
        if (preg_match('/\b' . preg_quote($color, '/') . '\b/', $text)) {
            $colorName = ucfirst($color);
            if ($color === 'grey') $colorName = 'Gray';
            if (!in_array($colorName, $found)) {
                $found[] = $colorName;
            }
        }
    }

    if (!empty($found)) {
        return implode('/', array_slice($found, 0, 3));
    }

    // Fallback: detect material-based colors
    if (preg_match('/\b(tweed|tartan|plaid|aztec|floral|embroidery|sequin|jacquard)\b/', $text)) {
        return 'Multicolor';
    }
    if (preg_match('/\b(sterling silver|silver)\b/', $text)) {
        return 'Silver';
    }
    if (preg_match('/\b(gold|golden)\b/', $text)) {
        return 'Gold';
    }
    if (preg_match('/\b(crystal|swarovski|rhinestone|diamond)\b/', $text)) {
        return 'Clear';
    }
    if (preg_match('/\b(faux fur|mink|fur)\b/', $text)) {
        return 'Brown';
    }
    if (preg_match('/\b(ceramic|porcelain|china)\b/', $text)) {
        return 'White';
    }
    if (preg_match('/\b(wood|wooden|bamboo)\b/', $text)) {
        return 'Brown';
    }
    if (preg_match('/\b(starfish|beach|coastal|seashell)\b/', $text)) {
        return 'Beige';
    }
    if (preg_match('/\b(wool|knit|merino|aran|cable)\b/', $text)) {
        return 'Natural';
    }

    return 'Multicolor';
}

/**
 * Get Google product category based on product name and categories
 */
function getGoogleCategory($name, $categories = []) {
    global $categoryMappings;

    $nameLower = strtolower($name);
    $categoriesLower = strtolower(implode(' ', $categories));
    $combined = $nameLower . ' ' . $categoriesLower;

    foreach ($categoryMappings as $keyword => $googleCategory) {
        if (strpos($combined, $keyword) !== false) {
            return $googleCategory;
        }
    }

    return 'Apparel & Accessories';
}

/**
 * Check if a Google category requires apparel attributes (gender, age_group)
 */
function requiresApparelAttributes($googleCategory) {
    return (strpos($googleCategory, 'Apparel') !== false
        || strpos($googleCategory, 'Clothing') !== false
        || strpos($googleCategory, 'Jewelry') !== false
        || strpos($googleCategory, 'Handbag') !== false
        || strpos($googleCategory, 'Wallet') !== false
        || strpos($googleCategory, 'Backpack') !== false);
}

/**
 * Escape XML special characters
 */
function xmlEscape($string) {
    return htmlspecialchars($string ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/**
 * Clean description for XML (strip HTML, limit length)
 */
function cleanDescription($description, $maxLength = 5000) {
    $clean = strip_tags($description);
    $clean = html_entity_decode($clean, ENT_QUOTES, 'UTF-8');
    $clean = preg_replace('/\s+/', ' ', $clean);
    $clean = trim($clean);

    if (strlen($clean) > $maxLength) {
        $clean = substr($clean, 0, $maxLength - 3) . '...';
    }

    return $clean;
}

/**
 * Build an XML item entry
 */
function buildItem($data) {
    $xml = "<item>\n";
    foreach ($data as $tag => $value) {
        if ($value === null || $value === '') continue;
        if (is_array($value)) {
            // Nested element (e.g., shipping)
            $xml .= "  <{$tag}>\n";
            foreach ($value as $subTag => $subValue) {
                $xml .= "    <{$subTag}>" . xmlEscape($subValue) . "</{$subTag}>\n";
            }
            $xml .= "  </{$tag}>\n";
        } else {
            $xml .= "  <{$tag}>" . xmlEscape($value) . "</{$tag}>\n";
        }
    }
    $xml .= "</item>\n";
    return $xml;
}

echo "Starting Google Merchant Feed generation...\n";
echo "Store: {$storeName} ({$storeUrl})\n";
$startTime = microtime(true);

// Check if 'disabled' column exists
$hasDisabled = false;
try {
    $db->query("SELECT disabled FROM products LIMIT 1");
    $hasDisabled = true;
} catch (PDOException $e) {
    // Column doesn't exist
}

$disabledClause = $hasDisabled ? 'AND p.disabled = 0' : '';

// Check if ships_free columns exist
$hasShipsFree = false;
try {
    $db->query("SELECT ships_free FROM products LIMIT 1");
    $hasShipsFree = true;
} catch (PDOException $e) {}

$hasShipsFreeUs = false;
try {
    $db->query("SELECT ships_free_us FROM products LIMIT 1");
    $hasShipsFreeUs = true;
} catch (PDOException $e) {}

// Check if google_color column exists
$hasGoogleColor = false;
try {
    $db->query("SELECT google_color FROM products LIMIT 1");
    $hasGoogleColor = true;
} catch (PDOException $e) {}

// Check if product_image_option_values table exists
$hasImageOptionValues = false;
try {
    $db->query("SELECT 1 FROM product_image_option_values LIMIT 1");
    $hasImageOptionValues = true;
} catch (PDOException $e) {}

// Keep out-of-stock offers in the feed with the correct availability.
$products = $db->query("
    SELECT p.*,
           (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as primary_image
    FROM products p
    WHERE p.is_active = 1
    {$disabledClause}
    ORDER BY p.id
")->fetchAll();

echo "Found " . count($products) . " active products\n";

// Start XML
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
$xml .= "<channel>\n";
$xml .= "<title>" . xmlEscape($storeName) . "</title>\n";
$xml .= "<link>{$storeUrl}</link>\n";
$xml .= "<description>" . xmlEscape($storeName) . " Product Feed</description>\n";

$itemCount = 0;
$skipped = 0;

$categoryStmt = $db->prepare("
    SELECT c.name FROM categories c
    JOIN product_categories pc ON c.id = pc.category_id
    WHERE pc.product_id = ?
");
$variantStmt = $db->prepare("
    SELECT * FROM product_variants
    WHERE product_id = ? AND is_active = 1
    ORDER BY id
");
$variantOptionsStmt = $db->prepare("
    SELECT po.option_name, pov.value_name, pov.id AS option_value_id
    FROM variant_option_values vov
    JOIN product_option_values pov ON pov.id = vov.option_value_id
    JOIN product_options po ON po.id = pov.option_id
    WHERE vov.variant_id = ?
    ORDER BY po.sort_order, pov.sort_order, pov.id
");

foreach ($products as $product) {
    $productId = (int)$product['id'];
    $productName = (string)$product['name'];
    $description = cleanDescription($product['description'] ?: $productName);
    $productUrl = "{$storeUrl}/products/{$product['slug']}";
    $defaultImageUrl = !empty($product['primary_image']) ? "{$storeUrl}{$product['primary_image']}" : '';

    if ($defaultImageUrl === '') {
        echo "  Skipping #{$productId} ({$productName}) - no image\n";
        $skipped++;
        continue;
    }

    $categoryStmt->execute([$productId]);
    $categoryNames = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
    $googleCategory = getGoogleCategory($productName, $categoryNames);
    $needsApparelAttrs = requiresApparelAttributes($googleCategory);
    $gender = detectGender($productName, $product['description'] ?? '');
    $brand = !empty($product['manufacturer']) ? $product['manufacturer'] : $storeName;
    $googleColor = $hasGoogleColor ? ($product['google_color'] ?? null) : null;
    $detectedColor = detectColor($productName, $product['description'] ?? '', $googleColor);
    $freeShipping = ($hasShipsFree && !empty($product['ships_free']))
        || ($hasShipsFreeUs && !empty($product['ships_free_us']));
    $hasSale = !empty($product['sale_price'])
        && (float)$product['sale_price'] < (float)$product['price'];

    $variantStmt->execute([$productId]);
    $variants = $variantStmt->fetchAll();
    $offers = !empty($variants) ? $variants : [null];

    foreach ($offers as $variant) {
        $variantId = $variant !== null ? (int)$variant['id'] : null;
        $optionValues = [];
        if ($variantId !== null) {
            $variantOptionsStmt->execute([$variantId]);
            $optionValues = $variantOptionsStmt->fetchAll();
        }

        $color = null;
        $size = null;
        $variantParts = [];
        $optionValueIds = [];
        foreach ($optionValues as $optionValue) {
            $optionName = strtolower(trim((string)$optionValue['option_name']));
            $valueName = trim((string)$optionValue['value_name']);
            $variantParts[] = $valueName;
            $optionValueIds[] = (int)$optionValue['option_value_id'];

            if (
                in_array($optionName, ['color', 'colour'], true)
                || str_contains($optionName, 'tartan')
                || str_contains($optionName, 'pattern')
            ) {
                $color = $valueName;
            } elseif ($optionName === 'size' || str_contains($optionName, 'size')) {
                $size = $valueName;
            }
        }

        $imageUrl = $defaultImageUrl;
        if ($hasImageOptionValues && !empty($optionValueIds)) {
            $placeholders = implode(',', array_fill(0, count($optionValueIds), '?'));
            $imageStmt = $db->prepare("
                SELECT pi.image_path, COUNT(DISTINCT piov.option_value_id) AS match_count
                FROM product_images pi
                JOIN product_image_option_values piov ON piov.image_id = pi.id
                WHERE pi.product_id = ?
                  AND piov.option_value_id IN ({$placeholders})
                GROUP BY pi.id, pi.image_path, pi.is_primary, pi.sort_order
                ORDER BY match_count DESC, pi.is_primary DESC, pi.sort_order ASC, pi.id ASC
                LIMIT 1
            ");
            $imageStmt->execute(array_merge([$productId], $optionValueIds));
            $variantImagePath = $imageStmt->fetchColumn();
            if ($variantImagePath) {
                $imageUrl = "{$storeUrl}{$variantImagePath}";
            }
        }

        $adjustment = $variant !== null ? (float)($variant['price_adjustment'] ?? 0) : 0.0;
        $regularPrice = max(0.01, (float)$product['price'] + $adjustment);
        $currentPrice = $hasSale
            ? max(0.01, (float)$product['sale_price'] + $adjustment)
            : $regularPrice;
        $inventory = $variant !== null
            ? (int)($variant['inventory_count'] ?? 0)
            : (int)($product['inventory_count'] ?? 0);
        $weightOz = $variant !== null && !empty($variant['weight_oz'])
            ? (float)$variant['weight_oz']
            : (float)($product['weight_oz'] ?? 0);

        $item = [
            'g:id' => \App\Core\GoogleMerchantItemId::encode($productId, $variantId),
            'title' => !empty($variantParts)
                ? $productName . ' - ' . implode(' / ', $variantParts)
                : $productName,
            'description' => $description,
            'link' => $productUrl,
            'g:image_link' => $imageUrl,
            'g:availability' => $inventory > 0 ? 'in_stock' : 'out_of_stock',
            'g:price' => number_format($regularPrice, 2, '.', '') . ' ' . $currency,
            'g:brand' => $brand,
            'g:condition' => 'new',
            'g:google_product_category' => $googleCategory,
        ];

        if ($variantId !== null) {
            $item['g:item_group_id'] = (string)$productId;
        }
        if ($hasSale) {
            $item['g:sale_price'] = number_format($currentPrice, 2, '.', '') . ' ' . $currency;
        }
        if ($color !== null || ($variantId === null && $detectedColor)) {
            $item['g:color'] = $color ?? $detectedColor;
        }
        if ($size !== null) {
            $item['g:size'] = $size;
        }
        if ($needsApparelAttrs) {
            $item['g:gender'] = $gender;
            $item['g:age_group'] = 'adult';
        }
        if ($weightOz > 0) {
            $item['g:shipping_weight'] = round($weightOz / 16, 2) . ' lb';
        }

        $sku = $variant !== null ? ($variant['sku'] ?? null) : ($product['sku'] ?? null);
        if (!empty($sku)) {
            $item['g:mpn'] = $sku;
        }
        if ($freeShipping) {
            $item['g:shipping'] = [
                'g:country' => 'US',
                'g:price' => '0 ' . $currency,
            ];
        }

        $xml .= buildItem($item);
        $itemCount++;
    }
}

$xml .= "</channel>\n";
$xml .= "</rss>\n";

$options = getopt('', ['output:']);
$outputPath = isset($options['output']) && is_string($options['output'])
    ? $options['output']
    : BASE_PATH . '/public/google-merchant-feed.xml';
$outputDirectory = dirname($outputPath);
if (!is_dir($outputDirectory) || !is_writable($outputDirectory)) {
    throw new RuntimeException("Feed output directory is not writable: {$outputDirectory}");
}

$tempPath = $outputPath . '.tmp.' . getmypid();
if (file_put_contents($tempPath, $xml, LOCK_EX) === false || !rename($tempPath, $outputPath)) {
    @unlink($tempPath);
    throw new RuntimeException("Unable to write feed: {$outputPath}");
}
chmod($outputPath, 0644);

$duration = round(microtime(true) - $startTime, 2);
echo "\nFeed generation complete!\n";
echo "Items in feed: {$itemCount}\n";
echo "Skipped (no image): {$skipped}\n";
echo "Output: {$outputPath}\n";
echo "Duration: {$duration}s\n";
echo "Generated: " . date('Y-m-d H:i:s') . "\n";
