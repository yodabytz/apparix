<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminUser;
use App\Models\Setting;

class SettingsController extends Controller
{
    private AdminUser $adminModel;
    private Setting $settingModel;
    private ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminUser();
        $this->settingModel = new Setting();
        $this->requireAdmin();
    }

    protected function requireAdmin(): void
    {
        $token = $_COOKIE['admin_token'] ?? null;
        if (!$token) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Authentication required'], 401);
                exit;
            }
            $this->redirect('/admin/login');
            exit;
        }

        $session = $this->adminModel->validateSession($token);
        if (!$session) {
            setcookie('admin_token', '', ['expires' => time() - 3600, 'path' => '/admin']);
            if ($this->isAjax()) {
                $this->json(['error' => 'Session expired'], 401);
                exit;
            }
            $this->redirect('/admin/login');
            exit;
        }

        $this->admin = $session;
    }

    /**
     * Store settings page
     */
    public function index(): void
    {
        $settings = [
            'store_name' => $this->settingModel->get('store_name', 'My Store'),
            'store_tagline' => $this->settingModel->get('store_tagline', ''),
            'store_email' => $this->settingModel->get('store_email', ''),
            'store_phone' => $this->settingModel->get('store_phone', ''),
            'store_logo' => $this->settingModel->get('store_logo', ''),
            'store_favicon' => $this->settingModel->get('store_favicon', ''),
            'store_currency' => $this->settingModel->get('store_currency', 'USD'),
            'store_currency_symbol' => $this->settingModel->get('store_currency_symbol', '$'),
            'show_powered_by' => $this->settingModel->get('show_powered_by', '1'),
            'maintenance_mode' => $this->settingModel->get('maintenance_mode', '0'),
            // SEO settings
            'seo_title' => $this->settingModel->get('seo_title', ''),
            'seo_description' => $this->settingModel->get('seo_description', ''),
            'seo_keywords' => $this->settingModel->get('seo_keywords', ''),
            'seo_og_image' => $this->settingModel->get('seo_og_image', ''),
            // Email settings
            'mail_from_email' => $this->settingModel->get('mail_from_email', ''),
            'mail_from_name' => $this->settingModel->get('mail_from_name', ''),
            'admin_notification_email' => $this->settingModel->get('admin_notification_email', ''),
            'smtp_enabled' => $this->settingModel->get('smtp_enabled', '0'),
            'smtp_host' => $this->settingModel->get('smtp_host', ''),
            'smtp_port' => $this->settingModel->get('smtp_port', '587'),
            'smtp_username' => $this->settingModel->get('smtp_username', ''),
            'smtp_password' => $this->settingModel->get('smtp_password', ''),
            'smtp_encryption' => $this->settingModel->get('smtp_encryption', 'tls'),
        ];

        $this->render('admin.settings.index', [
            'title' => 'Store Settings',
            'admin' => $this->admin,
            'settings' => $settings
        ], 'admin');
    }

    /**
     * Update store settings
     */
    public function update(): void
    {
        $this->requireValidCSRF();

        $fields = [
            'store_name' => 'string',
            'store_tagline' => 'string',
            'store_email' => 'string',
            'store_phone' => 'string',
            'store_currency' => 'string',
            'store_currency_symbol' => 'string',
        ];

        foreach ($fields as $field => $type) {
            $value = $this->post($field);
            if ($value !== null) {
                $this->settingModel->set($field, $value, $type, 'store', true);
            }
        }

        // Handle checkbox (boolean) settings - unchecked checkbox won't be in POST
        $showPoweredBy = $this->post('show_powered_by') ? '1' : '0';
        $this->settingModel->set('show_powered_by', $showPoweredBy, 'boolean', 'store', true);

        $maintenanceMode = $this->post('maintenance_mode') ? '1' : '0';
        $this->settingModel->set('maintenance_mode', $maintenanceMode, 'boolean', 'store', false);

        // Clear cache
        $this->settingModel->clearCache();

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'Settings updated']);
        } else {
            redirectWithFlash('/admin/settings', 'success', 'Settings saved successfully');
        }
    }

    /**
     * Upload store logo
     */
    public function uploadLogo(): void
    {
        $this->requireValidCSRF();

        if (empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
            ];

            $errorCode = $_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE;
            $errorMsg = $errorMessages[$errorCode] ?? 'Unknown upload error';

            $this->json(['error' => $errorMsg], 400);
            return;
        }

        $file = $_FILES['logo'];

        // Validate file type
        $allowedTypes = [
            'image/png',
            'image/jpeg',
            'image/jpg',
            'image/svg+xml',
            'image/webp',
            'image/gif'
        ];

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes)) {
            $this->json([
                'error' => 'Invalid file type. Allowed: PNG, JPG, SVG, WebP, GIF'
            ], 400);
            return;
        }

        // Validate file size (max 2MB)
        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $this->json(['error' => 'File too large. Maximum size is 2MB.'], 400);
            return;
        }

        // Get image dimensions (for non-SVG)
        $dimensions = null;
        $sizeWarning = null;
        if ($mimeType !== 'image/svg+xml') {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo) {
                $dimensions = [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1]
                ];

                // Suggest optimal dimensions (200-400px wide, 60-120px tall)
                if ($imageInfo[0] < 100 || $imageInfo[0] > 600) {
                    $sizeWarning = 'Recommended logo width: 200-400px';
                }
                if ($imageInfo[1] < 30 || $imageInfo[1] > 200) {
                    $sizeWarning = 'Recommended logo height: 60-120px';
                }
            }
        }

        // Create branding directory if needed
        $uploadDir = PUBLIC_PATH . '/assets/images/branding/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $this->json(['error' => 'Failed to create upload directory'], 500);
                return;
            }
        }

        // Generate filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png';
        $filename = 'logo-' . time() . '.' . strtolower($ext);
        $filePath = $uploadDir . $filename;

        // Delete old logo if exists
        $oldLogo = $this->settingModel->get('store_logo');
        if ($oldLogo) {
            $oldPath = PUBLIC_PATH . $oldLogo;
            if (file_exists($oldPath) && is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $webPath = '/assets/images/branding/' . $filename;
            $this->settingModel->set('store_logo', $webPath, 'file', 'store', true);
            $this->settingModel->clearCache();

            $response = [
                'success' => true,
                'path' => $webPath,
                'message' => 'Logo uploaded successfully'
            ];

            if ($dimensions) {
                $response['dimensions'] = $dimensions;
            }
            if ($sizeWarning) {
                $response['warning'] = $sizeWarning;
            }

            $this->json($response);
        } else {
            $this->json(['error' => 'Failed to save uploaded file'], 500);
        }
    }

    /**
     * Remove logo
     */
    public function removeLogo(): void
    {
        $this->requireValidCSRF();

        $currentLogo = $this->settingModel->get('store_logo');

        if ($currentLogo) {
            $path = PUBLIC_PATH . $currentLogo;
            if (file_exists($path) && is_file($path)) {
                unlink($path);
            }
        }

        $this->settingModel->set('store_logo', '', 'file', 'store', true);
        $this->settingModel->clearCache();

        $this->json(['success' => true, 'message' => 'Logo removed']);
    }

    /**
     * Update SEO settings
     */
    public function updateSeo(): void
    {
        $this->requireValidCSRF();

        $seoTitle = trim($this->post('seo_title', ''));
        $seoDescription = trim($this->post('seo_description', ''));
        $seoKeywords = trim($this->post('seo_keywords', ''));

        // Limit lengths
        $seoTitle = mb_substr($seoTitle, 0, 70);
        $seoDescription = mb_substr($seoDescription, 0, 160);
        $seoKeywords = mb_substr($seoKeywords, 0, 255);

        $this->settingModel->set('seo_title', $seoTitle, 'string', 'store', true);
        $this->settingModel->set('seo_description', $seoDescription, 'string', 'store', true);
        $this->settingModel->set('seo_keywords', $seoKeywords, 'string', 'store', true);

        $this->settingModel->clearCache();

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'SEO settings updated']);
        } else {
            redirectWithFlash('/admin/settings', 'success', 'SEO settings saved successfully');
        }
    }

    /**
     * Upload OG image for social sharing
     */
    public function uploadOgImage(): void
    {
        $this->requireValidCSRF();

        if (empty($_FILES['og_image']) || $_FILES['og_image']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'No file uploaded'], 400);
            return;
        }

        $file = $_FILES['og_image'];

        // Validate file type
        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes)) {
            $this->json(['error' => 'Invalid file type. Use PNG, JPG, or WebP.'], 400);
            return;
        }

        // Validate file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            $this->json(['error' => 'File too large. Maximum size is 2MB.'], 400);
            return;
        }

        // Create directory if needed
        $uploadDir = PUBLIC_PATH . '/assets/images/branding/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'og-image-' . time() . '.' . strtolower($ext);
        $filePath = $uploadDir . $filename;

        // Delete old OG image if exists
        $oldImage = $this->settingModel->get('seo_og_image');
        if ($oldImage) {
            $oldPath = PUBLIC_PATH . $oldImage;
            if (file_exists($oldPath) && is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $webPath = '/assets/images/branding/' . $filename;
            $this->settingModel->set('seo_og_image', $webPath, 'file', 'store', true);
            $this->settingModel->clearCache();

            $this->json([
                'success' => true,
                'path' => $webPath,
                'message' => 'Social image uploaded successfully'
            ]);
        } else {
            $this->json(['error' => 'Failed to save uploaded file'], 500);
        }
    }

    /**
     * Remove OG image
     */
    public function removeOgImage(): void
    {
        $this->requireValidCSRF();

        $currentImage = $this->settingModel->get('seo_og_image');

        if ($currentImage) {
            $path = PUBLIC_PATH . $currentImage;
            if (file_exists($path) && is_file($path)) {
                unlink($path);
            }
        }

        $this->settingModel->set('seo_og_image', '', 'file', 'store', true);
        $this->settingModel->clearCache();

        $this->json(['success' => true, 'message' => 'Social image removed']);
    }

    /**
     * Analytics & Integrations settings page
     */
    public function integrations(): void
    {
        $settings = [
            'google_tag_manager_id' => $this->settingModel->get('google_tag_manager_id', ''),
            'google_analytics_id' => $this->settingModel->get('google_analytics_id', ''),
            'google_adsense_id' => $this->settingModel->get('google_adsense_id', ''),
            'google_ads_conversion_id' => $this->settingModel->get('google_ads_conversion_id', ''),
            'facebook_pixel_id' => $this->settingModel->get('facebook_pixel_id', ''),
            'tiktok_pixel_id' => $this->settingModel->get('tiktok_pixel_id', ''),
            'pinterest_tag_id' => $this->settingModel->get('pinterest_tag_id', ''),
            'snapchat_pixel_id' => $this->settingModel->get('snapchat_pixel_id', ''),
            'microsoft_uet_tag_id' => $this->settingModel->get('microsoft_uet_tag_id', ''),
            'custom_head_scripts' => $this->settingModel->get('custom_head_scripts', ''),
            'custom_body_scripts' => $this->settingModel->get('custom_body_scripts', ''),
        ];

        $this->render('admin.settings.integrations', [
            'title' => 'Analytics & Integrations',
            'admin' => $this->admin,
            'settings' => $settings
        ], 'admin');
    }

    /**
     * Update analytics/integration settings
     */
    public function updateIntegrations(): void
    {
        $this->requireValidCSRF();

        $fields = [
            'google_tag_manager_id',
            'google_analytics_id',
            'google_adsense_id',
            'google_ads_conversion_id',
            'facebook_pixel_id',
            'tiktok_pixel_id',
            'pinterest_tag_id',
            'snapchat_pixel_id',
            'microsoft_uet_tag_id',
            'custom_head_scripts',
            'custom_body_scripts',
        ];

        foreach ($fields as $field) {
            $value = $this->post($field);
            if ($value !== null) {
                // Sanitize ID fields (alphanumeric and dashes only for IDs)
                if (str_ends_with($field, '_id')) {
                    $value = preg_replace('/[^a-zA-Z0-9\-_]/', '', $value);
                }
                $this->settingModel->set($field, $value, 'string', 'integrations', false);
            }
        }

        // Clear cache
        $this->settingModel->clearCache();

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'Integration settings updated']);
        } else {
            redirectWithFlash('/admin/settings/integrations', 'success', 'Settings saved successfully');
        }
    }

    /**
     * Upload favicon
     */
    public function uploadFavicon(): void
    {
        $this->requireValidCSRF();

        if (empty($_FILES['favicon']) || $_FILES['favicon']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'No file uploaded'], 400);
            return;
        }

        $file = $_FILES['favicon'];

        // Validate file type
        $allowedTypes = ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/ico'];

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes) && !str_ends_with($file['name'], '.ico')) {
            $this->json(['error' => 'Invalid file type. Use PNG or ICO format.'], 400);
            return;
        }

        // Validate file size (max 500KB)
        if ($file['size'] > 512 * 1024) {
            $this->json(['error' => 'File too large. Maximum size is 500KB.'], 400);
            return;
        }

        // Create branding directory if needed
        $uploadDir = PUBLIC_PATH . '/assets/images/branding/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png';
        $filename = 'favicon-' . time() . '.' . strtolower($ext);
        $filePath = $uploadDir . $filename;

        // Delete old favicon if exists
        $oldFavicon = $this->settingModel->get('store_favicon');
        if ($oldFavicon) {
            $oldPath = PUBLIC_PATH . $oldFavicon;
            if (file_exists($oldPath) && is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $webPath = '/assets/images/branding/' . $filename;
            $this->settingModel->set('store_favicon', $webPath, 'file', 'store', true);
            $this->settingModel->clearCache();

            $this->json([
                'success' => true,
                'path' => $webPath,
                'message' => 'Favicon uploaded successfully'
            ]);
        } else {
            $this->json(['error' => 'Failed to save uploaded file'], 500);
        }
    }

    /**
     * Hero section settings page
     */
    public function hero(): void
    {
        // Get current taglines as array (Setting model auto-decodes JSON)
        $taglines = $this->settingModel->get('hero_taglines', []);
        if (is_string($taglines)) {
            $taglines = json_decode($taglines, true) ?: [];
        }
        if (!is_array($taglines)) {
            $taglines = [];
        }

        $settings = [
            'hero_heading' => $this->settingModel->get('hero_heading', 'Welcome to {store_name}'),
            'hero_taglines' => $taglines,
            'hero_cta_text' => $this->settingModel->get('hero_cta_text', 'Shop Now'),
            'hero_cta_url' => $this->settingModel->get('hero_cta_url', '/products'),
            'hero_background_style' => $this->settingModel->get('hero_background_style', 'gradient-dark'),
            'hero_show_glow' => $this->settingModel->get('hero_show_glow', '1'),
            'hero_show_shimmer' => $this->settingModel->get('hero_show_shimmer', '1'),
            'hero_rotate_taglines' => $this->settingModel->get('hero_rotate_taglines', '1'),
            'hero_tagline_interval' => $this->settingModel->get('hero_tagline_interval', '8'),
            'hero_overlay_opacity' => $this->settingModel->get('hero_overlay_opacity', '0.12'),
        ];

        // Available background styles
        $backgroundStyles = [
            'gradient-dark' => 'Dark Gradient (Default)',
            'gradient-brand' => 'Brand Color Gradient',
            'gradient-light' => 'Light Elegant',
            'solid-dark' => 'Solid Dark',
            'solid-brand' => 'Solid Brand Color',
            'image' => 'Background Image',
        ];

        $this->render('admin.settings.hero', [
            'title' => 'Hero Section Settings',
            'admin' => $this->admin,
            'settings' => $settings,
            'backgroundStyles' => $backgroundStyles
        ], 'admin');
    }

    /**
     * Update hero section settings
     */
    public function updateHero(): void
    {
        $this->requireValidCSRF();

        // Text settings
        $heading = $this->post('hero_heading', 'Welcome to {store_name}');
        $ctaText = $this->post('hero_cta_text', 'Shop Now');
        $ctaUrl = $this->post('hero_cta_url', '/products');

        // Process taglines (one per line)
        $taglinesRaw = $this->post('hero_taglines', '');
        $taglines = array_filter(array_map('trim', explode("\n", $taglinesRaw)));
        if (empty($taglines)) {
            $taglines = ['Discover quality products curated just for you'];
        }

        // Visual settings
        $backgroundStyle = $this->post('hero_background_style', 'gradient-dark');
        $showGlow = $this->post('hero_show_glow') ? '1' : '0';
        $showShimmer = $this->post('hero_show_shimmer') ? '1' : '0';
        $rotateTaglines = $this->post('hero_rotate_taglines') ? '1' : '0';
        $taglineInterval = max(3, min(30, (int)$this->post('hero_tagline_interval', 8)));
        $overlayOpacity = $this->post('hero_overlay_opacity', '0.12');

        // Save settings
        $this->settingModel->set('hero_heading', $heading, 'string', 'store', true);
        $this->settingModel->set('hero_taglines', json_encode(array_values($taglines)), 'json', 'store', true);
        $this->settingModel->set('hero_cta_text', $ctaText, 'string', 'store', true);
        $this->settingModel->set('hero_cta_url', $ctaUrl, 'string', 'store', true);
        $this->settingModel->set('hero_background_style', $backgroundStyle, 'string', 'theme', true);
        $this->settingModel->set('hero_show_glow', $showGlow, 'boolean', 'theme', true);
        $this->settingModel->set('hero_show_shimmer', $showShimmer, 'boolean', 'theme', true);
        $this->settingModel->set('hero_rotate_taglines', $rotateTaglines, 'boolean', 'theme', true);
        $this->settingModel->set('hero_tagline_interval', (string)$taglineInterval, 'integer', 'theme', true);
        $this->settingModel->set('hero_overlay_opacity', $overlayOpacity, 'string', 'theme', true);

        $this->settingModel->clearCache();

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'Hero settings updated']);
        } else {
            redirectWithFlash('/admin/settings/hero', 'success', 'Hero settings saved successfully');
        }
    }

    /**
     * Upload hero background image
     */
    public function uploadHeroImage(): void
    {
        $this->requireValidCSRF();

        if (empty($_FILES['hero_image']) || $_FILES['hero_image']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'No file uploaded'], 400);
            return;
        }

        $file = $_FILES['hero_image'];

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes)) {
            $this->json(['error' => 'Invalid file type. Use JPG, PNG, or WebP.'], 400);
            return;
        }

        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            $this->json(['error' => 'File too large. Maximum size is 5MB.'], 400);
            return;
        }

        $uploadDir = PUBLIC_PATH . '/assets/images/branding/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'hero-bg-' . time() . '.' . strtolower($ext);
        $filePath = $uploadDir . $filename;

        // Delete old hero image if exists
        $oldImage = $this->settingModel->get('hero_background_image');
        if ($oldImage) {
            $oldPath = PUBLIC_PATH . $oldImage;
            if (file_exists($oldPath) && is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $webPath = '/assets/images/branding/' . $filename;
            $this->settingModel->set('hero_background_image', $webPath, 'file', 'theme', true);
            $this->settingModel->clearCache();

            $this->json([
                'success' => true,
                'path' => $webPath,
                'message' => 'Hero background uploaded'
            ]);
        } else {
            $this->json(['error' => 'Failed to save uploaded file'], 500);
        }
    }

    /**
     * Social links settings page
     */
    public function social(): void
    {
        $settings = [
            'social_facebook' => $this->settingModel->get('social_facebook', ''),
            'social_instagram' => $this->settingModel->get('social_instagram', ''),
            'social_twitter' => $this->settingModel->get('social_twitter', ''),
            'social_tiktok' => $this->settingModel->get('social_tiktok', ''),
            'social_youtube' => $this->settingModel->get('social_youtube', ''),
            'social_pinterest' => $this->settingModel->get('social_pinterest', ''),
            'social_linkedin' => $this->settingModel->get('social_linkedin', ''),
            'social_etsy' => $this->settingModel->get('social_etsy', ''),
            'social_amazon' => $this->settingModel->get('social_amazon', ''),
            'social_discord' => $this->settingModel->get('social_discord', ''),
            'social_threads' => $this->settingModel->get('social_threads', ''),
        ];

        $this->render('admin.settings.social', [
            'title' => 'Social Media Links',
            'admin' => $this->admin,
            'settings' => $settings
        ], 'admin');
    }

    /**
     * Update social links settings
     */
    public function updateSocial(): void
    {
        $this->requireValidCSRF();

        $fields = [
            'social_facebook',
            'social_instagram',
            'social_twitter',
            'social_tiktok',
            'social_youtube',
            'social_pinterest',
            'social_linkedin',
            'social_etsy',
            'social_amazon',
            'social_discord',
            'social_threads',
        ];

        foreach ($fields as $field) {
            $value = trim($this->post($field, ''));
            // Basic URL validation - allow empty or valid URLs
            if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                // Try prepending https:// if missing
                if (!preg_match('/^https?:\/\//', $value)) {
                    $value = 'https://' . $value;
                }
            }
            $this->settingModel->set($field, $value, 'string', 'store', true);
        }

        $this->settingModel->clearCache();

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'Social links updated']);
        } else {
            redirectWithFlash('/admin/settings/social', 'success', 'Social links saved successfully');
        }
    }

    /**
     * Payment settings page
     */
    public function payments(): void
    {
        $settings = [
            'stripe_mode' => $this->settingModel->get('stripe_mode', 'test'),
            'stripe_test_public_key' => $this->settingModel->get('stripe_test_public_key', ''),
            'stripe_test_secret_key' => $this->settingModel->get('stripe_test_secret_key', ''),
            'stripe_live_public_key' => $this->settingModel->get('stripe_live_public_key', ''),
            'stripe_live_secret_key' => $this->settingModel->get('stripe_live_secret_key', ''),
            'stripe_webhook_secret' => $this->settingModel->get('stripe_webhook_secret', ''),
            'paypal_enabled' => $this->settingModel->get('paypal_enabled', '0'),
            'paypal_client_id' => $this->settingModel->get('paypal_client_id', ''),
            'paypal_secret' => $this->settingModel->get('paypal_secret', ''),
            'paypal_mode' => $this->settingModel->get('paypal_mode', 'sandbox'),
        ];

        $this->render('admin.settings.payments', [
            'title' => 'Payment Settings',
            'admin' => $this->admin,
            'settings' => $settings
        ], 'admin');
    }

    /**
     * Update payment settings
     */
    public function updatePayments(): void
    {
        $this->requireValidCSRF();

        // Stripe settings
        $stripeMode = $this->post('stripe_mode', 'test');
        if (!in_array($stripeMode, ['test', 'live'])) {
            $stripeMode = 'test';
        }

        $this->settingModel->set('stripe_mode', $stripeMode, 'string', 'integrations', false);
        $this->settingModel->set('stripe_test_public_key', $this->post('stripe_test_public_key', ''), 'string', 'integrations', false);
        $this->settingModel->set('stripe_test_secret_key', $this->post('stripe_test_secret_key', ''), 'string', 'integrations', false);
        $this->settingModel->set('stripe_live_public_key', $this->post('stripe_live_public_key', ''), 'string', 'integrations', false);
        $this->settingModel->set('stripe_live_secret_key', $this->post('stripe_live_secret_key', ''), 'string', 'integrations', false);
        $this->settingModel->set('stripe_webhook_secret', $this->post('stripe_webhook_secret', ''), 'string', 'integrations', false);

        // PayPal settings
        $paypalEnabled = $this->post('paypal_enabled') ? '1' : '0';
        $paypalMode = $this->post('paypal_mode', 'sandbox');
        if (!in_array($paypalMode, ['sandbox', 'live'])) {
            $paypalMode = 'sandbox';
        }

        $this->settingModel->set('paypal_enabled', $paypalEnabled, 'boolean', 'integrations', false);
        $this->settingModel->set('paypal_client_id', $this->post('paypal_client_id', ''), 'string', 'integrations', false);
        $this->settingModel->set('paypal_secret', $this->post('paypal_secret', ''), 'string', 'integrations', false);
        $this->settingModel->set('paypal_mode', $paypalMode, 'string', 'integrations', false);

        $this->settingModel->clearCache();

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'Payment settings updated']);
        } else {
            redirectWithFlash('/admin/settings/payments', 'success', 'Payment settings saved successfully');
        }
    }

    /**
     * Update email settings
     */
    public function updateEmail(): void
    {
        $this->requireValidCSRF();

        // Basic email settings
        $mailFromEmail = trim($this->post('mail_from_email', ''));
        $mailFromName = trim($this->post('mail_from_name', ''));
        $adminEmail = trim($this->post('admin_notification_email', ''));

        // Validate email addresses if provided
        if (!empty($mailFromEmail) && !filter_var($mailFromEmail, FILTER_VALIDATE_EMAIL)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Invalid "From" email address'], 400);
            } else {
                redirectWithFlash('/admin/settings', 'error', 'Invalid "From" email address');
            }
            return;
        }

        if (!empty($adminEmail) && !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Invalid admin notification email address'], 400);
            } else {
                redirectWithFlash('/admin/settings', 'error', 'Invalid admin notification email address');
            }
            return;
        }

        $this->settingModel->set('mail_from_email', $mailFromEmail, 'string', 'email', false);
        $this->settingModel->set('mail_from_name', $mailFromName, 'string', 'email', false);
        $this->settingModel->set('admin_notification_email', $adminEmail, 'string', 'email', false);

        // SMTP settings
        $smtpEnabled = $this->post('smtp_enabled') ? '1' : '0';
        $smtpHost = trim($this->post('smtp_host', ''));
        $smtpPort = trim($this->post('smtp_port', '587'));
        $smtpUsername = trim($this->post('smtp_username', ''));
        $smtpPassword = $this->post('smtp_password', '');
        $smtpEncryption = $this->post('smtp_encryption', 'tls');

        // Validate encryption type
        if (!in_array($smtpEncryption, ['none', 'tls', 'ssl'])) {
            $smtpEncryption = 'tls';
        }

        // Validate port
        $smtpPort = max(1, min(65535, (int)$smtpPort));

        $this->settingModel->set('smtp_enabled', $smtpEnabled, 'boolean', 'email', false);
        $this->settingModel->set('smtp_host', $smtpHost, 'string', 'email', false);
        $this->settingModel->set('smtp_port', (string)$smtpPort, 'string', 'email', false);
        $this->settingModel->set('smtp_username', $smtpUsername, 'string', 'email', false);

        // Only update password if it's not the placeholder
        if ($smtpPassword !== '********' && $smtpPassword !== '') {
            $this->settingModel->set('smtp_password', $smtpPassword, 'string', 'email', false);
        }

        $this->settingModel->set('smtp_encryption', $smtpEncryption, 'string', 'email', false);

        $this->settingModel->clearCache();

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'Email settings updated']);
        } else {
            redirectWithFlash('/admin/settings', 'success', 'Email settings saved successfully');
        }
    }

    /**
     * Send a test email
     */
    public function testEmail(): void
    {
        $this->requireValidCSRF();

        $testTo = trim($this->post('test_email', ''));

        if (empty($testTo) || !filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
            $this->json(['error' => 'Please enter a valid email address'], 400);
            return;
        }

        // Get email settings
        $fromEmail = $this->settingModel->get('mail_from_email', '');
        $fromName = $this->settingModel->get('mail_from_name', '');
        $smtpEnabled = $this->settingModel->get('smtp_enabled', '0') === '1';

        if (empty($fromEmail)) {
            $fromEmail = 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }
        if (empty($fromName)) {
            $fromName = $this->settingModel->get('store_name', 'Apparix');
        }

        $subject = 'Test Email from ' . $fromName;
        $body = "This is a test email sent from your Apparix store.\n\n";
        $body .= "If you received this email, your email settings are configured correctly!\n\n";
        $body .= "Settings used:\n";
        $body .= "- From: {$fromName} <{$fromEmail}>\n";
        $body .= "- Method: " . ($smtpEnabled ? 'SMTP' : 'PHP mail()') . "\n";
        $body .= "\n--\nSent at: " . date('Y-m-d H:i:s');

        try {
            if ($smtpEnabled) {
                // Use SMTP
                $smtpHost = $this->settingModel->get('smtp_host', '');
                $smtpPort = (int)$this->settingModel->get('smtp_port', '587');
                $smtpUsername = $this->settingModel->get('smtp_username', '');
                $smtpPassword = $this->settingModel->get('smtp_password', '');
                $smtpEncryption = $this->settingModel->get('smtp_encryption', 'tls');

                $result = $this->sendSmtpEmail($testTo, $subject, $body, $fromEmail, $fromName, [
                    'host' => $smtpHost,
                    'port' => $smtpPort,
                    'username' => $smtpUsername,
                    'password' => $smtpPassword,
                    'encryption' => $smtpEncryption
                ]);
            } else {
                // Use PHP mail()
                $headers = [
                    'From' => "{$fromName} <{$fromEmail}>",
                    'Reply-To' => $fromEmail,
                    'X-Mailer' => 'PHP/' . phpversion(),
                    'Content-Type' => 'text/plain; charset=UTF-8'
                ];

                $result = mail($testTo, $subject, $body, $headers);
            }

            if ($result) {
                $this->json(['success' => true, 'message' => 'Test email sent successfully to ' . $testTo]);
            } else {
                $this->json(['error' => 'Failed to send test email. Check your server mail configuration.'], 500);
            }
        } catch (\Exception $e) {
            error_log("Test email error: " . $e->getMessage());
            $this->json(['error' => 'Failed to send: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Send email via SMTP
     */
    private function sendSmtpEmail(string $to, string $subject, string $body, string $fromEmail, string $fromName, array $smtp): bool
    {
        $host = $smtp['host'];
        $port = $smtp['port'];
        $username = $smtp['username'];
        $password = $smtp['password'];
        $encryption = $smtp['encryption'];

        // Build connection string
        $protocol = '';
        if ($encryption === 'ssl') {
            $protocol = 'ssl://';
        } elseif ($encryption === 'tls') {
            $protocol = 'tls://';
        }

        $errno = 0;
        $errstr = '';
        $timeout = 30;

        // For TLS, we connect without encryption first, then upgrade
        if ($encryption === 'tls') {
            $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        } else {
            $socket = @fsockopen($protocol . $host, $port, $errno, $errstr, $timeout);
        }

        if (!$socket) {
            throw new \Exception("Could not connect to SMTP server: {$errstr} ({$errno})");
        }

        // Set timeout
        stream_set_timeout($socket, $timeout);

        // Read greeting
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            throw new \Exception("SMTP Error: " . trim($response));
        }

        // EHLO
        fputs($socket, "EHLO " . gethostname() . "\r\n");
        $this->readSmtpResponse($socket);

        // STARTTLS for TLS connections
        if ($encryption === 'tls') {
            fputs($socket, "STARTTLS\r\n");
            $response = $this->readSmtpResponse($socket);
            if (substr($response, 0, 3) !== '220') {
                fclose($socket);
                throw new \Exception("STARTTLS failed: " . trim($response));
            }

            // Upgrade to TLS
            $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$crypto) {
                fclose($socket);
                throw new \Exception("Could not enable TLS encryption");
            }

            // EHLO again after TLS
            fputs($socket, "EHLO " . gethostname() . "\r\n");
            $this->readSmtpResponse($socket);
        }

        // AUTH LOGIN
        if (!empty($username) && !empty($password)) {
            fputs($socket, "AUTH LOGIN\r\n");
            $response = $this->readSmtpResponse($socket);
            if (substr($response, 0, 3) !== '334') {
                fclose($socket);
                throw new \Exception("AUTH LOGIN failed: " . trim($response));
            }

            fputs($socket, base64_encode($username) . "\r\n");
            $response = $this->readSmtpResponse($socket);
            if (substr($response, 0, 3) !== '334') {
                fclose($socket);
                throw new \Exception("Username rejected: " . trim($response));
            }

            fputs($socket, base64_encode($password) . "\r\n");
            $response = $this->readSmtpResponse($socket);
            if (substr($response, 0, 3) !== '235') {
                fclose($socket);
                throw new \Exception("Authentication failed: " . trim($response));
            }
        }

        // MAIL FROM
        fputs($socket, "MAIL FROM:<{$fromEmail}>\r\n");
        $response = $this->readSmtpResponse($socket);
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            throw new \Exception("MAIL FROM rejected: " . trim($response));
        }

        // RCPT TO
        fputs($socket, "RCPT TO:<{$to}>\r\n");
        $response = $this->readSmtpResponse($socket);
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            throw new \Exception("RCPT TO rejected: " . trim($response));
        }

        // DATA
        fputs($socket, "DATA\r\n");
        $response = $this->readSmtpResponse($socket);
        if (substr($response, 0, 3) !== '354') {
            fclose($socket);
            throw new \Exception("DATA command failed: " . trim($response));
        }

        // Email headers and body
        $email = "From: {$fromName} <{$fromEmail}>\r\n";
        $email .= "To: {$to}\r\n";
        $email .= "Subject: {$subject}\r\n";
        $email .= "MIME-Version: 1.0\r\n";
        $email .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $email .= "\r\n";
        $email .= $body;
        $email .= "\r\n.\r\n";

        fputs($socket, $email);
        $response = $this->readSmtpResponse($socket);
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            throw new \Exception("Message rejected: " . trim($response));
        }

        // QUIT
        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return true;
    }

    /**
     * Read SMTP response
     */
    private function readSmtpResponse($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            // Check if this is the last line (no hyphen after code)
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }
}
