<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Models\AdminUser;

/**
 * Admin controller for managing software releases
 */
class ReleaseController extends Controller
{
    private AdminUser $adminModel;
    private Database $db;
    private ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminUser();
        $this->db = Database::getInstance();
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
     * List all releases
     */
    public function index(): void
    {
        $releases = $this->db->select(
            "SELECT * FROM releases ORDER BY version_major DESC, version_minor DESC, version_patch DESC"
        );

        // Get update statistics
        $stats = $this->db->selectOne(
            "SELECT
                COUNT(*) as total_downloads,
                COUNT(DISTINCT license_key) as unique_licenses,
                SUM(CASE WHEN status = 'installed' THEN 1 ELSE 0 END) as successful_installs,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_installs
             FROM update_logs"
        );

        $this->render('admin.releases.index', [
            'title' => 'Software Releases',
            'admin' => $this->admin,
            'releases' => $releases,
            'stats' => $stats
        ], 'admin');
    }

    /**
     * Show create release form
     */
    public function create(): void
    {
        $this->render('admin.releases.create', [
            'title' => 'Create New Release',
            'admin' => $this->admin
        ], 'admin');
    }

    /**
     * Store new release
     */
    public function store(): void
    {
        $this->requireValidCSRF();

        $version = trim($this->post('version', ''));
        $releaseType = $this->post('release_type', 'stable');
        $releaseNotes = trim($this->post('release_notes', ''));
        $changelog = trim($this->post('changelog', ''));
        $minPhpVersion = trim($this->post('min_php_version', '8.0'));
        $minEdition = $this->post('min_edition', 'S');
        $isActive = $this->post('is_active') ? 1 : 0;

        // Validate version format
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            setFlash('error', 'Invalid version format. Use semantic versioning (e.g., 1.2.3)');
            $this->redirect('/admin/releases/create');
            return;
        }

        // Parse version numbers
        $parts = explode('.', $version);
        $versionMajor = (int) $parts[0];
        $versionMinor = (int) $parts[1];
        $versionPatch = (int) $parts[2];

        // Check if version exists
        $existing = $this->db->selectOne("SELECT id FROM releases WHERE version = ?", [$version]);
        if ($existing) {
            setFlash('error', 'Version ' . $version . ' already exists');
            $this->redirect('/admin/releases/create');
            return;
        }

        // Handle file upload
        if (empty($_FILES['update_file']['name'])) {
            setFlash('error', 'Update file is required');
            $this->redirect('/admin/releases/create');
            return;
        }

        $uploadedFile = $_FILES['update_file'];
        $filename = 'apparix-' . $version . '.tar.gz';
        $uploadPath = BASE_PATH . '/storage/updates/' . $filename;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $uploadPath)) {
            setFlash('error', 'Failed to upload file');
            $this->redirect('/admin/releases/create');
            return;
        }

        // Calculate file hash and size
        $fileHash = hash_file('sha256', $uploadPath);
        $fileSize = filesize($uploadPath);

        // Insert release
        $this->db->insert(
            "INSERT INTO releases (version, version_major, version_minor, version_patch, release_type, release_notes, changelog, min_php_version, min_edition, update_file, file_hash, file_size, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$version, $versionMajor, $versionMinor, $versionPatch, $releaseType, $releaseNotes, $changelog, $minPhpVersion, $minEdition, $filename, $fileHash, $fileSize, $isActive]
        );

        // Log activity
        $this->adminModel->logActivity($this->admin['admin_id'], 'create_release', 'release', null, "Created release v{$version}");

        setFlash('success', 'Release v' . $version . ' created successfully');
        $this->redirect('/admin/releases');
    }

    /**
     * Edit release
     */
    public function edit(): void
    {
        $id = $_GET['id'] ?? 0;

        $release = $this->db->selectOne("SELECT * FROM releases WHERE id = ?", [$id]);
        if (!$release) {
            setFlash('error', 'Release not found');
            $this->redirect('/admin/releases');
            return;
        }

        $this->render('admin.releases.edit', [
            'title' => 'Edit Release v' . $release['version'],
            'admin' => $this->admin,
            'release' => $release
        ], 'admin');
    }

    /**
     * Update release
     */
    public function update(): void
    {
        $this->requireValidCSRF();

        $id = $this->post('id');
        $releaseNotes = trim($this->post('release_notes', ''));
        $changelog = trim($this->post('changelog', ''));
        $minPhpVersion = trim($this->post('min_php_version', '8.0'));
        $minEdition = $this->post('min_edition', 'S');
        $isActive = $this->post('is_active') ? 1 : 0;

        $release = $this->db->selectOne("SELECT * FROM releases WHERE id = ?", [$id]);
        if (!$release) {
            setFlash('error', 'Release not found');
            $this->redirect('/admin/releases');
            return;
        }

        // Handle file update if new file uploaded
        $filename = $release['update_file'];
        $fileHash = $release['file_hash'];
        $fileSize = $release['file_size'];

        if (!empty($_FILES['update_file']['name'])) {
            $uploadedFile = $_FILES['update_file'];
            $filename = 'apparix-' . $release['version'] . '.tar.gz';
            $uploadPath = BASE_PATH . '/storage/updates/' . $filename;

            if (move_uploaded_file($uploadedFile['tmp_name'], $uploadPath)) {
                $fileHash = hash_file('sha256', $uploadPath);
                $fileSize = filesize($uploadPath);
            }
        }

        $this->db->update(
            "UPDATE releases SET release_notes = ?, changelog = ?, min_php_version = ?, min_edition = ?, update_file = ?, file_hash = ?, file_size = ?, is_active = ? WHERE id = ?",
            [$releaseNotes, $changelog, $minPhpVersion, $minEdition, $filename, $fileHash, $fileSize, $isActive, $id]
        );

        $this->adminModel->logActivity($this->admin['admin_id'], 'update_release', 'release', $id, "Updated release v{$release['version']}");

        setFlash('success', 'Release updated successfully');
        $this->redirect('/admin/releases');
    }

    /**
     * Delete release
     */
    public function delete(): void
    {
        $this->requireValidCSRF();
        $id = $this->post('id');

        $release = $this->db->selectOne("SELECT * FROM releases WHERE id = ?", [$id]);
        if (!$release) {
            $this->json(['success' => false, 'error' => 'Release not found']);
            return;
        }

        // Delete the file
        $filePath = BASE_PATH . '/storage/updates/' . $release['update_file'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete from database
        $this->db->delete("DELETE FROM releases WHERE id = ?", [$id]);

        $this->adminModel->logActivity($this->admin['admin_id'], 'delete_release', 'release', $id, "Deleted release v{$release['version']}");

        $this->json(['success' => true]);
    }

    /**
     * View update logs
     */
    public function logs(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $logs = $this->db->select(
            "SELECT * FROM update_logs ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );

        $totalCount = $this->db->selectOne("SELECT COUNT(*) as count FROM update_logs")['count'];
        $totalPages = ceil($totalCount / $perPage);

        $this->render('admin.releases.logs', [
            'title' => 'Update Logs',
            'admin' => $this->admin,
            'logs' => $logs,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount
        ], 'admin');
    }
}
