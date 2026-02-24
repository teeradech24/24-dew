<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_promotions':
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("
            SELECT p.*, pr.image_url as product_image, pr.name as product_name
            FROM promotions p
            LEFT JOIN products pr ON p.image_product_id = pr.id
            WHERE p.is_active = 1
            AND (p.start_date IS NULL OR p.start_date <= ?)
            AND (p.end_date IS NULL OR p.end_date >= ?)
            ORDER BY p.sort_order ASC
        ");
        $stmt->execute([$now, $now]);
        echo json_encode(['ok' => true, 'promotions' => $stmt->fetchAll()]);
        break;

    case 'get_flash_sales':
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("
            SELECT fs.*, p.name as product_name, p.image_url, p.stock_quantity,
                   c.name as category_name
            FROM flash_sales fs
            JOIN products p ON fs.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            WHERE fs.is_active = 1 AND fs.end_date >= ? AND fs.start_date <= ?
            ORDER BY fs.end_date ASC
        ");
        $stmt->execute([$now, $now]);
        $sales = $stmt->fetchAll();
        
        // Calculate remaining time
        foreach ($sales as &$s) {
            $end = strtotime($s['end_date']);
            $s['remaining_seconds'] = max(0, $end - time());
            $s['progress'] = $s['quantity_limit'] > 0 ? round(($s['quantity_sold'] / $s['quantity_limit']) * 100) : 0;
        }
        echo json_encode(['ok' => true, 'flash_sales' => $sales]);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
}
