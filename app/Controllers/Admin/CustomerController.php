<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\User;
use App\Models\AdminUser;

class CustomerController extends Controller
{
    private User $userModel;
    private AdminUser $adminModel;
    protected ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
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
     * List all customers
     */
    public function index(): void
    {
        $search = trim($this->get('search', ''));
        $page = max(1, (int)$this->get('page', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $users = $this->userModel->getAllUsers($perPage, $offset, $search ?: null);
        $totalUsers = $this->userModel->countUsers($search ?: null);
        $totalPages = ceil($totalUsers / $perPage);

        $this->render('admin.customers.index', [
            'title' => 'Customers',
            'admin' => $this->admin,
            'users' => $users,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalUsers' => $totalUsers
        ], 'admin');
    }

    /**
     * Delete customer
     */
    public function delete(): void
    {
        $this->requireValidCSRF();

        $id = (int)$this->post('id');

        if (!$id) {
            setFlash('error', 'Invalid request');
            $this->redirect('/admin/customers');
            return;
        }

        $user = $this->userModel->findById($id);

        if (!$user) {
            setFlash('error', 'Customer not found');
            $this->redirect('/admin/customers');
            return;
        }

        try {
            $this->userModel->deleteUser($id);

            // Log activity
            $this->adminModel->logActivity(
                $this->admin['admin_id'],
                'delete_customer',
                'user',
                $id,
                "Deleted customer: {$user['email']}"
            );

            setFlash('success', 'Customer deleted successfully');
        } catch (\Exception $e) {
            setFlash('error', 'Failed to delete customer');
        }

        $this->redirect('/admin/customers');
    }
}
