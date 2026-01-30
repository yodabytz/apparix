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

// Load environment variables
$envFile = BASE_PATH . '/.env';
if (!file_exists($envFile)) {
    die("Error: .env file not found\n");
}

$env = parse_ini_file($envFile);
if (!$env) {
    die("Error: Could not parse .env file\n");
}

// Database connection
try {
    $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4";
    $db = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Get store configuration
$storeUrl = rtrim($env['APP_URL'] ?? 'https://example.com', '/');
$storeName = $env['APP_NAME'] ?? 'Store';

// Google category mappings
$categoryMappings = [
    'clothing' => 'Apparel & Accessories > Clothing',
    'sweaters' => 'Apparel & Accessories > Clothing > Shirts & Tops > Sweaters',
    'cardigans' => 'Apparel & Accessories > Clothing > Shirts & Tops > Sweaters',
    'shirts' => 'Apparel & Accessories > Clothing > Shirts & Tops',
    'pants' => 'Apparel & Accessories > Clothing > Pants',
    'coats' => 'Apparel & Accessories > Clothing > Outerwear > Coats & Jackets',
    'jackets' => 'Apparel & Accessories > Clothing > Outerwear > Coats & Jackets',
    'ponchos' => 'Apparel & Accessories > Clothing > Outerwear > Capes & Ponchos',
    'capes' => 'Apparel & Accessories > Clothing > Outerwear > Capes & Ponchos',
    'scarves' => 'Apparel & Accessories > Clothing Accessories > Scarves & Shawls',
    'shawls' => 'Apparel & Accessories > Clothing Accessories > Scarves & Shawls',
    'hats' => 'Apparel & Accessories > Clothing Accessories > Hats',
    'gloves' => 'Apparel & Accessories > Clothing Accessories > Gloves & Mittens',
    'bags' => 'Apparel & Accessories > Handbags, Wallets & Cases > Handbags',
    'purses' => 'Apparel & Accessories > Handbags, Wallets & Cases > Handbags',
    'totes' => 'Apparel & Accessories > Handbags, Wallets & Cases > Handbags',
    'blankets' => 'Home & Garden > Linens & Bedding > Bedding > Blankets & Throws',
    'throws' => 'Home & Garden > Linens & Bedding > Bedding > Blankets & Throws',
    'bedding' => 'Home & Garden > Linens & Bedding > Bedding',
    'pillows' => 'Home & Garden > Linens & Bedding > Bedding > Pillows',
    'cushions' => 'Home & Garden > Linens & Bedding > Bedding > Pillows',
    'software' => 'Software',
    'plugins' => 'Software > Computer Software',
    'default' => 'Apparel & Accessories'
];

// Female/male keywords for gender detection
$femaleKeywords = ['women', 'womens', "women's", 'ladies', 'lady', 'female', 'her', 'girl', 'feminine'];
$maleKeywords = ['men', 'mens', "men's", 'male', 'him', 'boy', 'gentleman', 'masculine'];

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

    return null; // Will default to 'unisex' for apparel
}

/**
 * Get Google product category based on product name and categories
 */
function getGoogleCategory($name, $categories = []) {
    global $categoryMappings;

    $nameLower = strtolower($name);
    $categoriesLower = strtolower(implode(' ', $categories));
    $combined = $nameLower . ' ' . $categoriesLower;

    // Check specific product types first
    foreach ($categoryMappings as $keyword => $googleCategory) {
        if ($keyword !== 'default' && strpos($combined, $keyword) !== false) {
            return $googleCategory;
        }
    }

    return $categoryMappings['default'];
}

/**
 * Escape XML special characters
 */
function xmlEscape($string) {
    return htmlspecialchars($string, ENT_XML1 | ENT_QUOTES, 'UTF-8');
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

echo "Starting Google Merchant Feed generation...\n";
$startTime = microtime(true);

// Get all active products with inventory
$products = $db->query("
    SELECT p.*,
           (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as primary_image,
           (SELECT SUM(COALESCE(pv.inventory_count, 0)) FROM product_variants pv WHERE pv.product_id = p.id AND pv.is_active = 1) as variant_inventory
    FROM products p
    WHERE p.is_active = 1
    AND (p.inventory_count > 0 OR EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id AND pv.inventory_count > 0 AND pv.is_active = 1))
    ORDER BY p.id
")->fetchAll();

echo "Found " . count($products) . " active products with inventory\n";

// Start XML
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
$xml .= "<channel>\n";
$xml .= "<title>" . xmlEscape($storeName) . "</title>\n";
$xml .= "<link>{$storeUrl}</link>\n";
$xml .= "<description>" . xmlEscape($storeName) . " Product Feed</description>\n";

$itemCount = 0;

foreach ($products as $product) {
    $productId = $product['id'];
    $productName = $product['name'];
    $productSlug = $product['slug'];
    $description = cleanDescription($product['description'] ?: $productName);
    $price = number_format((float)($product['sale_price'] ?: $product['price']), 2, '.', '');
    $originalPrice = number_format((float)$product['price'], 2, '.', '');
    $productUrl = "{$storeUrl}/products/{$productSlug}";
    $imageUrl = $product['primary_image'] ? "{$storeUrl}{$product['primary_image']}" : '';

    // Skip products without images
    if (empty($imageUrl)) {
        echo "Skipping product #{$productId} - no image\n";
        continue;
    }

    // Get product categories
    $categories = $db->prepare("
        SELECT c.name FROM categories c
        JOIN product_categories pc ON c.id = pc.category_id
        WHERE pc.product_id = ?
    ");
    $categories->execute([$productId]);
    $categoryNames = $categories->fetchAll(PDO::FETCH_COLUMN);

    $googleCategory = getGoogleCategory($productName, $categoryNames);
    $isApparel = (strpos($googleCategory, 'Apparel') !== false || strpos($googleCategory, 'Clothing') !== false);
    $gender = detectGender($productName, $product['description'] ?? '');

    // Get product options (colors, sizes)
    $options = $db->prepare("
        SELECT po.option_name, pov.value_name
        FROM product_options po
        JOIN product_option_values pov ON po.id = pov.option_id
        WHERE po.product_id = ?
        ORDER BY po.sort_order, pov.sort_order
    ");
    $options->execute([$productId]);
    $optionValues = $options->fetchAll();

    $colors = [];
    $sizes = [];

    foreach ($optionValues as $opt) {
        $optName = strtolower($opt['option_name']);
        if ($optName === 'color' || $optName === 'colour' || strpos($optName, 'tartan') !== false || strpos($optName, 'pattern') !== false) {
            $colors[] = $opt['value_name'];
        } elseif ($optName === 'size') {
            $sizes[] = $opt['value_name'];
        }
    }

    // Availability
    $totalInventory = $product['inventory_count'];
    if ($product['variant_inventory'] !== null) {
        $totalInventory = (int)$product['variant_inventory'];
    }
    $availability = $totalInventory > 0 ? 'in_stock' : 'out_of_stock';

    // Shipping weight
    $weightLbs = $product['weight_oz'] ? round($product['weight_oz'] / 16, 2) : null;

    // If product has color variants, create separate items for each color
    if (!empty($colors)) {
        foreach ($colors as $colorIndex => $color) {
            $itemId = $productId . '-' . ($colorIndex + 1);
            $itemTitle = $productName . ' - ' . $color;

            // Try to find color-specific image
            $colorImage = $db->prepare("
                SELECT pi.image_path
                FROM product_images pi
                JOIN product_image_option_values piov ON pi.id = piov.image_id
                JOIN product_option_values pov ON piov.option_value_id = pov.id
                WHERE pi.product_id = ? AND LOWER(pov.value_name) = LOWER(?)
                ORDER BY pi.is_primary DESC, pi.sort_order ASC
                LIMIT 1
            ");
            $colorImage->execute([$productId, $color]);
            $colorImagePath = $colorImage->fetchColumn();
            $itemImageUrl = $colorImagePath ? "{$storeUrl}{$colorImagePath}" : $imageUrl;

            $xml .= "<item>\n";
            $xml .= "  <g:id>{$itemId}</g:id>\n";
            $xml .= "  <g:item_group_id>{$productId}</g:item_group_id>\n";
            $xml .= "  <title>" . xmlEscape($itemTitle) . "</title>\n";
            $xml .= "  <description>" . xmlEscape($description) . "</description>\n";
            $xml .= "  <link>{$productUrl}</link>\n";
            $xml .= "  <g:image_link>{$itemImageUrl}</g:image_link>\n";
            $xml .= "  <g:availability>{$availability}</g:availability>\n";
            $xml .= "  <g:price>{$price} USD</g:price>\n";

            if ($product['sale_price'] && $product['sale_price'] < $product['price']) {
                $xml .= "  <g:sale_price>{$price} USD</g:sale_price>\n";
                $xml .= "  <g:price>{$originalPrice} USD</g:price>\n";
            }

            $xml .= "  <g:brand>" . xmlEscape($storeName) . "</g:brand>\n";
            $xml .= "  <g:condition>new</g:condition>\n";
            $xml .= "  <g:google_product_category>" . xmlEscape($googleCategory) . "</g:google_product_category>\n";
            $xml .= "  <g:color>" . xmlEscape($color) . "</g:color>\n";

            if (!empty($sizes)) {
                $xml .= "  <g:size>" . xmlEscape(implode('/', $sizes)) . "</g:size>\n";
            }

            if ($isApparel) {
                $genderValue = $gender ?: 'unisex';
                $xml .= "  <g:gender>{$genderValue}</g:gender>\n";
                $xml .= "  <g:age_group>adult</g:age_group>\n";
            }

            if ($weightLbs) {
                $xml .= "  <g:shipping_weight>{$weightLbs} lb</g:shipping_weight>\n";
            }

            if ($product['ships_free'] || $product['ships_free_us']) {
                $xml .= "  <g:shipping>\n";
                $xml .= "    <g:country>US</g:country>\n";
                $xml .= "    <g:price>0 USD</g:price>\n";
                $xml .= "  </g:shipping>\n";
            }

            if ($product['sku']) {
                $xml .= "  <g:mpn>" . xmlEscape($product['sku']) . "-" . ($colorIndex + 1) . "</g:mpn>\n";
            }

            $xml .= "</item>\n";
            $itemCount++;
        }
    } else {
        // No color variants - single item
        $xml .= "<item>\n";
        $xml .= "  <g:id>{$productId}</g:id>\n";
        $xml .= "  <title>" . xmlEscape($productName) . "</title>\n";
        $xml .= "  <description>" . xmlEscape($description) . "</description>\n";
        $xml .= "  <link>{$productUrl}</link>\n";
        $xml .= "  <g:image_link>{$imageUrl}</g:image_link>\n";
        $xml .= "  <g:availability>{$availability}</g:availability>\n";
        $xml .= "  <g:price>{$price} USD</g:price>\n";

        if ($product['sale_price'] && $product['sale_price'] < $product['price']) {
            $xml .= "  <g:sale_price>{$price} USD</g:sale_price>\n";
            $xml .= "  <g:price>{$originalPrice} USD</g:price>\n";
        }

        $xml .= "  <g:brand>" . xmlEscape($storeName) . "</g:brand>\n";
        $xml .= "  <g:condition>new</g:condition>\n";
        $xml .= "  <g:google_product_category>" . xmlEscape($googleCategory) . "</g:google_product_category>\n";

        if (!empty($sizes)) {
            $xml .= "  <g:size>" . xmlEscape(implode('/', $sizes)) . "</g:size>\n";
        }

        if ($isApparel) {
            $genderValue = $gender ?: 'unisex';
            $xml .= "  <g:gender>{$genderValue}</g:gender>\n";
            $xml .= "  <g:age_group>adult</g:age_group>\n";
        }

        if ($weightLbs) {
            $xml .= "  <g:shipping_weight>{$weightLbs} lb</g:shipping_weight>\n";
        }

        if ($product['ships_free'] || $product['ships_free_us']) {
            $xml .= "  <g:shipping>\n";
            $xml .= "    <g:country>US</g:country>\n";
            $xml .= "    <g:price>0 USD</g:price>\n";
            $xml .= "  </g:shipping>\n";
        }

        if ($product['sku']) {
            $xml .= "  <g:mpn>" . xmlEscape($product['sku']) . "</g:mpn>\n";
        }

        $xml .= "</item>\n";
        $itemCount++;
    }
}

$xml .= "</channel>\n";
$xml .= "</rss>\n";

// Write to file
$outputPath = BASE_PATH . '/public/google-merchant-feed.xml';
file_put_contents($outputPath, $xml);

$duration = round(microtime(true) - $startTime, 2);
echo "\nFeed generation complete!\n";
echo "Items: {$itemCount}\n";
echo "Output: {$outputPath}\n";
echo "Duration: {$duration}s\n";
echo "Generated: " . date('Y-m-d H:i:s') . "\n";
