<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
$qty = (int)($_POST['qty'] ?? 1);

switch ($action) {
    case 'add':
        if ($productId <= 0) { echo json_encode(['ok' => false, 'msg' => 'Invalid product']); exit; }
        // Check product exists & stock
        $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product) { echo json_encode(['ok' => false, 'msg' => 'Product not found']); exit; }
        
        $currentQty = $_SESSION['cart'][$productId] ?? 0;
        $newQty = $currentQty + $qty;
        if ($newQty > $product['stock_quantity']) {
            echo json_encode(['ok' => false, 'msg' => 'สินค้าในสต็อกไม่เพียงพอ']);
            exit;
        }
        $_SESSION['cart'][$productId] = $newQty;
        echo json_encode(['ok' => true, 'msg' => 'เพิ่มลงตะกร้าแล้ว', 'count' => array_sum($_SESSION['cart'])]);
        break;

    case 'remove':
        unset($_SESSION['cart'][$productId]);
        echo json_encode(['ok' => true, 'count' => array_sum($_SESSION['cart'])]);
        break;

    case 'update':
        if ($qty <= 0) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $_SESSION['cart'][$productId] = $qty;
        }
        echo json_encode(['ok' => true, 'count' => array_sum($_SESSION['cart'])]);
        break;

    case 'get':
        $items = [];
        $total = 0;
        foreach ($_SESSION['cart'] as $pid => $q) {
            $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
            $stmt->execute([$pid]);
            $p = $stmt->fetch();
            if ($p) {
                $subtotal = $p['price'] * $q;
                $total += $subtotal;
                $items[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'price' => (float)$p['price'],
                    'qty' => $q,
                    'subtotal' => $subtotal,
                    'image_url' => $p['image_url'] ?? '',
                    'category' => $p['category_name'],
                    'stock' => (int)$p['stock_quantity'],
                ];
            }
        }
        echo json_encode(['ok' => true, 'items' => $items, 'total' => $total, 'count' => array_sum($_SESSION['cart'])]);
        break;

    case 'clear':
        $_SESSION['cart'] = [];
        echo json_encode(['ok' => true, 'count' => 0]);
        break;

    case 'count':
        echo json_encode(['ok' => true, 'count' => array_sum($_SESSION['cart'])]);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
}
