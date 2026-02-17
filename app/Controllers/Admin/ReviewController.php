<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Review;
use App\Models\AdminUser;

class ReviewController extends Controller
{
    protected Review $reviewModel;
    private AdminUser $adminModel;
    protected ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminUser();
        $this->reviewModel = new Review();
        $this->requireAdmin();
    }

    protected function requireAdmin(): void
    {
        $token = $_COOKIE['admin_token'] ?? null;
        if (!$token) {
            $this->redirect('/admin/login');
            exit;
        }

        $session = $this->adminModel->validateSession($token);
        if (!$session) {
            setcookie('admin_token', '', ['expires' => time() - 3600, 'path' => '/admin']);
            $this->redirect('/admin/login');
            exit;
        }

        $this->admin = $session;
    }

    /**
     * List all reviews
     */
    public function index(): void
    {
        $status = $this->get('status');
        $page = max(1, (int) $this->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $reviews = $this->reviewModel->getAllReviews($limit, $offset, $status);
        $counts = $this->reviewModel->countByStatus();

        $this->render('admin.reviews.index', [
            'title' => 'Product Reviews',
            'admin' => $this->admin,
            'reviews' => $reviews,
            'counts' => $counts,
            'currentStatus' => $status,
            'currentPage' => $page
        ], 'admin');
    }

    /**
     * Approve a review
     */
    public function approve(): void
    {
        $this->requireValidCSRF();

        $reviewId = (int) $this->post('review_id');

        if ($reviewId && $this->reviewModel->approve($reviewId)) {
            setFlash('success', 'Review approved successfully.');
        } else {
            setFlash('error', 'Failed to approve review.');
        }

        $this->redirect('/admin/reviews');
    }

    /**
     * Reject (delete) a review
     */
    public function reject(): void
    {
        $this->requireValidCSRF();

        $reviewId = (int) $this->post('review_id');

        if ($reviewId && $this->reviewModel->reject($reviewId)) {
            setFlash('success', 'Review deleted.');
        } else {
            setFlash('error', 'Failed to delete review.');
        }

        $this->redirect('/admin/reviews');
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(): void
    {
        $this->requireValidCSRF();

        $reviewId = (int) $this->post('review_id');

        if ($reviewId && $this->reviewModel->toggleFeatured($reviewId)) {
            setFlash('success', 'Review featured status updated.');
        } else {
            setFlash('error', 'Failed to update review.');
        }

        $this->redirect('/admin/reviews');
    }

    /**
     * Get helper for query params
     */
    protected function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
}
