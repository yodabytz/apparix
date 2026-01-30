<?php

namespace App\Controllers;

use App\Core\Controller;

class SplashController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Display the splash/coming soon page
     */
    public function index(): void
    {
        // Render splash page directly (no layout)
        $splashFile = BASE_PATH . '/app/Views/splash/index.php';
        require $splashFile;
    }
}
