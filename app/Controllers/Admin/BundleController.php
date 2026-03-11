<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminUser;
use App\Models\Bundle;
use App\Models\Setting;

class BundleController extends Controller
{
    private AdminUser $adminModel;
    private Bundle $bundleModel;
    protected ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminUser();
        $this->bundleModel = new Bundle();
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
     * List all bundles
     */
    public function index(): void
    {
        $bundles = $this->bundleModel->getAll();

        $this->render('admin.bundles.index', [
            'title' => 'Product Bundles',
            'admin' => $this->admin,
            'bundles' => $bundles
        ], 'admin');
    }

    /**
     * Show create bundle form
     */
    public function create(): void
    {
        $this->render('admin.bundles.edit', [
            'title' => 'Create Bundle',
            'admin' => $this->admin,
            'bundle' => null,
            'bundleProducts' => []
        ], 'admin');
    }

    /**
     * Show edit bundle form
     */
    public function edit(): void
    {
        $id = (int)$this->get('id');
        $bundle = $this->bundleModel->getById($id);

        if (!$bundle) {
            setFlash('error', 'Bundle not found');
            $this->redirect('/admin/bundles');
            return;
        }

        $this->render('admin.bundles.edit', [
            'title' => 'Edit Bundle: ' . $bundle['name'],
            'admin' => $this->admin,
            'bundle' => $bundle,
            'bundleProducts' => $bundle['products'] ?? []
        ], 'admin');
    }

    /**
     * Store new bundle
     */
    public function store(): void
    {
        $this->requireValidCSRF();

        $data = [
            'name' => trim($this->post('name', '')),
            'description' => trim($this->post('description', '')),
            'discount_type' => $this->post('discount_type', 'percentage'),
            'discount_value' => floatval($this->post('discount_value', 0)),
            'is_active' => $this->post('is_active') ? 1 : 0,
            'expires_at' => $this->post('expires_at') ?: null
        ];

        $productIds = $this->post('product_ids', []);

        if (empty($data['name'])) {
            setFlash('error', 'Bundle name is required');
            $this->redirect('/admin/bundles/create');
            return;
        }

        if ($data['discount_value'] <= 0) {
            setFlash('error', 'Discount value must be greater than 0');
            $this->redirect('/admin/bundles/create');
            return;
        }

        if (count($productIds) < 2) {
            setFlash('error', 'A bundle must contain at least 2 products');
            $this->redirect('/admin/bundles/create');
            return;
        }

        try {
            $id = $this->bundleModel->createBundle($data, $productIds);
            $this->adminModel->logActivity($this->admin['admin_id'], 'create_bundle', 'bundles', $id, "Created bundle: {$data['name']}");
            setFlash('success', 'Bundle created successfully');
            $this->redirect('/admin/bundles');
        } catch (\Exception $e) {
            setFlash('error', 'Failed to create bundle: ' . $e->getMessage());
            $this->redirect('/admin/bundles/create');
        }
    }

    /**
     * Update bundle
     */
    public function update(): void
    {
        $this->requireValidCSRF();

        $id = (int)$this->post('id');
        $bundle = $this->bundleModel->findById($id);

        if (!$bundle) {
            setFlash('error', 'Bundle not found');
            $this->redirect('/admin/bundles');
            return;
        }

        $data = [
            'name' => trim($this->post('name', '')),
            'description' => trim($this->post('description', '')),
            'discount_type' => $this->post('discount_type', 'percentage'),
            'discount_value' => floatval($this->post('discount_value', 0)),
            'is_active' => $this->post('is_active') ? 1 : 0,
            'expires_at' => $this->post('expires_at') ?: null
        ];

        $productIds = $this->post('product_ids', []);

        if (empty($data['name'])) {
            setFlash('error', 'Bundle name is required');
            $this->redirect('/admin/bundles/' . $id . '/edit');
            return;
        }

        if ($data['discount_value'] <= 0) {
            setFlash('error', 'Discount value must be greater than 0');
            $this->redirect('/admin/bundles/' . $id . '/edit');
            return;
        }

        if (count($productIds) < 2) {
            setFlash('error', 'A bundle must contain at least 2 products');
            $this->redirect('/admin/bundles/' . $id . '/edit');
            return;
        }

        try {
            $this->bundleModel->updateBundle($id, $data, $productIds);
            $this->adminModel->logActivity($this->admin['admin_id'], 'update_bundle', 'bundles', $id, "Updated bundle: {$data['name']}");
            setFlash('success', 'Bundle updated successfully');
            $this->redirect('/admin/bundles');
        } catch (\Exception $e) {
            setFlash('error', 'Failed to update bundle: ' . $e->getMessage());
            $this->redirect('/admin/bundles/' . $id . '/edit');
        }
    }

    /**
     * Delete bundle
     */
    public function delete(): void
    {
        $this->requireValidCSRF();

        $id = (int)$this->post('id');
        $bundle = $this->bundleModel->findById($id);

        if (!$bundle) {
            $this->json(['success' => false, 'error' => 'Bundle not found']);
            return;
        }

        try {
            $this->bundleModel->deleteBundle($id);
            $this->adminModel->logActivity($this->admin['admin_id'], 'delete_bundle', 'bundles', $id, "Deleted bundle: {$bundle['name']}");
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Save bundle-related setting (AJAX)
     */
    public function saveSetting(): void
    {
        $this->requireValidCSRF();

        $key = trim($this->post('key', ''));
        $value = $this->post('value', '0');

        $allowedKeys = ['allow_coupon_with_bundle'];
        if (!in_array($key, $allowedKeys)) {
            $this->json(['success' => false, 'error' => 'Invalid setting']);
            return;
        }

        $settingModel = new Setting();
        $settingModel->set($key, $value ? '1' : '0');

        $this->adminModel->logActivity($this->admin['admin_id'], 'update_setting', 'settings', null, "Updated $key to " . ($value ? 'enabled' : 'disabled'));
        $this->json(['success' => true]);
    }

    /**
     * Search products for bundle builder (AJAX)
     */
    public function searchProducts(): void
    {
        $query = trim($this->get('q', ''));
        $excludeIds = array_filter(array_map('intval', explode(',', $this->get('exclude', ''))));

        if (strlen($query) < 2) {
            $this->json(['products' => []]);
            return;
        }

        $products = $this->bundleModel->searchProducts($query, $excludeIds);
        $this->json(['products' => $products]);
    }
}
