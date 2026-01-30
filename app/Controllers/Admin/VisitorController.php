<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminUser;
use App\Models\Visitor;

class VisitorController extends Controller
{
    private Visitor $visitorModel;
    private AdminUser $adminModel;
    protected ?array $admin = null;

    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminUser();
        $this->visitorModel = new Visitor();
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
     * Visitor analytics dashboard
     */
    public function index(): void
    {
        $period = $_GET['period'] ?? 'month';
        $validPeriods = ['today', 'yesterday', 'week', 'month', 'all'];
        if (!in_array($period, $validPeriods)) {
            $period = 'month';
        }

        // Human visitor stats (bots excluded)
        $stats = $this->visitorModel->getStatsSummary();
        $byCountry = $this->visitorModel->getByCountry($period, 20, false);
        $topReferrers = $this->visitorModel->getTopReferrers($period, 20, false);
        $topPages = $this->visitorModel->getTopPages($period, 20, false);
        $recentVisitors = $this->visitorModel->getRecent(50, false);

        // Chart data based on period
        $chartType = 'daily'; // daily or hourly
        if ($period === 'today') {
            $chartType = 'hourly';
            $chartStats = $this->visitorModel->getHourlyStats(false);
            $chartBotStats = $this->visitorModel->getHourlyStats(true);
            $chartDays = 1;
        } elseif ($period === 'yesterday') {
            $chartType = 'hourly';
            $chartStats = $this->visitorModel->getHourlyStats(false, 'yesterday');
            $chartBotStats = $this->visitorModel->getHourlyStats(true, 'yesterday');
            $chartDays = 1;
        } elseif ($period === 'week') {
            $chartStats = $this->visitorModel->getDailyStats(7, false);
            $chartBotStats = $this->visitorModel->getDailyStats(7, true);
            $chartDays = 7;
        } elseif ($period === 'all') {
            $chartStats = $this->visitorModel->getDailyStats(365, false);
            $chartBotStats = $this->visitorModel->getDailyStats(365, true);
            $chartDays = 365;
        } else {
            // month (default)
            $chartStats = $this->visitorModel->getDailyStats(30, false);
            $chartBotStats = $this->visitorModel->getDailyStats(30, true);
            $chartDays = 30;
        }

        // Bot stats
        $botStats = $this->visitorModel->getBotStatsSummary();
        $topBots = $this->visitorModel->getTopBots($period, 15);

        // Device, browser, and HTTP status stats
        $deviceStats = $this->visitorModel->getDeviceStats($period, 10, false);
        $browserStats = $this->visitorModel->getBrowserStats($period, 10, false);
        $httpStats = $this->visitorModel->getHttpStatusStats($period, 10);

        $this->render('admin.visitors.index', [
            'title' => 'Visitor Analytics',
            'admin' => $this->admin,
            'period' => $period,
            'stats' => $stats,
            'byCountry' => $byCountry,
            'topReferrers' => $topReferrers,
            'topPages' => $topPages,
            'chartStats' => $chartStats,
            'chartBotStats' => $chartBotStats,
            'chartType' => $chartType,
            'chartDays' => $chartDays,
            'recentVisitors' => $recentVisitors,
            'botStats' => $botStats,
            'topBots' => $topBots,
            'deviceStats' => $deviceStats,
            'browserStats' => $browserStats,
            'httpStats' => $httpStats
        ], 'admin');
    }
}
