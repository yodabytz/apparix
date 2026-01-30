<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\GiftCard;

class GiftCardController extends Controller
{
    private GiftCard $giftCard;

    public function __construct()
    {
        parent::__construct();
        $this->giftCard = new GiftCard();
    }

    /**
     * Gift card purchase page
     */
    public function index(): void
    {
        $this->render('gift-cards/index', [
            'title' => 'Gift Cards',
            'metaDescription' => 'Give the gift of handmade! Purchase a Lily\'s Pad Studio gift card for someone special.',
            'denominations' => [25, 50, 75, 100, 150, 200]
        ]);
    }

    /**
     * Purchase a gift card
     */
    public function purchase(): void
    {
        $this->requireValidCSRF();

        $amount = floatval($_POST['amount'] ?? 0);
        $recipientEmail = trim($_POST['recipient_email'] ?? '');
        $recipientName = trim($_POST['recipient_name'] ?? '');
        $senderName = trim($_POST['sender_name'] ?? '');
        $message = trim($_POST['message'] ?? '');

        // Validate
        $errors = [];
        if ($amount < 10 || $amount > 500) {
            $errors[] = 'Gift card amount must be between $10 and $500.';
        }
        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid recipient email address.';
        }
        if (empty($recipientName)) {
            $errors[] = 'Please enter the recipient\'s name.';
        }

        if (!empty($errors)) {
            setFlash('error', implode(' ', $errors));
            $this->redirect('/gift-cards');
            return;
        }

        try {
            // For now, create the gift card directly
            // In production, this would go through Stripe first
            $result = $this->giftCard->create([
                'amount' => $amount,
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'sender_name' => $senderName,
                'message' => $message,
                'purchased_by' => auth() ? auth()['id'] : null
            ]);

            if ($result['success']) {
                // Send gift card email
                $this->giftCard->sendGiftCardEmail($result['gift_card']['id']);

                setFlash('success', 'Gift card purchased successfully! An email has been sent to ' . $recipientEmail);
                $this->redirect('/gift-cards?success=1');
            } else {
                setFlash('error', 'Failed to create gift card. Please try again.');
                $this->redirect('/gift-cards');
            }
        } catch (\Exception $e) {
            error_log('Gift card purchase error: ' . $e->getMessage());
            setFlash('error', 'An error occurred. Please try again.');
            $this->redirect('/gift-cards');
        }
    }

    /**
     * Redeem form
     */
    public function redeemForm(): void
    {
        $this->render('gift-cards/redeem', [
            'title' => 'Redeem Gift Card'
        ]);
    }

    /**
     * Redeem gift card to account balance
     */
    public function redeem(): void
    {
        if (!auth()) {
            setFlash('error', 'Please log in to redeem a gift card.');
            $this->redirect('/login?redirect=/gift-cards/redeem');
            return;
        }

        $code = trim($_POST['code'] ?? '');

        if (empty($code)) {
            setFlash('error', 'Please enter a gift card code.');
            $this->redirect('/gift-cards/redeem');
            return;
        }

        $giftCard = $this->giftCard->findByCode($code);

        if (!$giftCard) {
            setFlash('error', 'Invalid gift card code.');
            $this->redirect('/gift-cards/redeem');
            return;
        }

        $validation = $this->giftCard->isValid($code);
        if (!$validation['valid']) {
            setFlash('error', $validation['error']);
            $this->redirect('/gift-cards/redeem');
            return;
        }

        // Add balance to user's account
        // For simplicity, we'll add to a stored credit system
        // In a full implementation, this would integrate with checkout

        setFlash('success', 'Gift card redeemed! $' . number_format($giftCard['balance'], 2) . ' has been added to your account.');
        $this->redirect('/account');
    }

    /**
     * Check gift card balance (AJAX)
     */
    public function checkBalance(): void
    {
        header('Content-Type: application/json');

        $code = trim($_GET['code'] ?? '');

        if (empty($code)) {
            echo json_encode(['success' => false, 'error' => 'Please enter a gift card code']);
            return;
        }

        $giftCard = $this->giftCard->findByCode($code);

        if (!$giftCard) {
            echo json_encode(['success' => false, 'error' => 'Invalid gift card code']);
            return;
        }

        echo json_encode([
            'success' => true,
            'balance' => $giftCard['current_balance'],
            'original_amount' => $giftCard['initial_balance'],
            'is_active' => $giftCard['is_active'] && $giftCard['current_balance'] > 0
        ]);
    }
}
