<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Review;
use App\Models\Product;

class ReviewController extends Controller
{
    protected Review $reviewModel;
    protected Product $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->reviewModel = new Review();
        $this->productModel = new Product();
    }

    /**
     * Get reviews for a product (AJAX)
     */
    public function getProductReviews(): void
    {
        $productId = (int) $this->get('product_id');
        $page = max(1, (int) $this->get('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        if (!$productId) {
            $this->json(['error' => 'Product ID required'], 400);
            return;
        }

        $reviews = $this->reviewModel->getProductReviews($productId, $limit, $offset);
        $stats = $this->reviewModel->getProductStats($productId);

        // Check if current user can review
        $canReview = ['can_review' => false, 'reason' => 'login_required'];
        if (auth()) {
            $canReview = $this->reviewModel->canUserReview(auth()['id'], $productId);
        }

        $this->json([
            'reviews' => $reviews,
            'stats' => $stats,
            'can_review' => $canReview,
            'page' => $page,
            'has_more' => count($reviews) === $limit
        ]);
    }

    /**
     * Submit a review (AJAX)
     */
    public function submit(): void
    {
        $this->requireValidCSRF();

        // Must be logged in
        if (!auth()) {
            $this->json(['error' => 'You must be logged in to submit a review'], 401);
            return;
        }

        $userId = auth()['id'];
        $productId = (int) $this->post('product_id');
        $rating = (int) $this->post('rating');
        $title = trim($this->post('title', ''));
        $reviewText = trim($this->post('review_text', ''));

        // Validate
        if (!$productId || $rating < 1 || $rating > 5) {
            $this->json(['error' => 'Invalid rating. Please select 1-5 stars.'], 400);
            return;
        }

        // Check if user can review this product
        $canReview = $this->reviewModel->canUserReview($userId, $productId);

        if (!$canReview['can_review']) {
            $message = match ($canReview['reason']) {
                'purchase_required' => 'You must purchase this product before reviewing it.',
                'already_reviewed' => 'You have already reviewed this product.',
                default => 'You cannot review this product.'
            };
            $this->json(['error' => $message], 403);
            return;
        }

        try {
            $reviewId = $this->reviewModel->submitReview(
                $productId,
                $userId,
                $canReview['order_id'],
                $rating,
                $title ?: null,
                $reviewText ?: null
            );

            // Mark any pending review request as reviewed
            $this->markReviewRequestAsReviewed($userId, $productId, $canReview['order_id']);

            $this->json([
                'success' => true,
                'message' => 'Thank you for your review! It will be visible after moderation.'
            ]);

        } catch (\Exception $e) {
            error_log("Review submission failed: " . $e->getMessage());
            $this->json(['error' => 'Failed to submit review. Please try again.'], 500);
        }
    }

    /**
     * Review page from email link
     */
    public function fromEmail(string $token): void
    {
        $request = $this->reviewModel->getRequestByToken($token);

        if (!$request) {
            setFlash('error', 'This review link is invalid or has expired.');
            $this->redirect('/');
            return;
        }

        // Get product
        $product = $this->productModel->findBySlug($request['product_slug']);

        if (!$product) {
            setFlash('error', 'Product not found.');
            $this->redirect('/');
            return;
        }

        // Check if already reviewed
        if ($request['status'] === 'reviewed') {
            setFlash('info', 'You have already reviewed this product. Thank you!');
            $this->redirect('/products/' . $product['slug']);
            return;
        }

        // Get product image
        $images = $this->productModel->getImages($product['id']);
        $product['primary_image'] = $images[0]['image_path'] ?? null;

        $this->render('reviews/email-review', [
            'title' => 'Review ' . $product['name'],
            'product' => $product,
            'request' => $request,
            'token' => $token
        ]);
    }

    /**
     * Submit review from email link
     */
    public function submitFromEmail(): void
    {
        $this->requireValidCSRF();

        $token = $this->post('token');
        $rating = (int) $this->post('rating');
        $title = trim($this->post('title', ''));
        $reviewText = trim($this->post('review_text', ''));

        if (!$token || $rating < 1 || $rating > 5) {
            setFlash('error', 'Invalid review submission.');
            $this->redirect('/');
            return;
        }

        $request = $this->reviewModel->getRequestByToken($token);

        if (!$request) {
            setFlash('error', 'This review link is invalid or has expired.');
            $this->redirect('/');
            return;
        }

        try {
            $this->reviewModel->submitReview(
                $request['product_id'],
                $request['user_id'],
                $request['order_id'],
                $rating,
                $title ?: null,
                $reviewText ?: null
            );

            // Mark request as reviewed
            $this->reviewModel->markRequestReviewed($request['id']);

            setFlash('success', 'Thank you for your review! It will be visible after moderation.');
            $this->redirect('/products/' . $request['product_slug']);

        } catch (\Exception $e) {
            error_log("Email review submission failed: " . $e->getMessage());
            setFlash('error', 'Failed to submit review. Please try again.');
            $this->redirect('/review/' . $token);
        }
    }

    /**
     * Mark review request as reviewed
     */
    private function markReviewRequestAsReviewed(int $userId, int $productId, int $orderId): void
    {
        $db = \App\Core\Database::getInstance();

        $request = $db->selectOne(
            "SELECT id FROM review_requests WHERE user_id = ? AND product_id = ? AND order_id = ?",
            [$userId, $productId, $orderId]
        );

        if ($request) {
            $this->reviewModel->markRequestReviewed($request['id']);
        }
    }

    /**
     * Get helper method for query params
     */
    protected function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
}
