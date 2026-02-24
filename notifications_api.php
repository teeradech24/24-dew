<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get':
        $notifs = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 20")->fetchAll();
        $unread = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();
        echo json_encode(['ok' => true, 'notifications' => $notifs, 'unread' => (int)$unread]);
        break;

    case 'unread_count':
        $count = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();
        echo json_encode(['ok' => true, 'count' => (int)$count]);
        break;

    case 'mark_read':
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?")->execute([$id]);
        }
        echo json_encode(['ok' => true]);
        break;

    case 'mark_all_read':
        $pdo->exec("UPDATE notifications SET is_read = 1");
        echo json_encode(['ok' => true]);
        break;

    case 'check_stock':
        // Auto-create notifications for low stock products
        $lowStock = $pdo->query("SELECT id, name, stock_quantity FROM products WHERE stock_quantity > 0 AND stock_quantity < 5")->fetchAll();
        $created = 0;
        foreach ($lowStock as $p) {
            // Check if we already have a recent notification for this product
            $stmt = $pdo->prepare("SELECT id FROM notifications WHERE type = 'stock_low' AND message LIKE ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
            $stmt->execute(['%' . $p['name'] . '%']);
            if (!$stmt->fetch()) {
                $pdo->prepare("INSERT INTO notifications (type, title, message) VALUES ('stock_low', ?, ?)")
                    ->execute(["⚠️ สินค้าใกล้หมด", $p['name'] . " เหลือเพียง " . $p['stock_quantity'] . " ชิ้น"]);
                $created++;
            }
        }
        echo json_encode(['ok' => true, 'created' => $created]);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
}
