<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Newsletter;
use App\Models\AdminUser;

class NewsletterController extends Controller
{
    private Newsletter $newsletterModel;
    private AdminUser $adminModel;
    protected ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminUser();
        $this->newsletterModel = new Newsletter();
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

    /**
     * Newsletter dashboard - list sent newsletters and subscribers
     */
    public function index(): void
    {
        $newsletters = $this->newsletterModel->getSentNewsletters(20);
        $subscribers = $this->newsletterModel->getAllSubscribers(50);
        $activeCount = $this->newsletterModel->countSubscribers(true);
        $totalCount = $this->newsletterModel->countSubscribers(false);

        $this->render('admin.newsletter.index', [
            'title' => 'Newsletter',
            'admin' => $this->admin,
            'newsletters' => $newsletters,
            'subscribers' => $subscribers,
            'activeCount' => $activeCount,
            'totalCount' => $totalCount
        ], 'admin');
    }

    /**
     * Compose new newsletter
     */
    public function compose(): void
    {
        $this->render('admin.newsletter.compose', [
            'title' => 'Compose Newsletter',
            'admin' => $this->admin
        ], 'admin');
    }

    /**
     * Preview newsletter (AJAX)
     */
    public function preview(): void
    {
        $content = $this->post('content', '');
        $subject = $this->post('subject', 'Newsletter Preview');

        // Load template with preview content
        $templatePath = BASE_PATH . '/newsletter/templates/newsletter.html';
        if (file_exists($templatePath)) {
            $html = file_get_contents($templatePath);
            $html = str_replace('{{CONTENT}}', $content, $html);
            $html = str_replace('{{SUBJECT}}', htmlspecialchars($subject), $html);
            $html = str_replace('{{UNSUBSCRIBE_URL}}', '#', $html);
            $html = str_replace('{{SUBSCRIBER_NAME}}', 'Preview User', $html);
        } else {
            $html = $this->getBasicTemplate($content);
        }

        echo $html;
    }

    /**
     * Send newsletter
     */
    public function send(): void
    {
        // Clear output buffers for clean JSON response
        if ($this->isAjaxRequest()) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json');
        }

        try {
            $this->requireValidCSRF();

            $subject = trim($this->post('subject', ''));
            $content = $this->post('content', '');

            if (empty($subject) || empty($content)) {
                if ($this->isAjaxRequest()) {
                    echo json_encode(['success' => false, 'error' => 'Subject and content are required']);
                    exit;
                } else {
                    setFlash('error', 'Subject and content are required');
                    $this->redirect('/admin/newsletter/compose');
                }
                return;
            }

            // Create newsletter record
            $newsletterId = $this->newsletterModel->createNewsletter($subject, $content, $this->admin['admin_id']);

            // Send to all subscribers
            $result = $this->newsletterModel->sendNewsletter($newsletterId);

            if ($this->isAjaxRequest()) {
                echo json_encode([
                    'success' => true,
                    'message' => "Newsletter sent to {$result['sent']} subscribers",
                    'sent' => $result['sent'],
                    'failed' => $result['failed']
                ]);
                exit;
            } else {
                setFlash('success', "Newsletter sent to {$result['sent']} subscribers");
                $this->redirect('/admin/newsletter');
            }
        } catch (\Throwable $e) {
            error_log('Newsletter send error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
                exit;
            } else {
                setFlash('error', 'An error occurred while sending the newsletter');
                $this->redirect('/admin/newsletter/compose');
            }
        }
    }

    /**
     * View newsletter details
     */
    public function view(): void
    {
        $id = (int) $this->get('id');
        $newsletter = $this->newsletterModel->getNewsletterById($id);

        if (!$newsletter) {
            setFlash('error', 'Newsletter not found');
            $this->redirect('/admin/newsletter');
            return;
        }

        $this->render('admin.newsletter.view', [
            'title' => 'View Newsletter',
            'admin' => $this->admin,
            'newsletter' => $newsletter
        ], 'admin');
    }

    /**
     * Delete a newsletter
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/newsletter');
            return;
        }

        $this->requireValidCSRF();

        $id = (int) $this->post('id');

        if ($this->newsletterModel->deleteNewsletter($id)) {
            setFlash('success', 'Newsletter deleted');
        } else {
            setFlash('error', 'Failed to delete newsletter');
        }

        $this->redirect('/admin/newsletter');
    }

    /**
     * Resend an existing newsletter
     */
    public function resend(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/newsletter');
            return;
        }

        $this->requireValidCSRF();

        $id = (int) $this->post('id');
        $newsletter = $this->newsletterModel->getNewsletterById($id);

        if (!$newsletter) {
            setFlash('error', 'Newsletter not found');
            $this->redirect('/admin/newsletter');
            return;
        }

        // Send to all subscribers
        $result = $this->newsletterModel->sendNewsletter($id);

        if ($result['success']) {
            setFlash('success', "Newsletter resent to {$result['sent']} subscribers" . ($result['failed'] > 0 ? " ({$result['failed']} failed)" : ""));
        } else {
            setFlash('error', $result['error'] ?? 'Failed to resend newsletter');
        }

        $this->redirect('/admin/newsletter');
    }

    /**
     * Subscribers management
     */
    public function subscribers(): void
    {
        $page = max(1, (int)$this->get('page', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $subscribers = $this->newsletterModel->getAllSubscribers($perPage, $offset);
        $totalCount = $this->newsletterModel->countSubscribers();
        $activeCount = $this->newsletterModel->countSubscribers(true);

        $this->render('admin.newsletter.subscribers', [
            'title' => 'Newsletter Subscribers',
            'admin' => $this->admin,
            'subscribers' => $subscribers,
            'totalCount' => $totalCount,
            'activeCount' => $activeCount,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalCount / $perPage)
        ], 'admin');
    }

    /**
     * Delete a subscriber
     */
    public function deleteSubscriber(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/newsletter/subscribers');
            return;
        }

        $this->requireValidCSRF();

        $id = (int) $this->post('id');

        if ($this->newsletterModel->deleteSubscriber($id)) {
            setFlash('success', 'Subscriber deleted');
        } else {
            setFlash('error', 'Failed to delete subscriber');
        }

        $this->redirect('/admin/newsletter/subscribers');
    }

    /**
     * Export subscribers as CSV
     */
    public function export(): void
    {
        $subscribers = $this->newsletterModel->getActiveSubscribers();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="newsletter_subscribers_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Email', 'First Name', 'Subscribed At', 'Source']);

        foreach ($subscribers as $sub) {
            fputcsv($output, [
                $sub['email'],
                $sub['first_name'] ?? '',
                $sub['subscribed_at'],
                $sub['source']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Basic email template
     */
    private function getBasicTemplate(string $content): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5;">
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="' . appUrl() . '/assets/images/placeholder.png" alt="' . appName() . '" style="max-width: 200px;">
    </div>
    <div style="padding: 30px; background: #fff; border-radius: 8px;">
        ' . $content . '
    </div>
    <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
        <p>You received this email because you subscribed to our newsletter.</p>
        <p><a href="#" style="color: #FF68C5;">Unsubscribe</a></p>
    </div>
</body>
</html>';
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
