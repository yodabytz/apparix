<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Models\AdminUser;
use App\Models\ShippingZone;
use App\Models\ShippingMethod;
use App\Models\ShippingOrigin;

class ShippingController extends Controller
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
     * Shipping dashboard - overview of all shipping settings
     */
    public function index(): void
    {
        $db = Database::getInstance();

        $zones = $db->select("SELECT * FROM shipping_zones ORDER BY sort_order ASC");
        $methods = $db->select(
            "SELECT sm.*, sz.name as zone_name
             FROM shipping_methods sm
             JOIN shipping_zones sz ON sm.zone_id = sz.id
             ORDER BY sz.sort_order, sm.sort_order"
        );
        $origins = $db->select("SELECT * FROM shipping_origins ORDER BY is_default DESC, name ASC");
        $classes = $db->select("SELECT * FROM shipping_classes ORDER BY name ASC");

        $this->render('admin.shipping.index', [
            'title' => 'Shipping Settings',
            'admin' => $this->admin,
            'zones' => $zones,
            'methods' => $methods,
            'origins' => $origins,
            'classes' => $classes
        ], 'admin');
    }

    // ==================== ZONES ====================

    /**
     * Store new zone
     */
    public function storeZone(): void
    {
        $this->requireValidCSRF();
        $db = Database::getInstance();

        $name = trim($this->post('name', ''));
        $countries = $this->post('countries', '');
        $isActive = $this->post('is_active') ? 1 : 0;

        if (empty($name) || empty($countries)) {
            setFlash('error', 'Zone name and countries are required');
            $this->redirect('/admin/shipping');
            return;
        }

        // Parse countries (comma-separated or JSON)
        $countriesArray = $this->parseCountries($countries);

        $db->insert(
            "INSERT INTO shipping_zones (name, countries, is_active, sort_order) VALUES (?, ?, ?, ?)",
            [$name, json_encode($countriesArray), $isActive, 99]
        );

        $this->adminModel->logActivity($this->admin['admin_id'], 'create_shipping_zone', 'shipping_zone', 0, "Created zone: $name");

        setFlash('success', 'Shipping zone created successfully');
        $this->redirect('/admin/shipping');
    }

    /**
     * Update zone
     */
    public function updateZone(): void
    {
        $this->requireValidCSRF();
        $db = Database::getInstance();

        $id = (int) $this->post('id');
        $name = trim($this->post('name', ''));
        $countries = $this->post('countries', '');
        $isActive = $this->post('is_active') ? 1 : 0;

        if (empty($name) || empty($countries)) {
            setFlash('error', 'Zone name and countries are required');
            $this->redirect('/admin/shipping');
            return;
        }

        $countriesArray = $this->parseCountries($countries);

        $db->update(
            "UPDATE shipping_zones SET name = ?, countries = ?, is_active = ? WHERE id = ?",
            [$name, json_encode($countriesArray), $isActive, $id]
        );

        setFlash('success', 'Shipping zone updated successfully');
        $this->redirect('/admin/shipping');
    }

    /**
     * Delete zone
     */
    public function deleteZone(): void
    {
        $this->requireValidCSRF();
        $db = Database::getInstance();

        $id = (int) $this->post('id');

        // Check if zone has methods
        $methodCount = $db->selectOne("SELECT COUNT(*) as count FROM shipping_methods WHERE zone_id = ?", [$id]);
        if ($methodCount['count'] > 0) {
            setFlash('error', 'Cannot delete zone with shipping methods. Delete the methods first.');
            $this->redirect('/admin/shipping');
            return;
        }

        $db->update("DELETE FROM shipping_zones WHERE id = ?", [$id]);

        setFlash('success', 'Shipping zone deleted');
        $this->redirect('/admin/shipping');
    }

    // ==================== METHODS ====================

    /**
     * Store new method
     */
    public function storeMethod(): void
    {
        $this->requireValidCSRF();
        $db = Database::getInstance();

        $zoneId = (int) $this->post('zone_id');
        $carrier = trim($this->post('carrier', 'usps'));
        $methodCode = trim($this->post('method_code', 'standard'));
        $name = trim($this->post('name', ''));
        $deliveryEstimate = trim($this->post('delivery_estimate', ''));
        $rateType = $this->post('rate_type', 'flat');
        $flatRate = $this->post('flat_rate') !== '' ? floatval($this->post('flat_rate')) : null;
        $minOrderFree = $this->post('min_order_free') !== '' ? floatval($this->post('min_order_free')) : null;
        $handlingFee = floatval($this->post('handling_fee', 0));
        $isActive = $this->post('is_active') ? 1 : 0;

        if (empty($name) || !$zoneId) {
            setFlash('error', 'Method name and zone are required');
            $this->redirect('/admin/shipping');
            return;
        }

        $db->insert(
            "INSERT INTO shipping_methods (zone_id, carrier, method_code, name, delivery_estimate, rate_type, flat_rate, min_order_free, handling_fee, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$zoneId, $carrier, $methodCode, $name, $deliveryEstimate, $rateType, $flatRate, $minOrderFree, $handlingFee, $isActive, 99]
        );

        setFlash('success', 'Shipping method created successfully');
        $this->redirect('/admin/shipping');
    }

    /**
     * Update method
     */
    public function updateMethod(): void
    {
        $this->requireValidCSRF();
        $db = Database::getInstance();

        $id = (int) $this->post('id');
        $zoneId = (int) $this->post('zone_id');
        $carrier = trim($this->post('carrier', 'usps'));
        $methodCode = trim($this->post('method_code', 'standard'));
        $name = trim($this->post('name', ''));
        $deliveryEstimate = trim($this->post('delivery_estimate', ''));
        $rateType = $this->post('rate_type', 'flat');
        $flatRate = $this->post('flat_rate') !== '' ? floatval($this->post('flat_rate')) : null;
        $minOrderFree = $this->post('min_order_free') !== '' ? floatval($this->post('min_order_free')) : null;
        $handlingFee = floatval($this->post('handling_fee', 0));
        $isActive = $this->post('is_active') ? 1 : 0;

        $db->update(
            "UPDATE shipping_methods SET zone_id = ?, carrier = ?, method_code = ?, name = ?, delivery_estimate = ?,
             rate_type = ?, flat_rate = ?, min_order_free = ?, handling_fee = ?, is_active = ? WHERE id = ?",
            [$zoneId, $carrier, $methodCode, $name, $deliveryEstimate, $rateType, $flatRate, $minOrderFree, $handlingFee, $isActive, $id]
        );

        setFlash('success', 'Shipping method updated successfully');
        $this->redirect('/admin/shipping');
    }

    /**
     * Delete method
     */
    public function deleteMethod(): void
    {
        $this->requireValidCSRF();
        $db = Database::getInstance();

        $id = (int) $this->post('id');
        $db->update("DELETE FROM shipping_methods WHERE id = ?", [$id]);

        setFlash('success', 'Shipping method deleted');
        $this->redirect('/admin/shipping');
    }

    // ==================== ORIGINS/WAREHOUSES ====================

    /**
     * Store new origin
     */
    public function storeOrigin(): void
    {
        $this->requireValidCSRF();
        $db = Database::getInstance();

        $name = trim($this->post('name', ''));
        $addressLine1 = trim($this->post('address_line1', ''));
        $addressLine2 = trim($this->post('address_line2', ''));
        $city = trim($this->post('city', ''));
        $state = trim($this->post('state', ''));
        $postalCode = trim($this->post('postal_code', ''));
        $country = trim($this->post('country', 'US'));
        $phone = trim($this->post('phone', ''));
        $isDefault = $this->post('is_default') ? 1 : 0;
        $isActive = $this->post('is_active') ? 1 : 0;

        // Shipping costs per destination
        $shippingCostUsa = $this->post('shipping_cost_usa') !== '' ? floatval($this->post('shipping_cost_usa')) : null;
        $shippingCostCanada = $this->post('shipping_cost_canada') !== '' ? floatval($this->post('shipping_cost_canada')) : null;
        $shippingCostOverseas = $this->post('shipping_cost_overseas') !== '' ? floatval($this->post('shipping_cost_overseas')) : null;

        if (empty($name) || empty($addressLine1) || empty($city) || empty($state) || empty($postalCode)) {
            setFlash('error', 'Name, address, city, state, and postal code are required');
            $this->redirect('/admin/shipping');
            return;
        }

        // If this is default, clear other defaults
        if ($isDefault) {
            $db->update("UPDATE shipping_origins SET is_default = 0");
        }

        $db->insert(
            "INSERT INTO shipping_origins (name, address_line1, address_line2, city, state, postal_code, country, phone, is_default, is_active, shipping_cost_usa, shipping_cost_canada, shipping_cost_overseas)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$name, $addressLine1, $addressLine2 ?: null, $city, $state, $postalCode, $country, $phone ?: null, $isDefault, $isActive, $shippingCostUsa, $shippingCostCanada, $shippingCostOverseas]
        );

        setFlash('success', 'Warehouse/origin created successfully');
        $this->redirect('/admin/shipping');
    }

    /**
     * Update origin
     */
    public function updateOrigin(): void
    {
        $this->requireValidCSRF();
        $db = Database::getInstance();

        $id = (int) $this->post('id');
        $name = trim($this->post('name', ''));
        $addressLine1 = trim($this->post('address_line1', ''));
        $addressLine2 = trim($this->post('address_line2', ''));
        $city = trim($this->post('city', ''));
        $state = trim($this->post('state', ''));
        $postalCode = trim($this->post('postal_code', ''));
        $country = trim($this->post('country', 'US'));
        $phone = trim($this->post('phone', ''));
        $isDefault = $this->post('is_default') ? 1 : 0;
        $isActive = $this->post('is_active') ? 1 : 0;

        // Shipping costs per destination
        $shippingCostUsa = $this->post('shipping_cost_usa') !== '' ? floatval($this->post('shipping_cost_usa')) : null;
        $shippingCostCanada = $this->post('shipping_cost_canada') !== '' ? floatval($this->post('shipping_cost_canada')) : null;
        $shippingCostOverseas = $this->post('shipping_cost_overseas') !== '' ? floatval($this->post('shipping_cost_overseas')) : null;

        if (empty($name) || empty($addressLine1) || empty($city) || empty($state) || empty($postalCode)) {
            setFlash('error', 'Name, address, city, state, and postal code are required');
            $this->redirect('/admin/shipping');
            return;
        }

        // If this is default, clear other defaults
        if ($isDefault) {
            $db->update("UPDATE shipping_origins SET is_default = 0 WHERE id != ?", [$id]);
        }

        $db->update(
            "UPDATE shipping_origins SET name = ?, address_line1 = ?, address_line2 = ?, city = ?, state = ?,
             postal_code = ?, country = ?, phone = ?, is_default = ?, is_active = ?,
             shipping_cost_usa = ?, shipping_cost_canada = ?, shipping_cost_overseas = ? WHERE id = ?",
            [$name, $addressLine1, $addressLine2 ?: null, $city, $state, $postalCode, $country, $phone ?: null, $isDefault, $isActive, $shippingCostUsa, $shippingCostCanada, $shippingCostOverseas, $id]
        );

        setFlash('success', 'Warehouse/origin updated successfully');
        $this->redirect('/admin/shipping');
    }

    /**
     * Delete origin
     */
    public function deleteOrigin(): void
    {
        $this->requireValidCSRF();
        $db = Database::getInstance();

        $id = (int) $this->post('id');

        // Check if products are using this origin
        $productCount = $db->selectOne("SELECT COUNT(*) as count FROM products WHERE origin_id = ?", [$id]);
        if ($productCount['count'] > 0) {
            setFlash('error', 'Cannot delete origin used by products. Update the products first.');
            $this->redirect('/admin/shipping');
            return;
        }

        $db->update("DELETE FROM shipping_origins WHERE id = ?", [$id]);

        setFlash('success', 'Warehouse/origin deleted');
        $this->redirect('/admin/shipping');
    }

    // ==================== SHIPPING CLASSES ====================

    /**
     * Store new class
     */
    public function storeClass(): void
    {
        $this->requireValidCSRF();
        $db = Database::getInstance();

        $name = trim($this->post('name', ''));
        $slug = $this->generateSlug($name);
        $description = trim($this->post('description', ''));
        $handlingFee = floatval($this->post('handling_fee', 0));
        $isActive = $this->post('is_active') ? 1 : 0;

        if (empty($name)) {
            setFlash('error', 'Class name is required');
            $this->redirect('/admin/shipping');
            return;
        }

        $db->insert(
            "INSERT INTO shipping_classes (name, slug, description, handling_fee, is_active) VALUES (?, ?, ?, ?, ?)",
            [$name, $slug, $description ?: null, $handlingFee, $isActive]
        );

        setFlash('success', 'Shipping class created successfully');
        $this->redirect('/admin/shipping');
    }

    /**
     * Update class
     */
    public function updateClass(): void
    {
        $this->requireValidCSRF();
        $db = Database::getInstance();

        $id = (int) $this->post('id');
        $name = trim($this->post('name', ''));
        $description = trim($this->post('description', ''));
        $handlingFee = floatval($this->post('handling_fee', 0));
        $isActive = $this->post('is_active') ? 1 : 0;

        $db->update(
            "UPDATE shipping_classes SET name = ?, description = ?, handling_fee = ?, is_active = ? WHERE id = ?",
            [$name, $description ?: null, $handlingFee, $isActive, $id]
        );

        setFlash('success', 'Shipping class updated successfully');
        $this->redirect('/admin/shipping');
    }

    /**
     * Delete class
     */
    public function deleteClass(): void
    {
        $this->requireValidCSRF();
        $db = Database::getInstance();

        $id = (int) $this->post('id');

        // Check if products are using this class
        $productCount = $db->selectOne("SELECT COUNT(*) as count FROM products WHERE shipping_class_id = ?", [$id]);
        if ($productCount['count'] > 0) {
            setFlash('error', 'Cannot delete class used by products. Update the products first.');
            $this->redirect('/admin/shipping');
            return;
        }

        $db->update("DELETE FROM shipping_classes WHERE id = ?", [$id]);

        setFlash('success', 'Shipping class deleted');
        $this->redirect('/admin/shipping');
    }

    // ==================== HELPERS ====================

    private function parseCountries(string $input): array
    {
        // Try JSON first
        $decoded = json_decode($input, true);
        if (is_array($decoded)) {
            return array_map('strtoupper', array_map('trim', $decoded));
        }

        // Fall back to comma-separated
        $parts = explode(',', $input);
        return array_map('strtoupper', array_map('trim', $parts));
    }

    private function generateSlug(string $name): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
}
