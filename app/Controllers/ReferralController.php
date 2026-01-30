<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Referral;

class ReferralController extends Controller
{
    private Referral $referral;

    public function __construct()
    {
        parent::__construct();
        $this->referral = new Referral();
    }

    /**
     * Referral program page
     */
    public function index(): void
    {
        $userCode = null;
        $userStats = null;

        if (auth()) {
            $userCode = $this->referral->getCodeByUserId(auth()['id']);

            // Generate code if user doesn't have one
            if (!$userCode) {
                $code = $this->referral->generateCode(auth()['id']);
                $userCode = $this->referral->findByCode($code);
            }

            $userStats = $this->referral->getUserStats(auth()['id']);
        }

        $this->render('referrals/index', [
            'title' => 'Referral Program',
            'metaDescription' => 'Share Lily\'s Pad Studio with friends and earn rewards! Get $10 credit for each friend who makes a purchase.',
            'userCode' => $userCode,
            'userStats' => $userStats
        ]);
    }

    /**
     * Validate referral code (AJAX)
     */
    public function validate(): void
    {
        header('Content-Type: application/json');

        $code = trim($_POST['code'] ?? $_GET['code'] ?? '');

        if (empty($code)) {
            echo json_encode(['valid' => false, 'error' => 'No code provided']);
            return;
        }

        $referral = $this->referral->findByCode($code);

        if (!$referral) {
            echo json_encode(['valid' => false, 'error' => 'Invalid referral code']);
            return;
        }

        if (!$referral['is_active']) {
            echo json_encode(['valid' => false, 'error' => 'This referral code is no longer active']);
            return;
        }

        echo json_encode([
            'valid' => true,
            'discount_percent' => $referral['discount_percent'],
            'message' => 'You\'ll get ' . $referral['discount_percent'] . '% off your order!'
        ]);
    }
}
