<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Product;

class ProductController extends Controller
{
    private Product $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
    }

    /**
     * Display all products with pagination
     */
    public function index(): void
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $sort = $_GET['sort'] ?? 'default';  // Use admin-defined order by default
        $perPage = 12;

        $products = $this->productModel->paginateWithImages($page, $perPage, $sort);
        $totalProducts = $this->productModel->countActive();
        $totalPages = ceil($totalProducts / $perPage);

        // Get categories with product counts (hierarchical)
        $db = Database::getInstance();
        $categories = $this->getCategoriesHierarchy($db);

        $data = [
            'title' => 'Shop All Products',
            'products' => $products,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalProducts' => $totalProducts,
            'categories' => $categories,
            'sort' => $sort,
        ];

        $this->render('products.index', $data);
    }

    /**
     * Display single product by slug
     */
    public function show(): void
    {
        $slug = $_GET['slug'] ?? '';

        if (!$slug) {
            http_response_code(404);
            die('404 Not Found');
        }

        $product = $this->productModel->findBy('slug', $slug);

        if (!$product) {
            http_response_code(404);
            die('404 Not Found - Product not found');
        }

        // Check if product is disabled (completely hidden - 404)
        if (!empty($product['disabled'])) {
            http_response_code(404);
            die('404 Not Found - Product not found');
        }

        // Get full product data (images, options, variants)
        $product = $this->productModel->getFullProduct($product['id']);

        // Get product recommendations (customers also bought + related)
        $related = $this->productModel->getRecommendations($product['id'], 4);

        // Get primary image for OG tags
        $ogImage = null;
        if (!empty($product['images'])) {
            $ogImage = appUrl() . $product['images'][0]['image_path'];
        }

        // Build JSON-LD structured data
        $jsonLd = $this->buildProductJsonLd($product, $ogImage);

        // Get latest version for license products
        $latestVersion = null;
        if (!empty($product['is_license_product'])) {
            // Use product's own version if set (for plugins)
            if (!empty($product['version'])) {
                $latestVersion = $product['version'];
            } else {
                // Fall back to releases table (for main Apparix product)
                $db = $this->productModel->getDb();
                $release = $db->selectOne(
                    "SELECT version FROM releases WHERE is_active = 1 ORDER BY version_major DESC, version_minor DESC, version_patch DESC LIMIT 1"
                );
                if ($release) {
                    $latestVersion = $release['version'];
                }
            }
        }

        $data = [
            'title' => $product['name'],
            'product' => $product,
            'related' => $related,
            'latestVersion' => $latestVersion,
            'metaKeywords' => $product['meta_keywords'] ?? '',
            'metaDescription' => $product['meta_description'] ?? substr(strip_tags($product['description'] ?? ''), 0, 160),
            'ogImage' => $ogImage,
            'jsonLd' => $jsonLd,
        ];

        $this->render('products.show', $data);
    }

    /**
     * Display products by category
     */
    public function byCategory(): void
    {
        $slug = $_GET['slug'] ?? '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $sort = $_GET['sort'] ?? 'default';  // Use admin-defined order by default
        $perPage = 12;

        if (!$slug) {
            http_response_code(404);
            die('404 Not Found');
        }

        // Get category
        $db = $this->productModel->getDb();
        $category = $db->selectOne(
            "SELECT * FROM categories WHERE slug = ?",
            [$slug]
        );

        if (!$category) {
            http_response_code(404);
            die('404 Not Found - Category not found');
        }

        // Get subcategories with images if show_subcategory_grid is enabled
        $subcategories = [];
        if (!empty($category['show_subcategory_grid'])) {
            $subcategories = $db->select(
                "SELECT c.*,
                        (SELECT COUNT(DISTINCT pc.product_id) FROM product_categories pc
                         JOIN products p ON pc.product_id = p.id AND p.is_active = 1
                         WHERE pc.category_id = c.id) as product_count
                 FROM categories c
                 WHERE c.parent_id = ?
                 ORDER BY c.sort_order ASC, c.name ASC",
                [$category['id']]
            );
        }

        // Determine sort order - default uses admin-defined sort_order
        $orderBy = match($sort) {
            'price-low' => 'COALESCE(p.sale_price, p.price) ASC',
            'price-high' => 'COALESCE(p.sale_price, p.price) DESC',
            'name-az' => 'p.name ASC',
            'name-za' => 'p.name DESC',
            'newest' => 'p.created_at DESC',
            default => 'pc.sort_order ASC, p.created_at DESC', // admin-defined category order
        };

        // Get products in category with pagination and sorting
        $products = $db->select(
            "SELECT DISTINCT p.*,
                    (SELECT image_path FROM product_images WHERE product_id = p.id AND is_video = 0 ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as primary_image,
                    (SELECT image_path FROM product_images WHERE product_id = p.id AND is_video = 1 ORDER BY sort_order LIMIT 1) as video_path
             FROM products p
             JOIN product_categories pc ON p.id = pc.product_id
             WHERE pc.category_id = ? AND p.is_active = 1
             ORDER BY {$orderBy} LIMIT ? OFFSET ?",
            [$category['id'], $perPage, ($page - 1) * $perPage]
        );

        // Count total products in category
        $countResult = $db->selectOne(
            "SELECT COUNT(DISTINCT p.id) as count FROM products p
             JOIN product_categories pc ON p.id = pc.product_id
             WHERE pc.category_id = ? AND p.is_active = 1",
            [$category['id']]
        );
        $totalProducts = $countResult['count'];
        $totalPages = ceil($totalProducts / $perPage);

        // Get all categories with product counts for the filter bar (hierarchical)
        $categories = $this->getCategoriesHierarchy($db);

        // Build BreadcrumbList JSON-LD
        $jsonLd = $this->buildCategoryJsonLd($category);

        $data = [
            'title' => $category['name'],
            'category' => $category,
            'subcategories' => $subcategories,
            'products' => $products,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalProducts' => $totalProducts,
            'categories' => $categories,
            'currentCategory' => $slug,
            'sort' => $sort,
            'jsonLd' => $jsonLd,
        ];

        $this->render('products.category', $data);
    }

    /**
     * Search products - supports both AJAX and regular page requests
     */
    public function search(): void
    {
        // Get query from POST or GET
        $query = $this->post('q') ?: $this->get('q', '');
        $isAjax = $this->isAjax();

        // For AJAX requests, require CSRF
        if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireValidCSRF();
        }

        // Validate query length
        if (strlen(trim($query)) < 2) {
            if ($isAjax) {
                $this->json(['error' => 'Search query too short'], 400);
                return;
            }
            // For page requests, show empty results with message
            $this->renderSearchResults($query, [], 'Please enter at least 2 characters to search.');
            return;
        }

        $products = $this->productModel->search($query);

        if ($isAjax) {
            $this->json([
                'success' => true,
                'count' => count($products),
                'products' => $products,
            ]);
            return;
        }

        // Render search results page
        $this->renderSearchResults($query, $products);
    }

    /**
     * Render search results page
     */
    private function renderSearchResults(string $query, array $products, string $message = ''): void
    {
        $db = Database::getInstance();
        $categories = $this->getCategoriesHierarchy($db);

        $data = [
            'title' => 'Search Results - Lily\'s Pad Studio',
            'meta_description' => 'Search results for "' . escape($query) . '" at Lily\'s Pad Studio',
            'query' => $query,
            'products' => $products,
            'productCount' => count($products),
            'categories' => $categories,
            'message' => $message,
        ];

        $this->render('products.search', $data);
    }

    /**
     * Get categories in hierarchical structure with product counts (3 levels deep)
     */
    private function getCategoriesHierarchy(Database $db): array
    {
        // Get all categories with product counts
        $allCategories = $db->select(
            "SELECT c.*, COUNT(DISTINCT pc.product_id) as product_count
             FROM categories c
             LEFT JOIN product_categories pc ON c.id = pc.category_id
             LEFT JOIN products p ON pc.product_id = p.id AND p.is_active = 1
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.name ASC"
        );

        // Index all categories by ID for easy lookup
        $indexed = [];
        foreach ($allCategories as $cat) {
            $cat['children'] = [];
            $indexed[$cat['id']] = $cat;
        }

        // Build hierarchy - assign children to their parents
        $topLevel = [];
        foreach ($indexed as $id => $cat) {
            if ($cat['parent_id'] && isset($indexed[$cat['parent_id']])) {
                // This category has a parent - add it as a child
                $indexed[$cat['parent_id']]['children'][] = &$indexed[$id];
            } elseif (!$cat['parent_id']) {
                // Top-level category
                $topLevel[] = &$indexed[$id];
            }
        }

        return $topLevel;
    }

    /**
     * Build JSON-LD structured data for a product
     */
    private function buildProductJsonLd(array $product, ?string $ogImage): string
    {
        $baseUrl = appUrl();

        // Determine price and availability
        $price = $product['sale_price'] ?? $product['price'];
        $inStock = ($product['inventory_count'] ?? 0) > 0;

        // Build image array
        $images = [];
        if (!empty($product['images'])) {
            foreach ($product['images'] as $img) {
                $images[] = $baseUrl . $img['image_path'];
            }
        } elseif ($ogImage) {
            $images[] = $ogImage;
        }

        // Get category for BreadcrumbList
        $breadcrumbs = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => $baseUrl
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'Shop',
                'item' => $baseUrl . '/products'
            ],
            [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $product['name']
            ]
        ];

        // If product has a category, insert it
        if (!empty($product['category_name'])) {
            // Insert category between Shop and Product
            $breadcrumbs[2] = [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $product['category_name'],
                'item' => $baseUrl . '/category/' . ($product['category_slug'] ?? '')
            ];
            $breadcrumbs[] = [
                '@type' => 'ListItem',
                'position' => 4,
                'name' => $product['name']
            ];
        }

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => [
                // Product schema
                [
                    '@type' => 'Product',
                    'name' => $product['name'],
                    'description' => strip_tags($product['description'] ?? ''),
                    'image' => $images,
                    'sku' => $product['sku'] ?? $product['id'],
                    'url' => $baseUrl . '/products/' . $product['slug'],
                    'brand' => [
                        '@type' => 'Brand',
                        'name' => 'Lily\'s Pad Studio'
                    ],
                    'offers' => [
                        '@type' => 'Offer',
                        'url' => $baseUrl . '/products/' . $product['slug'],
                        'priceCurrency' => 'USD',
                        'price' => number_format((float)$price, 2, '.', ''),
                        'availability' => $inStock
                            ? 'https://schema.org/InStock'
                            : 'https://schema.org/OutOfStock',
                        'seller' => [
                            '@type' => 'Organization',
                            'name' => 'Lily\'s Pad Studio'
                        ]
                    ]
                ],
                // BreadcrumbList schema
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => $breadcrumbs
                ]
            ]
        ];

        return json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Build JSON-LD structured data for a category page
     */
    private function buildCategoryJsonLd(array $category): string
    {
        $baseUrl = appUrl();

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => $baseUrl
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Shop',
                    'item' => $baseUrl . '/products'
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $category['name']
                ]
            ]
        ];

        return json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
