<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ReCaptcha;
use App\Models\Newsletter;

class NewsletterController extends Controller
{
    private Newsletter $newsletterModel;

    public function __construct()
    {
        parent::__construct();
        $this->newsletterModel = new Newsletter();
    }

    /**
     * Subscribe to newsletter (AJAX)
     */
    public function subscribe(): void
    {
        $this->requireValidCSRF();

        // Verify reCAPTCHA
        $recaptchaToken = $this->post('recaptcha_token', '');
        if (!empty($recaptchaToken)) {
            $recaptchaResult = ReCaptcha::verify($recaptchaToken, 'newsletter_subscribe');
            if (!$recaptchaResult['success']) {
                $this->json(['success' => false, 'error' => 'Security verification failed. Please try again.']);
                return;
            }
        }

        $email = trim($this->post('email', ''));
        $firstName = trim($this->post('first_name', ''));
        $source = trim($this->post('source', 'website'));

        // Get user ID if logged in
        $userId = auth() ? auth()['id'] : null;

        $result = $this->newsletterModel->subscribe($email, $firstName ?: null, $userId, $source);

        $this->json($result);
    }

    /**
     * Unsubscribe from newsletter
     */
    public function unsubscribe(): void
    {
        $token = $this->get('token', '');

        if (empty($token)) {
            $this->render('newsletter/unsubscribe', [
                'title' => 'Unsubscribe',
                'error' => 'Invalid unsubscribe link'
            ]);
            return;
        }

        $result = $this->newsletterModel->unsubscribe($token);

        $this->render('newsletter/unsubscribe', [
            'title' => 'Unsubscribe',
            'success' => $result['success'],
            'message' => $result['message'] ?? $result['error'] ?? 'An error occurred'
        ]);
    }
}
