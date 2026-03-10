<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class SitemapController extends Controller
{
    private string $baseUrl;

    /**
     * Generate dynamic XML sitemap
     */
    public function index(): void
    {
        $this->baseUrl = appUrl();
        $db = Database::getInstance();

        // Set XML headers
        header('Content-Type: application/xml; charset=UTF-8');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Static pages
        $staticPages = [
            ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => '/products', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => '/contact', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => '/privacy', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => '/terms', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        foreach ($staticPages as $page) {
            $xml .= $this->createUrlEntry(
                $page['loc'],
                null,
                $page['changefreq'],
                $page['priority']
            );
        }

        // Categories
        $categories = $db->select("SELECT slug, created_at FROM categories ORDER BY name");
        foreach ($categories as $category) {
            $lastmod = $category['created_at'] ? date('Y-m-d', strtotime($category['created_at'])) : null;
            $xml .= $this->createUrlEntry(
                '/category/' . $category['slug'],
                $lastmod,
                'weekly',
                '0.8'
            );
        }

        // Products
        $products = $db->select("SELECT slug, updated_at FROM products WHERE is_active = 1 AND disabled = 0 ORDER BY name");
        foreach ($products as $product) {
            $lastmod = $product['updated_at'] ? date('Y-m-d', strtotime($product['updated_at'])) : null;
            $xml .= $this->createUrlEntry(
                '/products/' . $product['slug'],
                $lastmod,
                'weekly',
                '0.7'
            );
        }

        // Content pages (informational/SEO pages)
        try {
            $pages = $db->select("SELECT slug, updated_at FROM pages ORDER BY slug");
            foreach ($pages as $page) {
                $lastmod = !empty($page['updated_at']) ? date('Y-m-d', strtotime($page['updated_at'])) : null;
                $xml .= $this->createUrlEntry(
                    '/pages/' . $page['slug'],
                    $lastmod,
                    'monthly',
                    '0.6'
                );
            }
        } catch (\Exception $e) {
            // pages table may not exist on all installs
        }

        $xml .= '</urlset>';

        echo $xml;
        exit;
    }

    /**
     * Generate dynamic robots.txt with correct sitemap URL
     */
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');

        $sitemapUrl = appUrl() . '/sitemap.xml';

        $robots = <<<ROBOTS
# Robots.txt for Apparix E-Commerce
# Allow legitimate search engines, AI answer engines, and social bots

# Default — allow all well-behaved crawlers
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /cart/
Disallow: /checkout/
Disallow: /account/
Disallow: /login
Disallow: /register
Disallow: /logout
Disallow: /webhook/
Disallow: /api/
Disallow: /search?
Disallow: /cron/
Disallow: /install/
Disallow: /storage/

# Block aggressive scrapers and SEO crawlers
User-agent: Bytespider
Disallow: /

User-agent: PetalBot
Disallow: /

User-agent: CCBot
Disallow: /

User-agent: Omgilibot
Disallow: /

User-agent: MJ12bot
Disallow: /

User-agent: AhrefsBot
Disallow: /

User-agent: SemrushBot
Disallow: /

User-agent: DotBot
Disallow: /

User-agent: SEOkicks-Robot
Disallow: /

User-agent: BLEXBot
Disallow: /

User-agent: DataForSeoBot
Disallow: /

User-agent: Rogerbot
Disallow: /

Sitemap: {$sitemapUrl}
ROBOTS;

        echo $robots;
        exit;
    }

    /**
     * Generate dynamic manifest.json with correct store name
     */
    public function manifest(): void
    {
        header('Content-Type: application/manifest+json; charset=UTF-8');

        $name = setting('store_name') ?: appName();
        $description = setting('store_tagline') ?: setting('seo_description') ?: 'Shop our collection of quality products.';
        $themeColor = '#FF68C5';

        $manifest = [
            'name' => $name,
            'short_name' => $name,
            'description' => $description,
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#fdf2f8',
            'theme_color' => $themeColor,
            'orientation' => 'portrait-primary',
            'scope' => '/',
            'icons' => [
                ['src' => '/android-chrome-192x192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => '/android-chrome-512x512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => '/apple-touch-icon.png', 'sizes' => '180x180', 'type' => 'image/png'],
            ],
            'categories' => ['shopping', 'lifestyle'],
            'lang' => 'en-US',
            'dir' => 'ltr',
            'shortcuts' => [
                ['name' => 'Shop All Products', 'short_name' => 'Shop', 'description' => 'Browse our full collection', 'url' => '/products', 'icons' => [['src' => '/android-chrome-192x192.png', 'sizes' => '192x192']]],
                ['name' => 'My Cart', 'short_name' => 'Cart', 'description' => 'View your shopping cart', 'url' => '/cart', 'icons' => [['src' => '/android-chrome-192x192.png', 'sizes' => '192x192']]],
            ],
        ];

        echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Create a single URL entry for sitemap
     */
    private function createUrlEntry(string $loc, ?string $lastmod, string $changefreq, string $priority): string
    {
        $entry = "  <url>\n" .
               "    <loc>" . htmlspecialchars($this->baseUrl . $loc, ENT_XML1) . "</loc>\n";
        if ($lastmod !== null) {
            $entry .= "    <lastmod>{$lastmod}</lastmod>\n";
        }
        $entry .= "    <changefreq>{$changefreq}</changefreq>\n" .
               "    <priority>{$priority}</priority>\n" .
               "  </url>\n";
        return $entry;
    }
}
