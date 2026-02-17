<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Models\AdminUser;

class CategoryController extends Controller
{
    private AdminUser $adminModel;
    private ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminUser();
        $this->requireAdmin();
    }

    protected function requireAdmin(): void
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        $token = $_COOKIE['admin_token'] ?? null;
        if (!$token) {
            if ($isAjax) {
                $this->json(['error' => 'Not authenticated'], 401);
                exit;
            }
            $this->redirect('/admin/login');
            exit;
        }

        $session = $this->adminModel->validateSession($token);
        if (!$session) {
            setcookie('admin_token', '', ['expires' => time() - 3600, 'path' => '/admin']);
            if ($isAjax) {
                $this->json(['error' => 'Session expired'], 401);
                exit;
            }
            $this->redirect('/admin/login');
            exit;
        }

        $this->admin = $session;
    }

    /**
     * List all categories
     */
    public function index(): void
    {
        $db = Database::getInstance();

        // Get all categories with product count
        $allCategories = $db->select(
            "SELECT c.*,
                    p.name as parent_name,
                    (SELECT COUNT(*) FROM product_categories pc WHERE pc.category_id = c.id) as product_count
             FROM categories c
             LEFT JOIN categories p ON c.parent_id = p.id
             ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NOT NULL, c.sort_order ASC, c.name ASC"
        );

        // Organize into hierarchy for display (supports 3+ levels)
        $categoriesById = [];
        $children = [];

        foreach ($allCategories as $cat) {
            $categoriesById[$cat['id']] = $cat;
            if ($cat['parent_id']) {
                $children[$cat['parent_id']][] = $cat;
            }
        }

        // Recursive function to add categories with their children
        $addWithChildren = function($cat, $level) use (&$addWithChildren, &$children, &$hierarchicalCategories) {
            $cat['indent_level'] = $level;
            $cat['is_child'] = $level > 0;
            $hierarchicalCategories[] = $cat;

            if (isset($children[$cat['id']])) {
                foreach ($children[$cat['id']] as $child) {
                    $addWithChildren($child, $level + 1);
                }
            }
        };

        // Build hierarchical list starting from top-level categories
        $hierarchicalCategories = [];
        foreach ($allCategories as $cat) {
            if (!$cat['parent_id']) {
                $addWithChildren($cat, 0);
            }
        }

        // Get ALL categories for parent dropdown (allows setting any category as parent)
        $parentCategories = $db->select(
            "SELECT c.id, c.name, c.parent_id, p.name as parent_name
             FROM categories c
             LEFT JOIN categories p ON c.parent_id = p.id
             ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NOT NULL, c.sort_order ASC, c.name ASC"
        );

        $this->render('admin.categories.index', [
            'title' => 'Categories',
            'admin' => $this->admin,
            'categories' => $hierarchicalCategories,
            'parentCategories' => $parentCategories
        ], 'admin');
    }

    /**
     * Store new category
     */
    public function store(): void
    {
        $this->requireValidCSRF();

        $name = trim($this->post('name', ''));
        $slug = trim($this->post('slug', ''));
        $parentId = $this->post('parent_id') ?: null;
        $showSubcategoryGrid = $this->post('show_subcategory_grid') ? 1 : 0;

        if (empty($name)) {
            setFlash('error', 'Category name is required.');
            $this->redirect('/admin/categories');
            return;
        }

        // Generate slug if empty
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
            $slug = trim($slug, '-');
        }

        $db = Database::getInstance();

        // Check for duplicate slug
        $existing = $db->selectOne("SELECT id FROM categories WHERE slug = ?", [$slug]);
        if ($existing) {
            $slug .= '-' . time();
        }

        // Get max sort order
        $maxOrder = $db->selectOne("SELECT MAX(sort_order) as max_order FROM categories WHERE parent_id IS NULL OR parent_id = ?", [$parentId]);
        $sortOrder = ($maxOrder['max_order'] ?? 0) + 1;

        // Handle image upload
        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $imagePath = $this->uploadCategoryImage($_FILES['image']);
        }

        $db->insert(
            "INSERT INTO categories (name, slug, parent_id, sort_order, image, show_subcategory_grid, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [$name, $slug, $parentId, $sortOrder, $imagePath, $showSubcategoryGrid]
        );

        setFlash('success', 'Category created successfully.');
        $this->redirect('/admin/categories');
    }

    /**
     * Update category
     */
    public function update(): void
    {
        $this->requireValidCSRF();

        $id = (int)$this->post('id');
        $name = trim($this->post('name', ''));
        $slug = trim($this->post('slug', ''));
        $parentId = $this->post('parent_id') ?: null;
        $showSubcategoryGrid = $this->post('show_subcategory_grid') ? 1 : 0;
        $removeImage = $this->post('remove_image') ? true : false;

        if (empty($name)) {
            setFlash('error', 'Category name is required.');
            $this->redirect('/admin/categories');
            return;
        }

        // Prevent setting self as parent
        if ($parentId == $id) {
            setFlash('error', 'A category cannot be its own parent.');
            $this->redirect('/admin/categories');
            return;
        }

        $db = Database::getInstance();

        // Get current category data
        $current = $db->selectOne("SELECT image FROM categories WHERE id = ?", [$id]);

        // Check for duplicate slug (excluding current)
        $existing = $db->selectOne("SELECT id FROM categories WHERE slug = ? AND id != ?", [$slug, $id]);
        if ($existing) {
            $slug .= '-' . time();
        }

        // Prevent circular reference - check if parent_id would create a loop
        if ($parentId) {
            $checkParent = $db->selectOne("SELECT parent_id FROM categories WHERE id = ?", [$parentId]);
            if ($checkParent && $checkParent['parent_id'] == $id) {
                setFlash('error', 'Cannot set a subcategory as parent (circular reference).');
                $this->redirect('/admin/categories');
                return;
            }
        }

        // Handle image
        $imagePath = $current['image'] ?? null;

        // Remove image if requested
        if ($removeImage && $imagePath) {
            $fullPath = PUBLIC_PATH . $imagePath;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            $imagePath = null;
        }

        // Handle new image upload
        if (!empty($_FILES['image']['name'])) {
            // Delete old image if exists
            if ($imagePath) {
                $fullPath = PUBLIC_PATH . $imagePath;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
            $imagePath = $this->uploadCategoryImage($_FILES['image']);
        }

        $db->update(
            "UPDATE categories SET name = ?, slug = ?, parent_id = ?, image = ?, show_subcategory_grid = ? WHERE id = ?",
            [$name, $slug, $parentId, $imagePath, $showSubcategoryGrid, $id]
        );

        setFlash('success', 'Category updated successfully.');
        $this->redirect('/admin/categories');
    }

    /**
     * Delete category
     */
    public function delete(): void
    {
        $this->requireValidCSRF();

        $id = (int)$this->post('id');

        $db = Database::getInstance();

        // Check if category has products
        $productCount = $db->selectOne(
            "SELECT COUNT(*) as count FROM product_categories WHERE category_id = ?",
            [$id]
        );

        if ($productCount['count'] > 0) {
            setFlash('error', 'Cannot delete category with products. Remove products first.');
            $this->redirect('/admin/categories');
            return;
        }

        $db->update("DELETE FROM categories WHERE id = ?", [$id]);

        setFlash('success', 'Category deleted successfully.');
        $this->redirect('/admin/categories');
    }

    /**
     * Reorder categories (AJAX)
     */
    public function reorder(): void
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
                "UPDATE categories SET sort_order = ? WHERE id = ?",
                [$order, (int)$id]
            );
        }

        $this->json(['success' => true]);
    }

    /**
     * Upload category image
     */
    private function uploadCategoryImage(array $file): ?string
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        if ($file['size'] > $maxSize) {
            setFlash('error', 'Image too large. Maximum size: 5MB');
            return null;
        }

        // Validate actual file content type (not client-provided type)
        $actualType = mime_content_type($file['tmp_name']);
        if (!in_array($actualType, $allowedTypes)) {
            setFlash('error', 'Invalid image type. Allowed: JPG, PNG, GIF, WebP');
            return null;
        }

        // Validate extension matches content
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            setFlash('error', 'Invalid file extension');
            return null;
        }

        // Create categories upload directory if it doesn't exist
        $uploadDir = PUBLIC_PATH . '/assets/images/categories/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename with validated extension
        $filename = 'category-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return '/assets/images/categories/' . $filename;
        }

        return null;
    }
}
