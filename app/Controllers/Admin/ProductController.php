<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\License;
use App\Models\AdminUser;
use App\Models\Product;

class ProductController extends Controller
{
    private AdminUser $adminModel;
    private Product $productModel;
    private ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminUser();
        $this->productModel = new Product();
        $this->requireAdmin();
    }

    protected function requireAdmin(): void
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        $token = $_COOKIE['admin_token'] ?? null;
        if (!$token) {
            if ($isAjax) {
                $this->json(['error' => 'Not authenticated', 'code' => 'NO_TOKEN'], 401);
                exit;
            }
            $this->redirect('/admin/login');
            exit;
        }

        $session = $this->adminModel->validateSession($token);
        if (!$session) {
            setcookie('admin_token', '', ['expires' => time() - 3600, 'path' => '/admin']);
            if ($isAjax) {
                $this->json(['error' => 'Session expired', 'code' => 'INVALID_SESSION'], 401);
                exit;
            }
            $this->redirect('/admin/login');
            exit;
        }

        $this->admin = $session;
    }

    /**
     * List all products
     */
    public function index(): void
    {
        $db = Database::getInstance();
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';
        $categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;

        // Fetch all categories for dropdown
        $categories = $db->select("SELECT id, name FROM categories ORDER BY name");

        // Build query with optional search and filters
        $params = [];
        $whereClauses = [];
        $joinClause = '';
        $orderBy = 'p.sort_order ASC, p.created_at DESC';

        // Category filter - use category-specific sort order
        if ($categoryId > 0) {
            $joinClause = 'INNER JOIN product_categories pc ON p.id = pc.product_id AND pc.category_id = ?';
            $params[] = $categoryId;
            $orderBy = 'pc.sort_order ASC, p.created_at DESC';
        }

        if ($search !== '') {
            $whereClauses[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }

        // Filter: products missing cost data (excludes products marked as cost_not_applicable)
        if ($filter === 'missing_cost') {
            $whereClauses[] = "p.cost_not_applicable = 0";
            $whereClauses[] = "(
                EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id AND pv.cost IS NULL AND p.cost IS NULL)
                OR
                (NOT EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id) AND p.cost IS NULL)
            )";
        }

        $whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        $products = $db->select(
            "SELECT p.*,
                    (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as image,
                    (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) as variant_count,
                    (SELECT COALESCE(SUM(inventory_count), 0) FROM product_variants WHERE product_id = p.id AND is_active = 1) as variant_stock
             FROM products p
             {$joinClause}
             {$whereClause}
             ORDER BY {$orderBy}
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        // Count query
        $countParams = $categoryId > 0 ? [$categoryId] : [];
        if ($search !== '') {
            $countParams = array_merge($countParams, [$searchTerm, $searchTerm, $searchTerm]);
        }
        $countQuery = "SELECT COUNT(*) as count FROM products p {$joinClause} {$whereClause}";
        $totalCount = $db->selectOne($countQuery, $countParams);
        $totalPages = ceil($totalCount['count'] / $perPage);

        $title = 'Products';
        if ($filter === 'missing_cost') {
            $title = 'Products Missing Cost';
        }
        if ($categoryId > 0) {
            $currentCategory = $db->selectOne("SELECT name FROM categories WHERE id = ?", [$categoryId]);
            if ($currentCategory) {
                $title = $currentCategory['name'];
            }
        }

        $this->render('admin.products.index', [
            'title' => $title,
            'admin' => $this->admin,
            'products' => $products,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount['count'],
            'search' => $search,
            'filter' => $filter,
            'categories' => $categories,
            'categoryId' => $categoryId
        ], 'admin');
    }

    /**
     * Show create product form
     */
    public function create(): void
    {
        // Check license product limit before showing form
        $currentProductCount = $this->productModel->count();
        if (!License::canAddProduct($currentProductCount)) {
            $limit = License::getLimit('max_products');
            setFlash('error', "Product limit reached ({$limit} products). <a href='" . License::getUpgradeUrl() . "' target='_blank'>Upgrade your license</a> to add more products.");
            $this->redirect('/admin/products');
            return;
        }

        $db = Database::getInstance();
        $categories = $db->select("SELECT * FROM categories ORDER BY name");

        $this->render('admin.products.create', [
            'title' => 'Add New Product',
            'admin' => $this->admin,
            'categories' => $categories,
            'license' => License::getEditionInfo()
        ], 'admin');
    }

    /**
     * Store new product
     */
    public function store(): void
    {
        $this->requireValidCSRF();
        $db = Database::getInstance();

        // Check license product limit
        $currentProductCount = $this->productModel->count();
        if (!License::canAddProduct($currentProductCount)) {
            $limit = License::getLimit('max_products');
            setFlash('error', "Product limit reached ({$limit} products). Please upgrade your license to add more products.");
            $this->redirect('/admin/products');
            return;
        }

        // Validate input
        $name = trim($this->post('name', ''));
        $sku = trim($this->post('sku', ''));
        $description = trim($this->post('description', ''));
        $price = floatval($this->post('price', 0));
        $salePrice = $this->post('sale_price') ? floatval($this->post('sale_price')) : null;
        $inventory = intval($this->post('inventory_count', 0));
        $isActive = $this->post('is_active') ? 1 : 0;
        $featured = $this->post('featured') ? 1 : 0;
        $manufacturer = trim($this->post('manufacturer', '')) ?: null;

        // Digital product fields
        $isDigital = $this->post('is_digital') ? 1 : 0;
        $isLicenseProduct = $this->post('is_license_product') ? 1 : 0;
        $downloadFile = $isDigital ? trim($this->post('download_file', '')) ?: null : null;
        $downloadLimit = $isDigital ? intval($this->post('download_limit', 5)) : null;

        if (empty($name) || $price <= 0) {
            setFlash('error', 'Product name and price are required');
            $this->redirect('/admin/products/create');
            return;
        }

        // Generate slug
        $slug = $this->generateSlug($name);

        // Create product
        $productId = $db->insert(
            "INSERT INTO products (name, slug, sku, manufacturer, description, price, sale_price, inventory_count, is_active, featured, is_digital, is_license_product, download_file, download_limit)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$name, $slug, $sku, $manufacturer, $description, $price, $salePrice, $inventory, $isActive, $featured, $isDigital, $isLicenseProduct, $downloadFile, $downloadLimit]
        );

        // Handle categories
        $categoryIds = $this->post('categories', []);
        if (!empty($categoryIds)) {
            foreach ($categoryIds as $catId) {
                $db->insert(
                    "INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)",
                    [$productId, $catId]
                );
            }
        }

        // Log activity
        $this->adminModel->logActivity($this->admin['admin_id'], 'create_product', 'product', $productId, "Created product: $name");

        setFlash('success', 'Product created successfully! Now add options and images.');
        $this->redirect('/admin/products/' . $productId . '/edit');
    }

    /**
     * Edit product
     */
    public function edit(): void
    {
        $id = $_GET['id'] ?? 0;
        $db = Database::getInstance();

        $product = $this->productModel->find($id);
        if (!$product) {
            setFlash('error', 'Product not found');
            $this->redirect('/admin/products');
            return;
        }

        // Get product images with color associations (primary images only - no parent)
        $primaryImages = $db->select(
            "SELECT pi.*
             FROM product_images pi
             WHERE pi.product_id = ? AND pi.parent_image_id IS NULL
             ORDER BY pi.sort_order ASC",
            [$id]
        );

        // Get all sub-images
        $subImages = $db->select(
            "SELECT pi.*
             FROM product_images pi
             WHERE pi.product_id = ? AND pi.parent_image_id IS NOT NULL
             ORDER BY pi.parent_image_id, pi.sort_order ASC",
            [$id]
        );

        // Get all image-to-option-value links from junction table
        $imageOptionLinks = $db->select(
            "SELECT piov.image_id, piov.option_value_id, pov.value_name, po.option_name
             FROM product_image_option_values piov
             JOIN product_option_values pov ON piov.option_value_id = pov.id
             JOIN product_options po ON pov.option_id = po.id
             WHERE piov.image_id IN (SELECT id FROM product_images WHERE product_id = ?)",
            [$id]
        );

        // Group option values by image_id
        $optionValuesByImage = [];
        foreach ($imageOptionLinks as $link) {
            $optionValuesByImage[$link['image_id']][] = [
                'option_value_id' => $link['option_value_id'],
                'value_name' => $link['value_name'],
                'option_name' => $link['option_name']
            ];
        }

        // Group sub-images by parent
        $subImagesByParent = [];
        foreach ($subImages as $sub) {
            $sub['linked_options'] = $optionValuesByImage[$sub['id']] ?? [];
            $subImagesByParent[$sub['parent_image_id']][] = $sub;
        }

        // Attach sub-images and linked options to primary images
        $images = [];
        foreach ($primaryImages as $primary) {
            $primary['sub_images'] = $subImagesByParent[$primary['id']] ?? [];
            $primary['linked_options'] = $optionValuesByImage[$primary['id']] ?? [];
            // Build display name for linked options
            $linkedNames = array_map(fn($o) => $o['value_name'], $primary['linked_options']);
            $primary['linked_options_display'] = implode(', ', $linkedNames);
            $images[] = $primary;
        }

        // Count totals
        $primaryImageCount = count($primaryImages);
        $totalSubImages = count($subImages);

        // Get product options and values
        $options = $this->productModel->getOptions($id);

        // Get variants with their option values
        $variants = $db->select(
            "SELECT pv.*,
                    GROUP_CONCAT(CONCAT(po.option_name, ':', pov.value_name) ORDER BY po.sort_order SEPARATOR ', ') as options_display
             FROM product_variants pv
             LEFT JOIN variant_option_values vov ON pv.id = vov.variant_id
             LEFT JOIN product_option_values pov ON vov.option_value_id = pov.id
             LEFT JOIN product_options po ON pov.option_id = po.id
             WHERE pv.product_id = ?
             GROUP BY pv.id
             ORDER BY pv.id",
            [$id]
        );

        // Get categories
        $allCategories = $db->select("SELECT * FROM categories ORDER BY name");
        $productCategories = $db->select(
            "SELECT category_id FROM product_categories WHERE product_id = ?",
            [$id]
        );
        $productCategoryIds = array_column($productCategories, 'category_id');

        // Get Color/Style/Pattern/Tartan option values for image linking (not Size)
        $allOptions = $db->select(
            "SELECT pov.id, pov.value_name, po.option_name, po.id as option_id, po.sort_order as option_sort
             FROM product_option_values pov
             JOIN product_options po ON pov.option_id = po.id
             WHERE po.product_id = ? AND (
                LOWER(po.option_name) LIKE '%color%' OR
                LOWER(po.option_name) LIKE '%style%' OR
                LOWER(po.option_name) LIKE '%tartan%' OR
                LOWER(po.option_name) LIKE '%pattern%'
             )
             ORDER BY po.sort_order, pov.sort_order",
            [$id]
        );

        // Group options by option_name for display
        $colorOptions = [];
        foreach ($allOptions as $opt) {
            $colorOptions[] = $opt;
        }

        // Get shipping classes and origins for shipping tab
        $shippingClasses = $db->select("SELECT * FROM shipping_classes WHERE is_active = 1 ORDER BY name");
        $shippingOrigins = $db->select("SELECT * FROM shipping_origins WHERE is_active = 1 ORDER BY is_default DESC, name");

        $this->render('admin.products.edit', [
            'title' => 'Edit Product: ' . $product['name'],
            'admin' => $this->admin,
            'product' => $product,
            'images' => $images,
            'primaryImageCount' => $primaryImageCount,
            'totalSubImages' => $totalSubImages,
            'options' => $options,
            'variants' => $variants,
            'categories' => $allCategories,
            'productCategoryIds' => $productCategoryIds,
            'colorOptions' => $colorOptions,
            'shippingClasses' => $shippingClasses,
            'shippingOrigins' => $shippingOrigins
        ], 'admin');
    }

    /**
     * Update product
     */
    public function update(): void
    {
        $this->requireValidCSRF();
        $id = $this->post('id');
        $db = Database::getInstance();

        $product = $this->productModel->find($id);
        if (!$product) {
            setFlash('error', 'Product not found');
            $this->redirect('/admin/products');
            return;
        }

        // Check if this is a shipping-only update
        if ($this->post('update_shipping')) {
            $weightOz = $this->post('weight_oz') !== '' ? floatval($this->post('weight_oz')) : null;
            $lengthIn = $this->post('length_in') !== '' ? floatval($this->post('length_in')) : null;
            $widthIn = $this->post('width_in') !== '' ? floatval($this->post('width_in')) : null;
            $heightIn = $this->post('height_in') !== '' ? floatval($this->post('height_in')) : null;
            $shipsFree = $this->post('ships_free') ? 1 : 0;
            $shipsFreeUs = $this->post('ships_free_us') ? 1 : 0;
            $shippingPrice = $this->post('shipping_price') !== '' ? floatval($this->post('shipping_price')) : null;
            $shippingClassId = $this->post('shipping_class_id') !== '' ? intval($this->post('shipping_class_id')) : null;
            $originId = $this->post('origin_id') !== '' ? intval($this->post('origin_id')) : null;

            $db->update(
                "UPDATE products SET weight_oz = ?, length_in = ?, width_in = ?, height_in = ?,
                 ships_free = ?, ships_free_us = ?, shipping_price = ?, shipping_class_id = ?, origin_id = ?, updated_at = NOW() WHERE id = ?",
                [$weightOz, $lengthIn, $widthIn, $heightIn, $shipsFree, $shipsFreeUs, $shippingPrice, $shippingClassId, $originId, $id]
            );

            $this->adminModel->logActivity($this->admin['admin_id'], 'update_product_shipping', 'product', $id, "Updated shipping for: {$product['name']}");

            setFlash('success', 'Shipping settings updated successfully!');
            $this->redirect('/admin/products/' . $id . '/edit#shipping');
            return;
        }

        $name = trim($this->post('name', ''));
        $sku = trim($this->post('sku', ''));
        $description = trim($this->post('description', ''));
        $metaKeywords = trim($this->post('meta_keywords', ''));
        $metaDescription = trim($this->post('meta_description', ''));
        $price = floatval($this->post('price', 0));
        $salePrice = $this->post('sale_price') ? floatval($this->post('sale_price')) : null;
        $cost = $this->post('cost') !== '' ? floatval($this->post('cost')) : null;
        $costNotApplicable = $this->post('cost_not_applicable') ? 1 : 0;
        $inventory = intval($this->post('inventory_count', 0));
        $processingTime = trim($this->post('processing_time', '')) ?: null;
        $isActive = $this->post('is_active') ? 1 : 0;
        $featured = $this->post('featured') ? 1 : 0;
        $sortOrder = intval($this->post('sort_order', 0));
        $manufacturer = trim($this->post('manufacturer', '')) ?: null;

        // Digital product fields
        $isDigital = $this->post('is_digital') ? 1 : 0;
        $isLicenseProduct = $this->post('is_license_product') ? 1 : 0;
        $downloadFile = $isDigital ? trim($this->post('download_file', '')) ?: null : null;
        $downloadLimit = $isDigital ? intval($this->post('download_limit', 5)) : null;

        // Sanitize and limit SEO fields
        $metaKeywords = substr($metaKeywords, 0, 500);
        $metaDescription = substr($metaDescription, 0, 320);
        // Limit processing time field
        if ($processingTime) {
            $processingTime = substr($processingTime, 0, 100);
        }

        if (empty($name) || $price <= 0) {
            setFlash('error', 'Product name and price are required');
            $this->redirect('/admin/products/' . $id . '/edit');
            return;
        }

        // Update slug only if name changed
        $slug = $product['slug'];
        if ($name !== $product['name']) {
            $slug = $this->generateSlug($name, $id);
        }

        // Store old inventory for back-in-stock notification check
        $oldInventory = (int)$product['inventory_count'];

        $db->update(
            "UPDATE products SET name = ?, slug = ?, sku = ?, manufacturer = ?, description = ?, meta_keywords = ?, meta_description = ?,
             price = ?, sale_price = ?, cost = ?, cost_not_applicable = ?, inventory_count = ?, processing_time = ?, is_active = ?, featured = ?, sort_order = ?,
             is_digital = ?, is_license_product = ?, download_file = ?, download_limit = ?, updated_at = NOW() WHERE id = ?",
            [$name, $slug, $sku, $manufacturer, $description, $metaKeywords, $metaDescription, $price, $salePrice, $cost, $costNotApplicable, $inventory, $processingTime, $isActive, $featured, $sortOrder, $isDigital, $isLicenseProduct, $downloadFile, $downloadLimit, $id]
        );

        // Check and send back-in-stock notifications if inventory was restored
        if ($oldInventory <= 0 && $inventory > 0) {
            $notificationService = new \App\Core\StockNotificationService();
            $notificationService->checkAndNotify($id, null, $inventory);
        }

        // Update categories
        $db->update("DELETE FROM product_categories WHERE product_id = ?", [$id]);
        $categoryIds = $this->post('categories', []);
        if (!empty($categoryIds)) {
            foreach ($categoryIds as $catId) {
                $db->insert(
                    "INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)",
                    [$id, $catId]
                );
            }
        }

        // Recalculate price range
        $this->updatePriceRange($id);

        $this->adminModel->logActivity($this->admin['admin_id'], 'update_product', 'product', $id, "Updated product: $name");

        setFlash('success', 'Product updated successfully!');
        $this->redirect('/admin/products/' . $id . '/edit');
    }

    /**
     * Delete product
     */
    public function delete(): void
    {
        $this->requireValidCSRF();
        $id = $this->post('id');
        $db = Database::getInstance();

        $product = $this->productModel->find($id);
        if (!$product) {
            setFlash('error', 'Product not found');
            $this->redirect('/admin/products');
            return;
        }

        // Delete images from filesystem
        $images = $db->select("SELECT image_path FROM product_images WHERE product_id = ?", [$id]);
        foreach ($images as $image) {
            $filePath = BASE_PATH . '/public' . $image['image_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Delete product (cascades to images, variants, etc.)
        $db->update("DELETE FROM products WHERE id = ?", [$id]);

        $this->adminModel->logActivity($this->admin['admin_id'], 'delete_product', 'product', $id, "Deleted product: {$product['name']}");

        setFlash('success', 'Product deleted successfully');
        $this->redirect('/admin/products');
    }

    /**
     * Add product option (e.g., Color, Size)
     */
    public function addOption(): void
    {
        $this->requireValidCSRF();
        $productId = $this->post('product_id');
        $optionName = trim($this->post('option_name', ''));
        $values = $this->post('values', '');

        if (empty($optionName)) {
            $this->json(['error' => 'Option name is required'], 400);
            return;
        }

        $db = Database::getInstance();

        // Get next sort order
        $maxSort = $db->selectOne(
            "SELECT MAX(sort_order) as max FROM product_options WHERE product_id = ?",
            [$productId]
        );
        $sortOrder = ($maxSort['max'] ?? 0) + 1;

        // Create option
        $optionId = $db->insert(
            "INSERT INTO product_options (product_id, option_name, sort_order) VALUES (?, ?, ?)",
            [$productId, $optionName, $sortOrder]
        );

        // Add values if provided
        $valueList = array_filter(array_map('trim', explode(',', $values)));
        $valueSort = 1;
        foreach ($valueList as $value) {
            $db->insert(
                "INSERT INTO product_option_values (option_id, value_name, sort_order) VALUES (?, ?, ?)",
                [$optionId, $value, $valueSort++]
            );
        }

        $this->adminModel->logActivity($this->admin['admin_id'], 'add_option', 'product', $productId, "Added option: $optionName");

        $this->json(['success' => true, 'option_id' => $optionId]);
    }

    /**
     * Add option value
     */
    public function addOptionValue(): void
    {
        $this->requireValidCSRF();
        $optionId = $this->post('option_id');
        $valueName = trim($this->post('value_name', ''));

        if (empty($valueName)) {
            $this->json(['error' => 'Value name is required'], 400);
            return;
        }

        $db = Database::getInstance();

        $maxSort = $db->selectOne(
            "SELECT MAX(sort_order) as max FROM product_option_values WHERE option_id = ?",
            [$optionId]
        );
        $sortOrder = ($maxSort['max'] ?? 0) + 1;

        $valueId = $db->insert(
            "INSERT INTO product_option_values (option_id, value_name, sort_order) VALUES (?, ?, ?)",
            [$optionId, $valueName, $sortOrder]
        );

        $this->json(['success' => true, 'value_id' => $valueId]);
    }

    /**
     * Delete option
     */
    public function deleteOption(): void
    {
        $this->requireValidCSRF();
        $optionId = $this->post('option_id');

        $db = Database::getInstance();
        $db->update("DELETE FROM product_options WHERE id = ?", [$optionId]);

        $this->json(['success' => true]);
    }

    /**
     * Delete single option value
     */
    public function deleteOptionValue(): void
    {
        $this->requireValidCSRF();
        $valueId = $this->post('value_id');

        $db = Database::getInstance();

        // Delete variant_option_values entries that use this value
        $db->update("DELETE FROM variant_option_values WHERE option_value_id = ?", [$valueId]);

        // Delete the option value itself
        $db->update("DELETE FROM product_option_values WHERE id = ?", [$valueId]);

        $this->json(['success' => true]);
    }

    /**
     * Generate variants from options (preserves existing variant settings)
     */
    public function generateVariants(): void
    {
        $this->requireValidCSRF();
        $productId = $this->post('product_id');

        $db = Database::getInstance();
        $product = $this->productModel->find($productId);

        // Get all options and their values
        $options = $db->select(
            "SELECT * FROM product_options WHERE product_id = ? ORDER BY sort_order",
            [$productId]
        );

        $optionValues = [];
        foreach ($options as $option) {
            $values = $db->select(
                "SELECT * FROM product_option_values WHERE option_id = ? ORDER BY sort_order",
                [$option['id']]
            );
            if (!empty($values)) {
                $optionValues[$option['id']] = $values;
            }
        }

        if (empty($optionValues)) {
            $this->json(['error' => 'No options with values found'], 400);
            return;
        }

        // Generate all combinations
        $combinations = $this->generateCombinations(array_values($optionValues));

        // Get existing variants with their option value keys
        $existingVariants = $db->select(
            "SELECT pv.*, GROUP_CONCAT(vov.option_value_id ORDER BY vov.option_value_id) as value_key
             FROM product_variants pv
             LEFT JOIN variant_option_values vov ON pv.id = vov.variant_id
             WHERE pv.product_id = ?
             GROUP BY pv.id",
            [$productId]
        );

        // Create lookup of existing variants by their option value combination
        $existingByKey = [];
        foreach ($existingVariants as $variant) {
            if ($variant['value_key']) {
                $existingByKey[$variant['value_key']] = $variant;
            }
        }

        // Track which existing variants are still valid
        $validExistingIds = [];
        $created = 0;
        $kept = 0;

        $baseSku = $product['sku'] ?: 'PROD';
        $maxVariantNum = $db->selectOne(
            "SELECT COUNT(*) as cnt FROM product_variants WHERE product_id = ?",
            [$productId]
        )['cnt'] ?? 0;
        $variantNum = $maxVariantNum + 1;

        foreach ($combinations as $combo) {
            // Create key from sorted option value IDs
            $valueIds = array_map(fn($v) => $v['id'], $combo);
            sort($valueIds);
            $comboKey = implode(',', $valueIds);

            if (isset($existingByKey[$comboKey])) {
                // Variant already exists - keep it with its settings
                $validExistingIds[] = $existingByKey[$comboKey]['id'];
                $kept++;
            } else {
                // Create new variant
                $sku = $baseSku . '-' . str_pad($variantNum++, 3, '0', STR_PAD_LEFT);

                $variantId = $db->insert(
                    "INSERT INTO product_variants (product_id, sku, price_adjustment, inventory_count, is_active) VALUES (?, ?, 0, 1, 1)",
                    [$productId, $sku]
                );

                // Link variant to option values
                foreach ($combo as $value) {
                    $db->insert(
                        "INSERT INTO variant_option_values (variant_id, option_value_id) VALUES (?, ?)",
                        [$variantId, $value['id']]
                    );
                }
                $created++;
            }
        }

        // Remove orphaned variants (ones that no longer match any valid combination)
        $orphanedCount = 0;
        foreach ($existingVariants as $variant) {
            if (!in_array($variant['id'], $validExistingIds)) {
                $db->update("DELETE FROM product_variants WHERE id = ?", [$variant['id']]);
                $orphanedCount++;
            }
        }

        $this->updatePriceRange($productId);
        $this->adminModel->logActivity($this->admin['admin_id'], 'generate_variants', 'product', $productId,
            "Generated variants: $created new, $kept kept, $orphanedCount removed");

        $this->json([
            'success' => true,
            'created' => $created,
            'kept' => $kept,
            'removed' => $orphanedCount,
            'total' => $created + $kept
        ]);
    }

    /**
     * Mass update variant prices/costs by option value
     */
    public function massUpdateVariantPrices(): void
    {
        $this->requireValidCSRF();
        $productId = $this->post('product_id');
        $optionValueId = (int)$this->post('option_value_id');

        // Check which fields to update (only update if provided)
        $rawCost = $this->post('cost');
        $rawPrice = $this->post('price_adjustment');

        $updatePrice = $rawPrice !== null && $rawPrice !== '';
        $updateCost = $rawCost !== null && $rawCost !== '';

        $priceAdjustment = $updatePrice ? floatval($this->post('price_adjustment')) : null;
        $cost = $updateCost ? floatval($this->post('cost')) : null;

        $db = Database::getInstance();

        // Get all variants that have this option value
        $variants = $db->select(
            "SELECT pv.id FROM product_variants pv
             JOIN variant_option_values vov ON pv.id = vov.variant_id
             WHERE pv.product_id = ? AND vov.option_value_id = ?",
            [$productId, $optionValueId]
        );

        $updated = 0;
        foreach ($variants as $variant) {
            // Build dynamic update query based on which fields to update
            $setClauses = [];
            $params = [];

            if ($updatePrice) {
                $setClauses[] = "price_adjustment = ?";
                $params[] = $priceAdjustment;
            }
            if ($updateCost) {
                $setClauses[] = "cost = ?";
                $params[] = $cost;
            }

            if (!empty($setClauses)) {
                $params[] = $variant['id'];
                $db->update(
                    "UPDATE product_variants SET " . implode(", ", $setClauses) . " WHERE id = ?",
                    $params
                );
                $updated++;
            }
        }

        if ($updatePrice) {
            $this->updatePriceRange($productId);
        }

        $this->json(['success' => true, 'updated' => $updated]);
    }

    /**
     * Update single variant
     */
    public function updateVariant(): void
    {
        $this->requireValidCSRF();
        $variantId = $this->post('variant_id');
        $sku = trim($this->post('sku', ''));
        $priceAdjustment = floatval($this->post('price_adjustment', 0));
        $cost = $this->post('cost') !== '' && $this->post('cost') !== null ? floatval($this->post('cost')) : null;
        $inventory = intval($this->post('inventory_count', 0));
        $isActive = $this->post('is_active') ? 1 : 0;
        $licenseEdition = $this->post('license_edition') ?: null;

        $db = Database::getInstance();

        // Get product_id and old inventory for back-in-stock notification check
        $variant = $db->selectOne("SELECT product_id, inventory_count FROM product_variants WHERE id = ?", [$variantId]);
        $oldInventory = $variant ? (int)$variant['inventory_count'] : 0;

        $db->update(
            "UPDATE product_variants SET sku = ?, price_adjustment = ?, cost = ?, inventory_count = ?, is_active = ?, license_edition = ? WHERE id = ?",
            [$sku, $priceAdjustment, $cost, $inventory, $isActive, $licenseEdition, $variantId]
        );

        if ($variant) {
            $this->updatePriceRange($variant['product_id']);

            // Check and send back-in-stock notifications if inventory was restored
            if ($oldInventory <= 0 && $inventory > 0) {
                $notificationService = new \App\Core\StockNotificationService();
                $notificationService->checkAndNotify($variant['product_id'], $variantId, $inventory);
            }
        }

        $this->json(['success' => true]);
    }

    /**
     * Upload images (supports both main images and sub-images)
     */
    public function uploadImages(): void
    {
        $this->requireValidCSRF();
        $productId = $this->post('product_id');
        $parentImageId = $this->post('parent_image_id') ?: null; // For sub-images

        if (empty($_FILES['images']['name'][0])) {
            $this->json(['error' => 'No files uploaded'], 400);
            return;
        }

        $db = Database::getInstance();
        $product = $this->productModel->find($productId);

        // Different limits for main images vs sub-images
        if ($parentImageId) {
            // Uploading sub-images - check sub-image count for this parent (max 5)
            $currentCount = $db->selectOne(
                "SELECT COUNT(*) as count FROM product_images WHERE parent_image_id = ?",
                [$parentImageId]
            );
            $maxImages = 5;
            $errorMsg = 'Maximum 5 sub-images allowed per main image';
        } else {
            // Uploading main images - check primary image count (max 40)
            $currentCount = $db->selectOne(
                "SELECT COUNT(*) as count FROM product_images WHERE product_id = ? AND parent_image_id IS NULL",
                [$productId]
            );
            $maxImages = 40;
            $errorMsg = 'Maximum 40 main images allowed per product';
        }

        if ($currentCount['count'] >= $maxImages) {
            $this->json(['error' => $errorMsg], 400);
            return;
        }

        $uploadDir = BASE_PATH . '/public/assets/images/products/' . $product['slug'] . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];
        $allowedVideoTypes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-m4v'];
        $allowedTypes = array_merge($allowedImageTypes, $allowedVideoTypes);
        $maxImageSize = 15 * 1024 * 1024; // 15MB for images
        $maxVideoSize = 50 * 1024 * 1024; // 50MB for videos
        $uploaded = [];
        $failed = [];

        // PHP upload error messages
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server limit (upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Server missing temp folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
        ];

        // Get sort order based on context
        if ($parentImageId) {
            $maxSort = $db->selectOne(
                "SELECT MAX(sort_order) as max FROM product_images WHERE parent_image_id = ?",
                [$parentImageId]
            );
        } else {
            $maxSort = $db->selectOne(
                "SELECT MAX(sort_order) as max FROM product_images WHERE product_id = ? AND parent_image_id IS NULL",
                [$productId]
            );
        }
        $sortOrder = ($maxSort['max'] ?? 0) + 1;

        // Only set is_primary for main images (not sub-images)
        $isPrimary = (!$parentImageId && ($currentCount['count'] ?? 0) == 0) ? 1 : 0;

        // Validate file upload array structure
        if (!isset($_FILES['images']) || !is_array($_FILES['images']['tmp_name'] ?? null)) {
            echo json_encode(['success' => false, 'message' => 'No files uploaded']);
            return;
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            $fileName = $_FILES['images']['name'][$key] ?? 'Unknown file';
            $errorCode = $_FILES['images']['error'][$key] ?? UPLOAD_ERR_NO_FILE;

            // Check for PHP upload errors
            if ($errorCode !== UPLOAD_ERR_OK) {
                $failed[] = $fileName . ': ' . ($uploadErrors[$errorCode] ?? 'Unknown error');
                continue;
            }

            $fileType = mime_content_type($tmpName);
            $fileSize = $_FILES['images']['size'][$key] ?? 0;

            // Check file type
            if (!in_array($fileType, $allowedTypes)) {
                $failed[] = $fileName . ': Invalid file type (' . $fileType . ')';
                continue;
            }

            // Check file size based on type
            $isVideo = in_array($fileType, $allowedVideoTypes);
            $maxSize = $isVideo ? $maxVideoSize : $maxImageSize;

            if ($fileSize > $maxSize) {
                $maxMB = round($maxSize / 1024 / 1024);
                $fileMB = round($fileSize / 1024 / 1024, 1);
                $failed[] = $fileName . ': Too large (' . $fileMB . 'MB, max ' . $maxMB . 'MB)';
                continue;
            }

            // Check max images limit
            if ($currentCount['count'] + count($uploaded) >= $maxImages) {
                $failed[] = $fileName . ': Maximum ' . $maxImages . ' images reached';
                break;
            }

            // Generate unique filename (prefix with 'sub-' for sub-images, 'vid-' for videos)
            if ($isVideo) {
                $prefix = 'vid-';
            } else {
                $prefix = $parentImageId ? 'sub-' : 'img-';
            }
            $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
            $filename = $prefix . uniqid() . '.' . strtolower($ext);
            $filePath = $uploadDir . $filename;
            $webPath = '/assets/images/products/' . $product['slug'] . '/' . $filename;

            if (move_uploaded_file($tmpName, $filePath)) {
                // For images (not videos), resize, optimize, and convert to WebP
                if (!$isVideo) {
                    $webpFilename = $this->processUploadedImage($filePath, $uploadDir, $filename);
                    if ($webpFilename) {
                        $filename = $webpFilename;
                        $webPath = '/assets/images/products/' . $product['slug'] . '/' . $filename;
                    }
                }

                // Videos always go to sort_order 1 (second position after primary)
                $videoSortOrder = $isVideo ? 1 : $sortOrder++;

                $imageId = $db->insert(
                    "INSERT INTO product_images (product_id, parent_image_id, image_path, is_video, is_primary, sort_order) VALUES (?, ?, ?, ?, ?, ?)",
                    [$productId, $parentImageId, $webPath, $isVideo ? 1 : 0, $isPrimary, $videoSortOrder]
                );

                // If video was added, shift other non-primary images down
                if ($isVideo && !$parentImageId) {
                    $db->update(
                        "UPDATE product_images SET sort_order = sort_order + 1
                         WHERE product_id = ? AND id != ? AND is_primary = 0 AND parent_image_id IS NULL AND is_video = 0",
                        [$productId, $imageId]
                    );
                }

                $uploaded[] = [
                    'id' => $imageId,
                    'path' => $webPath
                ];

                $isPrimary = 0; // Only first image is primary
            }
        }

        $imageType = $parentImageId ? 'sub-images' : 'main images';
        $this->adminModel->logActivity($this->admin['admin_id'], 'upload_images', 'product', $productId, "Uploaded " . count($uploaded) . " $imageType");

        $response = [
            'success' => count($uploaded) > 0 || count($failed) === 0,
            'uploaded' => $uploaded,
            'uploadedCount' => count($uploaded),
            'failedCount' => count($failed)
        ];

        if (!empty($failed)) {
            $response['failed'] = $failed;
            $response['message'] = count($uploaded) > 0
                ? count($uploaded) . ' uploaded, ' . count($failed) . ' failed'
                : 'All uploads failed';
        }

        $this->json($response);
    }

    /**
     * Update image (set primary, link to color, reorder)
     */
    public function updateImage(): void
    {
        $this->requireValidCSRF();
        $imageId = $this->post('image_id');
        $action = $this->post('action');

        $db = Database::getInstance();
        $image = $db->selectOne("SELECT * FROM product_images WHERE id = ?", [$imageId]);

        if (!$image) {
            $this->json(['error' => 'Image not found'], 404);
            return;
        }

        switch ($action) {
            case 'set_primary':
                // Unset all other primary images
                $db->update(
                    "UPDATE product_images SET is_primary = 0 WHERE product_id = ?",
                    [$image['product_id']]
                );
                $db->update("UPDATE product_images SET is_primary = 1 WHERE id = ?", [$imageId]);
                break;

            case 'link_color':
                // Support multiple option values via junction table
                $optionValueIds = $this->post('option_value_ids', '');

                // Clear existing links for this image
                $db->update(
                    "DELETE FROM product_image_option_values WHERE image_id = ?",
                    [$imageId]
                );

                // Add new links
                if (!empty($optionValueIds)) {
                    $ids = array_filter(array_map('intval', explode(',', $optionValueIds)));
                    foreach ($ids as $valueId) {
                        $db->insert(
                            "INSERT IGNORE INTO product_image_option_values (image_id, option_value_id) VALUES (?, ?)",
                            [$imageId, $valueId]
                        );
                    }
                }

                // Also update the legacy option_value_id field for backward compatibility
                $firstValueId = !empty($ids) ? $ids[0] : null;
                $db->update(
                    "UPDATE product_images SET option_value_id = ? WHERE id = ?",
                    [$firstValueId, $imageId]
                );
                break;

            case 'update_alt':
                $altText = trim($this->post('alt_text', ''));
                $db->update(
                    "UPDATE product_images SET alt_text = ? WHERE id = ?",
                    [$altText, $imageId]
                );
                break;

            case 'reorder':
                $newOrder = intval($this->post('sort_order', 0));
                $db->update(
                    "UPDATE product_images SET sort_order = ? WHERE id = ?",
                    [$newOrder, $imageId]
                );
                break;
        }

        $this->json(['success' => true]);
    }

    /**
     * Delete image
     */
    public function deleteImage(): void
    {
        $this->requireValidCSRF();
        $imageId = $this->post('image_id');

        $db = Database::getInstance();
        $image = $db->selectOne("SELECT * FROM product_images WHERE id = ?", [$imageId]);

        if (!$image) {
            $this->json(['error' => 'Image not found'], 404);
            return;
        }

        // Delete file
        $filePath = BASE_PATH . '/public' . $image['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete from database
        $db->update("DELETE FROM product_images WHERE id = ?", [$imageId]);

        // If this was primary, set next image as primary
        if ($image['is_primary']) {
            $nextImage = $db->selectOne(
                "SELECT id FROM product_images WHERE product_id = ? ORDER BY sort_order LIMIT 1",
                [$image['product_id']]
            );
            if ($nextImage) {
                $db->update("UPDATE product_images SET is_primary = 1 WHERE id = ?", [$nextImage['id']]);
            }
        }

        $this->json(['success' => true]);
    }

    /**
     * Delete multiple images at once
     */
    public function deleteImages(): void
    {
        $this->requireValidCSRF();
        $imageIds = $this->post('image_ids', '');

        if (empty($imageIds)) {
            $this->json(['error' => 'No images selected'], 400);
            return;
        }

        $ids = array_filter(array_map('intval', explode(',', $imageIds)));

        if (empty($ids)) {
            $this->json(['error' => 'Invalid image IDs'], 400);
            return;
        }

        $db = Database::getInstance();
        $productId = null;
        $deletedCount = 0;

        foreach ($ids as $imageId) {
            $image = $db->selectOne("SELECT * FROM product_images WHERE id = ?", [$imageId]);

            if (!$image) continue;

            $productId = $image['product_id'];

            // Delete file
            $filePath = BASE_PATH . '/public' . $image['image_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete from database (cascade will handle sub-images)
            $db->update("DELETE FROM product_images WHERE id = ?", [$imageId]);
            $deletedCount++;
        }

        // If we deleted any images, ensure there's still a primary image
        if ($productId && $deletedCount > 0) {
            $hasPrimary = $db->selectOne(
                "SELECT id FROM product_images WHERE product_id = ? AND is_primary = 1 AND parent_image_id IS NULL",
                [$productId]
            );

            if (!$hasPrimary) {
                $nextImage = $db->selectOne(
                    "SELECT id FROM product_images WHERE product_id = ? AND parent_image_id IS NULL ORDER BY sort_order LIMIT 1",
                    [$productId]
                );
                if ($nextImage) {
                    $db->update("UPDATE product_images SET is_primary = 1 WHERE id = ?", [$nextImage['id']]);
                }
            }
        }

        $this->adminModel->logActivity($this->admin['admin_id'], 'delete_images', 'product', $productId, "Deleted $deletedCount images");

        $this->json(['success' => true, 'deleted' => $deletedCount]);
    }

    /**
     * Reorder images (drag and drop)
     */
    public function reorderImages(): void
    {
        $this->requireValidCSRF();
        $productId = $this->post('product_id');
        $imageIds = $this->post('image_ids', '');

        if (empty($imageIds)) {
            $this->json(['error' => 'No images provided'], 400);
            return;
        }

        $db = Database::getInstance();
        $ids = explode(',', $imageIds);

        // Update sort order for each image
        foreach ($ids as $index => $imageId) {
            $imageId = intval($imageId);
            $isPrimary = ($index === 0) ? 1 : 0;

            $db->update(
                "UPDATE product_images SET sort_order = ?, is_primary = ? WHERE id = ? AND product_id = ?",
                [$index, $isPrimary, $imageId, $productId]
            );
        }

        $this->json(['success' => true]);
    }

    /**
     * Move images to be sub-images of another image
     */
    public function moveImagesToSub(): void
    {
        $this->requireValidCSRF();
        $productId = $this->post('product_id');
        $parentImageId = $this->post('parent_image_id');
        $imageIds = $this->post('image_ids', '');

        if (empty($imageIds) || empty($parentImageId)) {
            $this->json(['error' => 'Missing required parameters'], 400);
            return;
        }

        $db = Database::getInstance();
        $ids = array_filter(array_map('intval', explode(',', $imageIds)));

        if (empty($ids)) {
            $this->json(['error' => 'No valid image IDs'], 400);
            return;
        }

        // Verify parent image exists and belongs to this product
        $parentImage = $db->selectOne(
            "SELECT id, parent_image_id FROM product_images WHERE id = ? AND product_id = ?",
            [$parentImageId, $productId]
        );

        if (!$parentImage) {
            $this->json(['error' => 'Parent image not found'], 404);
            return;
        }

        // Parent must be a main image (not already a sub-image)
        if ($parentImage['parent_image_id'] !== null) {
            $this->json(['error' => 'Cannot move images under a sub-image. Select a main image as parent.'], 400);
            return;
        }

        // Check how many sub-images the parent already has
        $currentSubCount = $db->selectOne(
            "SELECT COUNT(*) as count FROM product_images WHERE parent_image_id = ?",
            [$parentImageId]
        );
        $availableSlots = 5 - ($currentSubCount['count'] ?? 0);

        if (count($ids) > $availableSlots) {
            $this->json(['error' => "Parent can only accept $availableSlots more sub-images (max 5)"], 400);
            return;
        }

        // Get max sort order for existing sub-images
        $maxSort = $db->selectOne(
            "SELECT MAX(sort_order) as max FROM product_images WHERE parent_image_id = ?",
            [$parentImageId]
        );
        $sortOrder = ($maxSort['max'] ?? 0) + 1;

        $movedCount = 0;
        $wasPrimary = false;

        foreach ($ids as $imageId) {
            // Don't move the parent to itself
            if ($imageId == $parentImageId) {
                continue;
            }

            // Verify image belongs to this product
            $image = $db->selectOne(
                "SELECT id, is_primary, parent_image_id FROM product_images WHERE id = ? AND product_id = ?",
                [$imageId, $productId]
            );

            if (!$image) {
                continue;
            }

            // Track if we're moving the primary image
            if ($image['is_primary']) {
                $wasPrimary = true;
            }

            // If this was a main image with sub-images, orphan those sub-images (make them main images)
            if ($image['parent_image_id'] === null) {
                $db->update(
                    "UPDATE product_images SET parent_image_id = NULL WHERE parent_image_id = ?",
                    [$imageId]
                );
            }

            // Move the image to be a sub-image
            $db->update(
                "UPDATE product_images SET parent_image_id = ?, is_primary = 0, sort_order = ? WHERE id = ?",
                [$parentImageId, $sortOrder++, $imageId]
            );
            $movedCount++;
        }

        // If we moved the primary image, set a new primary
        if ($wasPrimary) {
            $newPrimary = $db->selectOne(
                "SELECT id FROM product_images WHERE product_id = ? AND parent_image_id IS NULL ORDER BY sort_order LIMIT 1",
                [$productId]
            );
            if ($newPrimary) {
                $db->update("UPDATE product_images SET is_primary = 1 WHERE id = ?", [$newPrimary['id']]);
            }
        }

        $this->adminModel->logActivity($this->admin['admin_id'], 'move_images_to_sub', 'product', $productId, "Moved $movedCount images to sub-images");

        $this->json(['success' => true, 'moved' => $movedCount]);
    }

    /**
     * Get featured products for ordering
     */
    public function featured(): void
    {
        $db = Database::getInstance();

        $products = $db->select(
            "SELECT p.id, p.name, p.featured_order,
                    (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as image
             FROM products p
             WHERE p.featured = 1 AND p.is_active = 1
             ORDER BY p.featured_order ASC, p.id DESC"
        );

        $this->render('admin.products.featured', [
            'title' => 'Featured Products Order',
            'admin' => $this->admin,
            'products' => $products
        ], 'admin');
    }

    /**
     * Reorder featured products (AJAX)
     */
    public function reorderFeatured(): void
    {
        $this->requireValidCSRF();

        $ids = $this->post('ids', '');
        $ids = array_filter(explode(',', $ids));

        if (empty($ids)) {
            $this->json(['success' => false, 'error' => 'No IDs provided']);
            return;
        }

        $db = Database::getInstance();

        foreach ($ids as $order => $id) {
            $db->update(
                "UPDATE products SET featured_order = ? WHERE id = ?",
                [$order, (int)$id]
            );
        }

        $this->json(['success' => true]);
    }

    /**
     * Remove a single product from Featured (AJAX)
     */
    public function removeFeatured(): void
    {
        $this->requireValidCSRF();

        $id = (int) $this->post('id', 0);

        if (!$id) {
            $this->json(['success' => false, 'error' => 'No product ID provided']);
            return;
        }

        $db = Database::getInstance();

        $db->update(
            "UPDATE products SET featured = 0, featured_order = 0 WHERE id = ?",
            [$id]
        );

        $this->adminModel->logActivity($this->admin['admin_id'], 'remove_featured', 'products', $id, "Removed product from featured");

        $this->json(['success' => true]);
    }

    /**
     * Reorder products in admin list (AJAX)
     */
    public function reorderProducts(): void
    {
        $this->requireValidCSRF();

        $ids = $this->post('ids', '');
        $ids = array_filter(explode(',', $ids));
        $categoryId = (int)$this->post('category_id', 0);

        if (empty($ids)) {
            $this->json(['success' => false, 'error' => 'No IDs provided']);
            return;
        }

        $db = Database::getInstance();

        if ($categoryId > 0) {
            // Category-specific ordering
            foreach ($ids as $order => $id) {
                $db->update(
                    "UPDATE product_categories SET sort_order = ? WHERE product_id = ? AND category_id = ?",
                    [$order, (int)$id, $categoryId]
                );
            }
            $this->adminModel->logActivity($this->admin['admin_id'], 'reorder_products', 'category', $categoryId, "Reordered " . count($ids) . " products in category");
        } else {
            // Global ordering (no category filter)
            foreach ($ids as $order => $id) {
                $db->update(
                    "UPDATE products SET sort_order = ? WHERE id = ?",
                    [$order, (int)$id]
                );
            }
            $this->adminModel->logActivity($this->admin['admin_id'], 'reorder_products', 'products', 0, "Reordered " . count($ids) . " products");
        }

        $this->json(['success' => true]);
    }

    /**
     * Bulk action on products (feature, unfeature, delete)
     */
    public function bulkAction(): void
    {
        $this->requireValidCSRF();

        $action = $this->post('action', '');
        $ids = $this->post('ids', '');
        $ids = array_filter(explode(',', $ids), 'is_numeric');

        if (empty($action) || empty($ids)) {
            $this->json(['success' => false, 'error' => 'Invalid request']);
            return;
        }

        $db = Database::getInstance();
        $count = count($ids);

        switch ($action) {
            case 'feature':
                // Get max featured_order
                $maxOrder = $db->selectOne("SELECT MAX(featured_order) as max FROM products WHERE featured = 1");
                $order = ($maxOrder['max'] ?? 0) + 1;

                foreach ($ids as $id) {
                    $db->update(
                        "UPDATE products SET featured = 1, featured_order = ? WHERE id = ?",
                        [$order++, (int)$id]
                    );
                }
                $this->adminModel->logActivity($this->admin['admin_id'], 'bulk_feature', 'products', 0, "Featured $count products");
                break;

            case 'unfeature':
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $db->update(
                    "UPDATE products SET featured = 0, featured_order = 0 WHERE id IN ($placeholders)",
                    array_map('intval', $ids)
                );
                $this->adminModel->logActivity($this->admin['admin_id'], 'bulk_unfeature', 'products', 0, "Unfeatured $count products");
                break;

            case 'activate':
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $db->update(
                    "UPDATE products SET is_active = 1 WHERE id IN ($placeholders)",
                    array_map('intval', $ids)
                );
                $this->adminModel->logActivity($this->admin['admin_id'], 'bulk_activate', 'products', 0, "Activated $count products");
                break;

            case 'deactivate':
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $db->update(
                    "UPDATE products SET is_active = 0 WHERE id IN ($placeholders)",
                    array_map('intval', $ids)
                );
                $this->adminModel->logActivity($this->admin['admin_id'], 'bulk_deactivate', 'products', 0, "Deactivated $count products");
                break;

            case 'disable':
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $db->update(
                    "UPDATE products SET disabled = 1, is_active = 0 WHERE id IN ($placeholders)",
                    array_map('intval', $ids)
                );
                $this->adminModel->logActivity($this->admin['admin_id'], 'bulk_disable', 'products', 0, "Disabled $count products");
                break;

            case 'enable':
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $db->update(
                    "UPDATE products SET disabled = 0 WHERE id IN ($placeholders)",
                    array_map('intval', $ids)
                );
                $this->adminModel->logActivity($this->admin['admin_id'], 'bulk_enable', 'products', 0, "Enabled $count products");
                break;

            case 'delete':
                foreach ($ids as $id) {
                    $product = $this->productModel->find((int)$id);
                    if ($product) {
                        // Delete product images from filesystem
                        $uploadDir = BASE_PATH . '/public/assets/images/products/' . $product['slug'] . '/';
                        if (is_dir($uploadDir)) {
                            $files = glob($uploadDir . '*');
                            foreach ($files as $file) {
                                if (is_file($file)) {
                                    unlink($file);
                                }
                            }
                            rmdir($uploadDir);
                        }

                        // Delete product from database (cascade will handle related records)
                        $db->update("DELETE FROM products WHERE id = ?", [(int)$id]);
                    }
                }
                $this->adminModel->logActivity($this->admin['admin_id'], 'bulk_delete', 'products', 0, "Deleted $count products");
                break;

            default:
                $this->json(['success' => false, 'error' => 'Unknown action']);
                return;
        }

        $this->json(['success' => true, 'count' => $count]);
    }

    /**
     * Generate slug from name
     */
    private function generateSlug(string $name, ?int $excludeId = null): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        $db = Database::getInstance();
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = "SELECT id FROM products WHERE slug = ?";
            $params = [$slug];

            if ($excludeId) {
                $query .= " AND id != ?";
                $params[] = $excludeId;
            }

            $existing = $db->selectOne($query, $params);
            if (!$existing) {
                break;
            }

            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Generate all combinations of option values
     */
    private function generateCombinations(array $arrays, int $i = 0): array
    {
        if (!isset($arrays[$i])) {
            return [[]];
        }

        $result = [];
        $tmp = $this->generateCombinations($arrays, $i + 1);

        foreach ($arrays[$i] as $v) {
            foreach ($tmp as $t) {
                $result[] = array_merge([$v], $t);
            }
        }

        return $result;
    }

    /**
     * Update price range for product based on variants
     */
    private function updatePriceRange(int $productId): void
    {
        $db = Database::getInstance();
        $product = $this->productModel->find($productId);

        $basePrice = $product['sale_price'] ?: $product['price'];

        // Get min/max price adjustments from active variants
        $priceRange = $db->selectOne(
            "SELECT MIN(price_adjustment) as min_adj, MAX(price_adjustment) as max_adj
             FROM product_variants WHERE product_id = ? AND is_active = 1",
            [$productId]
        );

        if ($priceRange && $priceRange['min_adj'] !== null) {
            $minPrice = $basePrice + $priceRange['min_adj'];
            $maxPrice = $basePrice + $priceRange['max_adj'];
        } else {
            $minPrice = $basePrice;
            $maxPrice = $basePrice;
        }

        $db->update(
            "UPDATE products SET price_min = ?, price_max = ? WHERE id = ?",
            [$minPrice, $maxPrice, $productId]
        );
    }

    /**
     * Process uploaded image: resize full-size image and create thumbnail
     * Full-size: max 1200x1200 pixels (maintains aspect ratio)
     * Thumbnail: 200x200 pixels (for product cards)
     */
    private function processUploadedImage(string $filePath, string $uploadDir, string $filename): ?string
    {
        // Get image info
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            return null;
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // Max dimensions
        $maxFullSize = 1200;
        $thumbSize = 200;

        // Load source image based on type
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($filePath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($filePath);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($filePath);
                break;
            default:
                // For AVIF and other formats, skip processing
                return null;
        }

        if (!$source) {
            return null;
        }

        // Resize full-size image if needed
        if ($width > $maxFullSize || $height > $maxFullSize) {
            $ratio = min($maxFullSize / $width, $maxFullSize / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency for PNG/GIF/WebP
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);

            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $resized;

            // Update dimensions for thumbnail creation
            $width = $newWidth;
            $height = $newHeight;
        }

        // Generate WebP filename
        $pathInfo = pathinfo($filename);
        $webpFilename = $pathInfo['filename'] . '.webp';
        $webpPath = $uploadDir . $webpFilename;

        // Save main image as WebP (quality 85)
        imagealphablending($source, true);
        imagesavealpha($source, true);
        imagewebp($source, $webpPath, 85);

        // Create thumbnail with square crop (centered)
        $thumbWidth = $thumbSize;
        $thumbHeight = $thumbSize;

        // Calculate crop area (center crop to square)
        $size = min($width, $height);
        $srcX = ($width - $size) / 2;
        $srcY = ($height - $size) / 2;

        $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefilledrectangle($thumbnail, 0, 0, $thumbWidth, $thumbHeight, $transparent);

        imagecopyresampled($thumbnail, $source, 0, 0, (int)$srcX, (int)$srcY, $thumbWidth, $thumbHeight, $size, $size);

        // Save thumbnail as WebP
        $thumbWebpFilename = 'thumb-' . $pathInfo['filename'] . '.webp';
        $thumbWebpPath = $uploadDir . $thumbWebpFilename;
        imagewebp($thumbnail, $thumbWebpPath, 85);

        imagedestroy($source);
        imagedestroy($thumbnail);

        // Delete original file if it's not already WebP
        if ($mimeType !== 'image/webp' && file_exists($filePath)) {
            unlink($filePath);
        }

        return $webpFilename;
    }

    /**
     * Get product statistics (AJAX)
     */
    public function stats(): void
    {
        $id = $_GET['id'] ?? 0;

        if (!$id) {
            $this->json(['error' => 'Product ID required'], 400);
            return;
        }

        $db = Database::getInstance();

        // Get product basic info
        $product = $this->productModel->find($id);
        if (!$product) {
            $this->json(['error' => 'Product not found'], 404);
            return;
        }

        // Get page views from visitors table (exclude bots)
        $pageViews = $db->selectOne(
            "SELECT COUNT(*) as views FROM visitors
             WHERE page_url LIKE ? AND is_bot = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            ['/products/' . $product['slug'] . '%']
        );
        $views30Days = $pageViews ? (int)$pageViews['views'] : 0;

        $allTimeViews = $db->selectOne(
            "SELECT COUNT(*) as views FROM visitors
             WHERE page_url LIKE ? AND is_bot = 0",
            ['/products/' . $product['slug'] . '%']
        );
        $viewsAllTime = $allTimeViews ? (int)$allTimeViews['views'] : 0;

        // Get sales data from order_items
        $salesData = $db->selectOne(
            "SELECT
                COUNT(DISTINCT oi.order_id) as order_count,
                SUM(oi.quantity) as units_sold,
                SUM(oi.quantity * oi.price) as revenue
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             WHERE oi.product_id = ? AND o.status NOT IN ('cancelled', 'refunded')",
            [$id]
        );

        $unitsSold = $salesData ? (int)$salesData['units_sold'] : 0;
        $revenue = $salesData ? (float)$salesData['revenue'] : 0;
        $orderCount = $salesData ? (int)$salesData['order_count'] : 0;

        // Calculate conversion rate (orders / views * 100)
        $conversionRate = $viewsAllTime > 0 ? round(($orderCount / $viewsAllTime) * 100, 2) : 0;

        // Get last 30 days sales
        $recentSales = $db->selectOne(
            "SELECT
                SUM(oi.quantity) as units_sold,
                SUM(oi.quantity * oi.price) as revenue
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             WHERE oi.product_id = ?
               AND o.status NOT IN ('cancelled', 'refunded')
               AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            [$id]
        );

        $unitsSold30Days = $recentSales ? (int)$recentSales['units_sold'] : 0;
        $revenue30Days = $recentSales ? (float)$recentSales['revenue'] : 0;

        $this->json([
            'success' => true,
            'product' => [
                'id' => $id,
                'name' => $product['name']
            ],
            'stats' => [
                'views_30_days' => $views30Days,
                'views_all_time' => $viewsAllTime,
                'units_sold' => $unitsSold,
                'units_sold_30_days' => $unitsSold30Days,
                'revenue' => $revenue,
                'revenue_30_days' => $revenue30Days,
                'order_count' => $orderCount,
                'conversion_rate' => $conversionRate
            ]
        ]);
    }

    /**
     * Load an image from file
     */
    private function loadImage(string $path, string $mimeType)
    {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/webp':
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }

    /**
     * Save an image to file
     */
    private function saveImage($image, string $path, string $mimeType): bool
    {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagejpeg($image, $path, 85); // 85% quality
            case 'image/png':
                return imagepng($image, $path, 6); // Compression level 6
            case 'image/gif':
                return imagegif($image, $path);
            case 'image/webp':
                return imagewebp($image, $path, 85);
            default:
                return false;
        }
    }
}
