<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Coupon;

class CloverController extends Controller
{
    private Coupon $couponModel;

    public function __construct()
    {
        parent::__construct();
        $this->couponModel = new Coupon();
    }

    /**
     * Generate a unique one-use clover coupon
     */
    public function generate(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }

        // Rate limit by session/IP to prevent abuse
        $sessionId = session_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $cacheKey = 'clover_' . md5($sessionId . $ip);

        // Check if already generated in this session
        if (isset($_SESSION['clover_code'])) {
            echo json_encode([
                'success' => true,
                'code' => $_SESSION['clover_code'],
                'existing' => true
            ]);
            return;
        }

        try {
            // Generate unique code with CLOVER prefix
            $code = 'CLOVER' . $this->couponModel->generateCode(6);

            // Create one-use coupon: 15% off, no minimum, one use only, no account required
            $couponId = $this->couponModel->createCoupon([
                'code' => $code,
                'description' => 'Lucky Clover Game - 15% off (one-time use)',
                'type' => 'percentage',
                'value' => 15,
                'min_purchase' => 0,
                'max_uses' => 1,
                'applies_to' => 'all',
                'product_ids' => null,
                'category_ids' => null,
                'requires_account' => 0,
                'one_per_customer' => 0,
                'starts_at' => null,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                'is_active' => 1
            ]);

            if ($couponId) {
                // Store in session so they get the same code if they click again
                $_SESSION['clover_code'] = $code;

                echo json_encode([
                    'success' => true,
                    'code' => $code,
                    'existing' => false
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to generate code']);
            }
        } catch (\Exception $e) {
            error_log('Clover coupon error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'An error occurred']);
        }
    }
}
