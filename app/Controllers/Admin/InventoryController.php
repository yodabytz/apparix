<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Models\AdminUser;

class InventoryController extends Controller
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
     * Show inventory import page
     */
    public function index(): void
    {
        $db = Database::getInstance();

        // Get recent import logs
        $recentImports = $db->select(
            "SELECT * FROM inventory_import_logs ORDER BY created_at DESC LIMIT 10"
        );

        // Get inventory stats
        $stats = $db->selectOne(
            "SELECT
                (SELECT COUNT(*) FROM products WHERE sku IS NOT NULL AND sku != '') as products_with_sku,
                (SELECT COUNT(*) FROM product_variants WHERE sku IS NOT NULL AND sku != '') as variants_with_sku,
                (SELECT SUM(inventory_count) FROM products) as total_product_stock,
                (SELECT SUM(inventory_count) FROM product_variants) as total_variant_stock"
        );

        $this->render('admin.inventory.index', [
            'title' => 'Inventory Import',
            'admin' => $this->admin,
            'recentImports' => $recentImports,
            'stats' => $stats
        ], 'admin');
    }

    /**
     * Process CSV upload and update inventory
     */
    public function import(): void
    {
        $this->requireValidCSRF();

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setFlash('error', 'Please upload a valid CSV file.');
            $this->redirect('/admin/inventory');
            return;
        }

        $file = $_FILES['csv_file'];

        // Validate file type
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            setFlash('error', 'Only CSV files are allowed.');
            $this->redirect('/admin/inventory');
            return;
        }

        // Read and parse CSV
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            setFlash('error', 'Could not read the uploaded file.');
            $this->redirect('/admin/inventory');
            return;
        }

        $db = Database::getInstance();

        // Find SKU and stock columns
        $header = fgetcsv($handle);

        // Skip if first row is a date/update row
        if (stripos($header[0] ?? '', 'updated') !== false || empty(trim($header[0] ?? ''))) {
            $header = fgetcsv($handle);
        }

        // Find column indexes
        $skuCol = null;
        $stockCol = null;

        foreach ($header as $i => $col) {
            $col = strtolower(trim($col));
            if ($col === 'sku') {
                $skuCol = $i;
            }
            if (strpos($col, 'total') !== false && strpos($col, 'stock') !== false) {
                $stockCol = $i;
            }
            if ($col === 'stock' || $col === 'quantity' || $col === 'qty') {
                $stockCol = $i;
            }
        }

        if ($skuCol === null) {
            fclose($handle);
            setFlash('error', 'Could not find SKU column in CSV.');
            $this->redirect('/admin/inventory');
            return;
        }

        if ($stockCol === null) {
            fclose($handle);
            setFlash('error', 'Could not find stock/quantity column in CSV. Looking for: "Total in stock", "stock", "quantity", or "qty"');
            $this->redirect('/admin/inventory');
            return;
        }

        // Get all existing SKUs from database
        $existingProducts = $db->select("SELECT id, sku FROM products WHERE sku IS NOT NULL AND sku != ''");
        $existingVariants = $db->select("SELECT id, sku, product_id FROM product_variants WHERE sku IS NOT NULL AND sku != ''");

        $productSkus = [];
        foreach ($existingProducts as $p) {
            $productSkus[strtoupper(trim($p['sku']))] = $p['id'];
        }

        $variantSkus = [];
        foreach ($existingVariants as $v) {
            $variantSkus[strtoupper(trim($v['sku']))] = ['id' => $v['id'], 'product_id' => $v['product_id']];
        }

        // Process CSV rows
        $updated = 0;
        $skipped = 0;
        $notFound = 0;
        $errors = 0;
        $details = [];

        while (($row = fgetcsv($handle)) !== false) {
            $sku = strtoupper(trim($row[$skuCol] ?? ''));
            $stockValue = trim($row[$stockCol] ?? '');

            if (empty($sku)) {
                $skipped++;
                continue;
            }

            // Parse stock value
            // "-" means available (we'll treat as in stock with 5 units)
            // "0" or empty means out of stock
            // Numbers are actual quantities
            $stock = 0;
            if ($stockValue === '-') {
                $stock = 5; // Available from supplier
            } elseif (is_numeric($stockValue)) {
                $stock = (int)$stockValue;
            } else {
                $stock = 0; // Not available or unknown
            }

            // Try to match variant first, then product
            $matched = false;

            if (isset($variantSkus[$sku])) {
                // Update variant inventory
                try {
                    $db->update(
                        "UPDATE product_variants SET inventory_count = ? WHERE id = ?",
                        [$stock, $variantSkus[$sku]['id']]
                    );
                    $updated++;
                    $matched = true;
                    $details[] = ['sku' => $sku, 'type' => 'variant', 'stock' => $stock, 'status' => 'updated'];
                } catch (\Exception $e) {
                    $errors++;
                    $details[] = ['sku' => $sku, 'type' => 'variant', 'stock' => $stock, 'status' => 'error', 'error' => $e->getMessage()];
                }
            } elseif (isset($productSkus[$sku])) {
                // Update product inventory
                try {
                    $db->update(
                        "UPDATE products SET inventory_count = ? WHERE id = ?",
                        [$stock, $productSkus[$sku]]
                    );
                    $updated++;
                    $matched = true;
                    $details[] = ['sku' => $sku, 'type' => 'product', 'stock' => $stock, 'status' => 'updated'];
                } catch (\Exception $e) {
                    $errors++;
                    $details[] = ['sku' => $sku, 'type' => 'product', 'stock' => $stock, 'status' => 'error', 'error' => $e->getMessage()];
                }
            } else {
                $notFound++;
                // Don't log every not found to avoid huge logs
            }
        }

        fclose($handle);

        // Log the import
        $this->logImport($db, $file['name'], $updated, $skipped, $notFound, $errors);

        // Update price ranges for affected products
        $this->updatePriceRanges($db);

        // Set flash message with results
        $message = "Import complete: $updated items updated";
        if ($notFound > 0) {
            $message .= ", $notFound SKUs not found in database (skipped)";
        }
        if ($skipped > 0) {
            $message .= ", $skipped rows skipped (empty SKU)";
        }
        if ($errors > 0) {
            $message .= ", $errors errors";
        }

        setFlash('success', $message);
        $this->redirect('/admin/inventory');
    }

    /**
     * Log import to database
     */
    private function logImport(Database $db, string $filename, int $updated, int $skipped, int $notFound, int $errors): void
    {
        $db->insert(
            "INSERT INTO inventory_import_logs (filename, updated_count, skipped_count, not_found_count, error_count, admin_id) VALUES (?, ?, ?, ?, ?, ?)",
            [$filename, $updated, $skipped, $notFound, $errors, $this->admin['admin_id']]
        );
    }

    /**
     * Update price ranges for all products with variants
     */
    private function updatePriceRanges(Database $db): void
    {
        // This updates the price_min and price_max based on active variants
        $db->update("
            UPDATE products p
            SET
                price_min = (
                    SELECT MIN(COALESCE(p.sale_price, p.price) + pv.price_adjustment)
                    FROM product_variants pv
                    WHERE pv.product_id = p.id AND pv.is_active = 1
                ),
                price_max = (
                    SELECT MAX(COALESCE(p.sale_price, p.price) + pv.price_adjustment)
                    FROM product_variants pv
                    WHERE pv.product_id = p.id AND pv.is_active = 1
                )
            WHERE EXISTS (SELECT 1 FROM product_variants WHERE product_id = p.id)
        ", []);
    }

    /**
     * Download sample CSV template
     */
    public function template(): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="inventory_template.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['SKU', 'Total in stock']);
        fputcsv($output, ['EXAMPLE-SKU-001', '10']);
        fputcsv($output, ['EXAMPLE-SKU-002', '5']);
        fputcsv($output, ['EXAMPLE-SKU-003', '-']); // Available from supplier
        fputcsv($output, ['EXAMPLE-SKU-004', '0']); // Out of stock
        fclose($output);
        exit;
    }
}
