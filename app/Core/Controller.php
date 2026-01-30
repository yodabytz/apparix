<?php

namespace App\Core;

/**
 * Base Controller class for all controllers
 */
class Controller
{
    protected View $view;

    public function __construct()
    {
        $this->view = new View();
    }

    /**
     * Render view
     */
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $this->view->render($view, $data, $layout);
    }

    /**
     * Get JSON response
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        // Clean output buffer before JSON response
        while (ob_get_level()) {
            ob_end_clean();
        }
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to URL
     */
    protected function redirect(string $url): void
    {
        // Discard any output buffered content before redirect
        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Location: ' . $url, true, 302);
        exit;
    }

    /**
     * Get POST data (raw - escape on output, not input)
     */
    protected function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get GET data (raw - escape on output, not input)
     */
    protected function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    /**
     * Get current user ID
     */
    protected function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Verify CSRF token and die if invalid
     */
    protected function requireValidCSRF(): void
    {
        if (!CSRF::verifyPostToken()) {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

            // Discard output buffer before response
            if (ob_get_level()) {
                ob_end_clean();
            }

            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'CSRF token validation failed. Please refresh the page.', 'code' => 'CSRF_FAILED']);
                exit;
            }

            // For regular form submissions, redirect back with error message
            setFlash('error', 'Your session has expired. Please try again.');
            $referer = $_SERVER['HTTP_REFERER'] ?? '/';
            header('Location: ' . $referer, true, 302);
            exit;
        }
    }

    /**
     * Require authentication
     */
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
        }
    }

    /**
     * Require admin authentication
     */
    protected function requireAdmin(): void
    {
        if (empty($_SESSION['admin_id'])) {
            $this->redirect('/admin/login');
        }
    }
}
