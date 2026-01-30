<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Coupon;
use App\Models\AdminUser;

class CouponController extends Controller
{
    private Coupon $couponModel;
    private AdminUser $adminModel;
    protected ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminUser();
        $this->couponModel = new Coupon();
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
     * List all coupons
     */
    public function index(): void
    {
        $coupons = $this->couponModel->getAllForAdmin();

        $this->render('admin.coupons.index', [
            'title' => 'Coupon Codes',
            'admin' => $this->admin,
            'coupons' => $coupons
        ], 'admin');
    }

    /**
     * Show create coupon form
     */
    public function create(): void
    {
        $db = Database::getInstance();
        $products = $db->select("SELECT id, name FROM products WHERE is_active = 1 ORDER BY name");
        $categories = $db->select("SELECT id, name FROM categories ORDER BY name");

        // Generate a suggested code
        $suggestedCode = $this->couponModel->generateCode();

        $this->render('admin.coupons.create', [
            'title' => 'Create Coupon',
            'admin' => $this->admin,
            'products' => $products,
            'categories' => $categories,
            'suggestedCode' => $suggestedCode
        ], 'admin');
    }

    /**
     * Store new coupon
     */
    public function store(): void
    {
        $this->requireValidCSRF();

        $data = [
            'code' => $this->post('code'),
            'description' => $this->post('description'),
            'type' => $this->post('type'),
            'value' => $this->post('value'),
            'min_purchase' => $this->post('min_purchase') ?: 0,
            'max_uses' => $this->post('max_uses') ?: null,
            'applies_to' => $this->post('applies_to', 'all'),
            'product_ids' => $this->post('applies_to') === 'products' ? implode(',', $this->post('product_ids', [])) : null,
            'category_ids' => $this->post('applies_to') === 'categories' ? implode(',', $this->post('category_ids', [])) : null,
            'requires_account' => $this->post('requires_account') ? 1 : 0,
            'one_per_customer' => $this->post('one_per_customer') ? 1 : 0,
            'starts_at' => $this->post('starts_at') ?: null,
            'expires_at' => $this->post('expires_at') ?: null,
            'is_active' => $this->post('is_active') ? 1 : 0
        ];

        // Validation
        if (empty($data['code'])) {
            setFlash('error', 'Coupon code is required');
            $this->redirect('/admin/coupons/create');
            return;
        }

        if (empty($data['value']) || $data['value'] <= 0) {
            setFlash('error', 'Discount value must be greater than 0');
            $this->redirect('/admin/coupons/create');
            return;
        }

        // Check for duplicate code
        if ($this->couponModel->findByCode($data['code'])) {
            setFlash('error', 'A coupon with this code already exists');
            $this->redirect('/admin/coupons/create');
            return;
        }

        try {
            $this->couponModel->createCoupon($data);
            $this->adminModel->logActivity($this->admin['admin_id'], 'create_coupon', 'coupons', 0, "Created coupon: {$data['code']}");
            setFlash('success', 'Coupon created successfully');
            $this->redirect('/admin/coupons');
        } catch (\Exception $e) {
            setFlash('error', 'Failed to create coupon: ' . $e->getMessage());
            $this->redirect('/admin/coupons/create');
        }
    }

    /**
     * Show edit coupon form
     */
    public function edit(): void
    {
        $id = (int)$this->get('id');
        $coupon = $this->couponModel->findById($id);

        if (!$coupon) {
            setFlash('error', 'Coupon not found');
            $this->redirect('/admin/coupons');
            return;
        }

        $db = Database::getInstance();
        $products = $db->select("SELECT id, name FROM products WHERE is_active = 1 ORDER BY name");
        $categories = $db->select("SELECT id, name FROM categories ORDER BY name");
        $usageHistory = $this->couponModel->getUsageHistory($id);

        $this->render('admin.coupons.edit', [
            'title' => 'Edit Coupon: ' . $coupon['code'],
            'admin' => $this->admin,
            'coupon' => $coupon,
            'products' => $products,
            'categories' => $categories,
            'usageHistory' => $usageHistory
        ], 'admin');
    }

    /**
     * Update coupon
     */
    public function update(): void
    {
        $this->requireValidCSRF();

        $id = (int)$this->post('id');
        $coupon = $this->couponModel->findById($id);

        if (!$coupon) {
            setFlash('error', 'Coupon not found');
            $this->redirect('/admin/coupons');
            return;
        }

        $data = [
            'code' => $this->post('code'),
            'description' => $this->post('description'),
            'type' => $this->post('type'),
            'value' => $this->post('value'),
            'min_purchase' => $this->post('min_purchase') ?: 0,
            'max_uses' => $this->post('max_uses') ?: null,
            'applies_to' => $this->post('applies_to', 'all'),
            'product_ids' => $this->post('applies_to') === 'products' ? implode(',', $this->post('product_ids', [])) : null,
            'category_ids' => $this->post('applies_to') === 'categories' ? implode(',', $this->post('category_ids', [])) : null,
            'requires_account' => $this->post('requires_account') ? 1 : 0,
            'one_per_customer' => $this->post('one_per_customer') ? 1 : 0,
            'starts_at' => $this->post('starts_at') ?: null,
            'expires_at' => $this->post('expires_at') ?: null,
            'is_active' => $this->post('is_active') ? 1 : 0
        ];

        // Check for duplicate code (excluding current)
        $existing = $this->couponModel->findByCode($data['code']);
        if ($existing && $existing['id'] != $id) {
            setFlash('error', 'A coupon with this code already exists');
            $this->redirect('/admin/coupons/' . $id . '/edit');
            return;
        }

        try {
            $this->couponModel->updateCoupon($id, $data);
            $this->adminModel->logActivity($this->admin['admin_id'], 'update_coupon', 'coupons', $id, "Updated coupon: {$data['code']}");
            setFlash('success', 'Coupon updated successfully');
            $this->redirect('/admin/coupons');
        } catch (\Exception $e) {
            setFlash('error', 'Failed to update coupon: ' . $e->getMessage());
            $this->redirect('/admin/coupons/' . $id . '/edit');
        }
    }

    /**
     * Delete coupon
     */
    public function delete(): void
    {
        $this->requireValidCSRF();

        $id = (int)$this->post('id');
        $coupon = $this->couponModel->findById($id);

        if (!$coupon) {
            $this->json(['success' => false, 'error' => 'Coupon not found']);
            return;
        }

        try {
            $this->couponModel->deleteCoupon($id);
            $this->adminModel->logActivity($this->admin['admin_id'], 'delete_coupon', 'coupons', $id, "Deleted coupon: {$coupon['code']}");
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Generate random code (AJAX)
     */
    public function generateCode(): void
    {
        $code = $this->couponModel->generateCode();
        $this->json(['success' => true, 'code' => $code]);
    }

    /**
     * Toggle coupon active status (AJAX)
     */
    public function toggleStatus(): void
    {
        $this->requireValidCSRF();

        $id = (int)$this->post('id');
        $coupon = $this->couponModel->findById($id);

        if (!$coupon) {
            $this->json(['success' => false, 'error' => 'Coupon not found']);
            return;
        }

        $db = Database::getInstance();
        $newStatus = $coupon['is_active'] ? 0 : 1;
        $db->update("UPDATE discount_codes SET is_active = ? WHERE id = ?", [$newStatus, $id]);

        $this->json(['success' => true, 'is_active' => $newStatus]);
    }
}
