<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Page;
use App\Models\AdminUser;

class PageController extends Controller
{
    private Page $pageModel;
    private AdminUser $adminModel;
    protected ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminUser();
        $this->pageModel = new Page();
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

    public function index(): void
    {
        $pages = $this->pageModel->getAll('sort_order ASC, title ASC');

        $this->render('admin.pages.index', [
            'title' => 'Pages',
            'admin' => $this->admin,
            'pages' => $pages
        ], 'admin');
    }

    public function create(): void
    {
        $this->render('admin.pages.create', [
            'title' => 'Create Page',
            'admin' => $this->admin
        ], 'admin');
    }

    public function store(): void
    {
        $this->requireValidCSRF();

        $title = trim($this->post('title', ''));
        $slug = trim($this->post('slug', ''));
        $content = $this->post('content', '');

        if (empty($title)) {
            setFlash('error', 'Page title is required');
            $this->redirect('/admin/pages/create');
            return;
        }

        if (empty($slug)) {
            $slug = $this->generateSlug($title);
        } else {
            $slug = $this->generateSlug($slug);
        }

        if ($this->pageModel->slugExists($slug)) {
            setFlash('error', 'A page with this URL slug already exists');
            $this->redirect('/admin/pages/create');
            return;
        }

        $data = [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'meta_title' => trim($this->post('meta_title', '')) ?: null,
            'meta_description' => trim($this->post('meta_description', '')) ?: null,
            'keywords' => trim($this->post('keywords', '')) ?: null,
            'is_active' => $this->post('is_active') ? 1 : 0,
            'show_title' => $this->post('show_title') ? 1 : 0,
            'show_in_footer' => $this->post('show_in_footer') ? 1 : 0,
            'footer_label' => trim($this->post('footer_label', '')) ?: null,
            'sort_order' => (int)$this->post('sort_order', 0)
        ];

        try {
            $this->pageModel->create($data);
            $this->adminModel->logActivity($this->admin['admin_id'], 'create_page', 'pages', 0, "Created page: {$title}");
            setFlash('success', 'Page created successfully');
            $this->redirect('/admin/pages');
        } catch (\Exception $e) {
            setFlash('error', 'Failed to create page: ' . $e->getMessage());
            $this->redirect('/admin/pages/create');
        }
    }

    public function edit(): void
    {
        $id = (int)$this->get('id');
        $page = $this->pageModel->find($id);

        if (!$page) {
            setFlash('error', 'Page not found');
            $this->redirect('/admin/pages');
            return;
        }

        $this->render('admin.pages.edit', [
            'title' => 'Edit Page: ' . $page['title'],
            'admin' => $this->admin,
            'page' => $page
        ], 'admin');
    }

    public function update(): void
    {
        $this->requireValidCSRF();

        $id = (int)$this->post('id');
        $page = $this->pageModel->find($id);

        if (!$page) {
            setFlash('error', 'Page not found');
            $this->redirect('/admin/pages');
            return;
        }

        $title = trim($this->post('title', ''));
        $slug = trim($this->post('slug', ''));

        if (empty($title)) {
            setFlash('error', 'Page title is required');
            $this->redirect('/admin/pages/' . $id . '/edit');
            return;
        }

        if (empty($slug)) {
            $slug = $this->generateSlug($title);
        } else {
            $slug = $this->generateSlug($slug);
        }

        if ($this->pageModel->slugExists($slug, $id)) {
            setFlash('error', 'A page with this URL slug already exists');
            $this->redirect('/admin/pages/' . $id . '/edit');
            return;
        }

        $data = [
            'title' => $title,
            'slug' => $slug,
            'content' => $this->post('content', ''),
            'meta_title' => trim($this->post('meta_title', '')) ?: null,
            'meta_description' => trim($this->post('meta_description', '')) ?: null,
            'keywords' => trim($this->post('keywords', '')) ?: null,
            'is_active' => $this->post('is_active') ? 1 : 0,
            'show_title' => $this->post('show_title') ? 1 : 0,
            'show_in_footer' => $this->post('show_in_footer') ? 1 : 0,
            'footer_label' => trim($this->post('footer_label', '')) ?: null,
            'sort_order' => (int)$this->post('sort_order', 0)
        ];

        try {
            $this->pageModel->update($id, $data);
            $this->adminModel->logActivity($this->admin['admin_id'], 'update_page', 'pages', $id, "Updated page: {$title}");
            setFlash('success', 'Page updated successfully');
            $this->redirect('/admin/pages');
        } catch (\Exception $e) {
            setFlash('error', 'Failed to update page: ' . $e->getMessage());
            $this->redirect('/admin/pages/' . $id . '/edit');
        }
    }

    public function delete(): void
    {
        $this->requireValidCSRF();

        $id = (int)$this->post('id');
        $page = $this->pageModel->find($id);

        if (!$page) {
            $this->json(['success' => false, 'error' => 'Page not found']);
            return;
        }

        try {
            $this->pageModel->delete($id);
            $this->adminModel->logActivity($this->admin['admin_id'], 'delete_page', 'pages', $id, "Deleted page: {$page['title']}");
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function generateSlug(string $text): string
    {
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
}
