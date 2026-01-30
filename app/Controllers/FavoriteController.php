<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Favorite;

class FavoriteController extends Controller
{
    private Favorite $favoriteModel;

    public function __construct()
    {
        parent::__construct();
        $this->favoriteModel = new Favorite();
    }

    /**
     * Toggle favorite status (AJAX)
     */
    public function toggle(): void
    {
        $this->requireValidCSRF();

        $productId = (int)$this->post('product_id', 0);

        if ($productId <= 0) {
            $this->json(['success' => false, 'error' => 'Invalid product']);
            return;
        }

        $userId = auth() ? auth()['id'] : null;
        $sessionId = $userId ? null : session_id();

        $result = $this->favoriteModel->toggle($productId, $userId, $sessionId);
        $count = $this->favoriteModel->getCount($userId, $sessionId);

        $this->json([
            'success' => true,
            'favorited' => $result['favorited'],
            'count' => $count
        ]);
    }

    /**
     * Show favorites page
     */
    public function index(): void
    {
        $userId = auth() ? auth()['id'] : null;
        $sessionId = $userId ? null : session_id();

        $favorites = $this->favoriteModel->getAll($userId, $sessionId);

        $this->render('favorites/index', [
            'title' => 'My Favorites',
            'favorites' => $favorites
        ]);
    }

    /**
     * Get favorite IDs (AJAX) - for initializing hearts on page load
     */
    public function getIds(): void
    {
        $userId = auth() ? auth()['id'] : null;
        $sessionId = $userId ? null : session_id();

        $ids = $this->favoriteModel->getFavoriteIds($userId, $sessionId);

        $this->json([
            'success' => true,
            'ids' => $ids
        ]);
    }
}
