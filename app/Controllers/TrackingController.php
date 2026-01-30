<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class TrackingController extends Controller
{
    /**
     * Order tracking page
     */
    public function index(): void
    {
        $orderNumber = $_GET['order'] ?? $_POST['order_number'] ?? null;
        $email = $_GET['email'] ?? $_POST['email'] ?? null;
        $order = null;
        $error = null;

        if ($orderNumber && $email) {
            $db = Database::getInstance();

            $order = $db->selectOne(
                "SELECT * FROM orders WHERE order_number = ? AND customer_email = ?",
                [trim($orderNumber), strtolower(trim($email))]
            );

            if (!$order) {
                $error = 'Order not found. Please check your order number and email address.';
            } else {
                // Get order items
                $order['items'] = $db->select(
                    "SELECT oi.*, p.name, p.slug,
                            (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY sort_order LIMIT 1) as image
                     FROM order_items oi
                     JOIN products p ON oi.product_id = p.id
                     WHERE oi.order_id = ?",
                    [$order['id']]
                );

                // Get status history
                $order['history'] = $db->select(
                    "SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC",
                    [$order['id']]
                );

                // Get tracking events if shipped
                if ($order['tracking_number']) {
                    $order['tracking_events'] = $db->select(
                        "SELECT * FROM tracking_events WHERE order_id = ? ORDER BY event_date DESC",
                        [$order['id']]
                    );
                }
            }
        }

        $this->render('tracking/index', [
            'title' => 'Track Your Order',
            'order' => $order,
            'error' => $error,
            'orderNumber' => $orderNumber,
            'email' => $email
        ]);
    }
}
