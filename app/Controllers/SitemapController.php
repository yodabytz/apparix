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
                date('Y-m-d'),
                $page['changefreq'],
                $page['priority']
            );
        }

        // Categories
        $categories = $db->select("SELECT slug, created_at FROM categories ORDER BY name");
        foreach ($categories as $category) {
            $lastmod = $category['created_at'] ? date('Y-m-d', strtotime($category['created_at'])) : date('Y-m-d');
            $xml .= $this->createUrlEntry(
                '/category/' . $category['slug'],
                $lastmod,
                'weekly',
                '0.8'
            );
        }

        // Products
        $products = $db->select("SELECT slug, updated_at FROM products WHERE is_active = 1 ORDER BY name");
        foreach ($products as $product) {
            $lastmod = $product['updated_at'] ? date('Y-m-d', strtotime($product['updated_at'])) : date('Y-m-d');
            $xml .= $this->createUrlEntry(
                '/products/' . $product['slug'],
                $lastmod,
                'weekly',
                '0.7'
            );
        }

        $xml .= '</urlset>';

        echo $xml;
        exit;
    }

    /**
     * Create a single URL entry for sitemap
     */
    private function createUrlEntry(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        return "  <url>\n" .
               "    <loc>" . htmlspecialchars($this->baseUrl . $loc, ENT_XML1) . "</loc>\n" .
               "    <lastmod>{$lastmod}</lastmod>\n" .
               "    <changefreq>{$changefreq}</changefreq>\n" .
               "    <priority>{$priority}</priority>\n" .
               "  </url>\n";
    }
}
