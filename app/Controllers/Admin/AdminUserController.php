<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\License;
use App\Models\AdminUser;

class AdminUserController extends Controller
{
    private AdminUser $adminModel;
    protected ?array $admin = null;

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

        // Only super_admin can manage admin users
        if ($this->admin['role'] !== 'super_admin') {
            setFlash('error', 'You do not have permission to manage admin users');
            $this->redirect('/admin');
            exit;
        }
    }

    /**
     * List all admin users
     */
    public function index(): void
    {
        $admins = $this->adminModel->getAllAdmins();

        $this->render('admin.users.index', [
            'title' => 'Admin Users',
            'admin' => $this->admin,
            'admins' => $admins
        ], 'admin');
    }

    /**
     * Show create admin form
     */
    public function create(): void
    {
        $this->render('admin.users.create', [
            'title' => 'Add Admin User',
            'admin' => $this->admin
        ], 'admin');
    }

    /**
     * Store new admin user
     */
    public function store(): void
    {
        $this->requireValidCSRF();

        // Check license admin user limit
        $currentAdminCount = count($this->adminModel->getAllAdmins());
        if (!License::canAddAdminUser($currentAdminCount)) {
            $limit = License::getLimit('max_admin_users');
            setFlash('error', "Admin user limit reached ({$limit} users). Please upgrade your license to add more admin users.");
            $this->redirect('/admin/users');
            return;
        }

        $email = trim($this->post('email', ''));
        $name = trim($this->post('name', ''));
        $password = $this->post('password', '');
        $confirmPassword = $this->post('confirm_password', '');
        $role = $this->post('role', 'admin');

        // Validation
        $errors = [];

        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif ($this->adminModel->emailExists($email)) {
            $errors[] = 'Email already exists';
        }

        if (empty($name)) {
            $errors[] = 'Name is required';
        } elseif (strlen($name) > 100) {
            $errors[] = 'Name is too long';
        }

        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        } elseif ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }

        // Validate role
        $validRoles = ['admin', 'super_admin'];
        if (!in_array($role, $validRoles)) {
            $role = 'admin';
        }

        if (!empty($errors)) {
            setFlash('error', implode('<br>', $errors));
            $this->redirect('/admin/users/create');
            return;
        }

        try {
            $this->adminModel->createAdmin($email, $password, $name, $role);

            // Log activity
            $this->adminModel->logActivity(
                $this->admin['admin_id'],
                'create_admin',
                'admin_user',
                null,
                "Created admin user: {$email}"
            );

            setFlash('success', 'Admin user created successfully');
            $this->redirect('/admin/users');
        } catch (\Exception $e) {
            setFlash('error', 'Failed to create admin user');
            $this->redirect('/admin/users/create');
        }
    }

    /**
     * Show edit admin form
     */
    public function edit(): void
    {
        $id = (int) $this->get('id');

        if (!$id) {
            $this->redirect('/admin/users');
            return;
        }

        $adminUser = $this->adminModel->findById($id);

        if (!$adminUser) {
            setFlash('error', 'Admin user not found');
            $this->redirect('/admin/users');
            return;
        }

        $this->render('admin.users.edit', [
            'title' => 'Edit Admin User',
            'admin' => $this->admin,
            'adminUser' => $adminUser
        ], 'admin');
    }

    /**
     * Update admin user
     */
    public function update(): void
    {
        $this->requireValidCSRF();

        $id = (int) $this->post('id');

        if (!$id) {
            $this->redirect('/admin/users');
            return;
        }

        $adminUser = $this->adminModel->findById($id);

        if (!$adminUser) {
            setFlash('error', 'Admin user not found');
            $this->redirect('/admin/users');
            return;
        }

        $email = trim($this->post('email', ''));
        $name = trim($this->post('name', ''));
        $password = $this->post('password', '');
        $confirmPassword = $this->post('confirm_password', '');
        $role = $this->post('role', 'admin');

        // Validation
        $errors = [];

        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif ($this->adminModel->emailExists($email, $id)) {
            $errors[] = 'Email already exists';
        }

        if (empty($name)) {
            $errors[] = 'Name is required';
        } elseif (strlen($name) > 100) {
            $errors[] = 'Name is too long';
        }

        // Password is optional for updates
        if (!empty($password)) {
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters';
            } elseif ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match';
            }
        }

        // Validate role
        $validRoles = ['admin', 'super_admin'];
        if (!in_array($role, $validRoles)) {
            $role = 'admin';
        }

        // Prevent demoting yourself
        if ($id === $this->admin['admin_id'] && $role !== $this->admin['role']) {
            $errors[] = 'You cannot change your own role';
        }

        if (!empty($errors)) {
            setFlash('error', implode('<br>', $errors));
            $this->redirect('/admin/users/edit?id=' . $id);
            return;
        }

        $data = [
            'email' => $email,
            'name' => $name,
            'role' => $role
        ];

        if (!empty($password)) {
            $data['password'] = $password;
        }

        try {
            $this->adminModel->updateAdmin($id, $data);

            // Log activity
            $this->adminModel->logActivity(
                $this->admin['admin_id'],
                'update_admin',
                'admin_user',
                $id,
                "Updated admin user: {$email}"
            );

            setFlash('success', 'Admin user updated successfully');
            $this->redirect('/admin/users');
        } catch (\Exception $e) {
            setFlash('error', 'Failed to update admin user');
            $this->redirect('/admin/users/edit?id=' . $id);
        }
    }

    /**
     * Delete admin user
     */
    public function delete(): void
    {
        $this->requireValidCSRF();

        $id = (int) $this->post('id');

        if (!$id) {
            setFlash('error', 'Invalid request');
            $this->redirect('/admin/users');
            return;
        }

        // Prevent self-deletion
        if ($id === $this->admin['admin_id']) {
            setFlash('error', 'You cannot delete your own account');
            $this->redirect('/admin/users');
            return;
        }

        // Prevent deleting the last admin
        if ($this->adminModel->countAdmins() <= 1) {
            setFlash('error', 'Cannot delete the last admin user');
            $this->redirect('/admin/users');
            return;
        }

        $adminUser = $this->adminModel->findById($id);

        if (!$adminUser) {
            setFlash('error', 'Admin user not found');
            $this->redirect('/admin/users');
            return;
        }

        try {
            $this->adminModel->deleteAdmin($id);

            // Log activity
            $this->adminModel->logActivity(
                $this->admin['admin_id'],
                'delete_admin',
                'admin_user',
                $id,
                "Deleted admin user: {$adminUser['email']}"
            );

            setFlash('success', 'Admin user deleted successfully');
        } catch (\Exception $e) {
            setFlash('error', 'Failed to delete admin user');
        }

        $this->redirect('/admin/users');
    }
}
