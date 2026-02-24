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

    case 'validate_coupon':
        $code = strtoupper(trim($_POST['code'] ?? ''));
        if (empty($code)) { echo json_encode(['ok' => false, 'msg' => 'กรุณาใส่โค้ดคูปอง']); exit; }
        
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch();
        
        if (!$coupon) { echo json_encode(['ok' => false, 'msg' => 'ไม่พบคูปองนี้']); exit; }
        if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) { echo json_encode(['ok' => false, 'msg' => 'คูปองหมดอายุแล้ว']); exit; }
        if ($coupon['max_uses'] && $coupon['used_count'] >= $coupon['max_uses']) { echo json_encode(['ok' => false, 'msg' => 'คูปองถูกใช้ครบแล้ว']); exit; }
        
        // Calculate cart total
        $cartTotal = 0;
        foreach ($_SESSION['cart'] as $pid => $q) {
            $s = $pdo->prepare("SELECT price FROM products WHERE id = ?"); $s->execute([$pid]);
            $p = $s->fetch(); if ($p) $cartTotal += $p['price'] * $q;
        }
        if ($cartTotal < $coupon['min_order']) { echo json_encode(['ok' => false, 'msg' => 'ยอดขั้นต่ำ ฿' . number_format($coupon['min_order'], 2)]); exit; }
        
        $discount = $coupon['discount_type'] === 'percent' ? ($cartTotal * $coupon['discount_value'] / 100) : $coupon['discount_value'];
        $discount = min($discount, $cartTotal);
        
        echo json_encode([
            'ok' => true,
            'code' => $coupon['code'],
            'type' => $coupon['discount_type'],
            'value' => (float)$coupon['discount_value'],
            'discount' => $discount,
            'label' => $coupon['discount_type'] === 'percent' ? $coupon['discount_value'] . '%' : '฿' . number_format($coupon['discount_value'], 2),
            'new_total' => $cartTotal - $discount,
        ]);
        break;

    case 'checkout':
        if (empty($_SESSION['cart'])) {
            echo json_encode(['ok' => false, 'msg' => 'ตะกร้าว่างเปล่า']);
            exit;
        }
        try {
            $pdo->beginTransaction();
            $orderNum = 'GP-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $total = 0;
            $orderItems = [];

            foreach ($_SESSION['cart'] as $pid => $q) {
                $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id = ?");
                $stmt->execute([$pid]);
                $p = $stmt->fetch();
                if (!$p) continue;
                $sub = $p['price'] * $q;
                $total += $sub;
                $orderItems[] = ['pid' => $pid, 'name' => $p['name'], 'price' => $p['price'], 'qty' => $q, 'sub' => $sub];
            }

            // Apply coupon
            $discount = 0;
            $couponCode = trim($_POST['coupon_code'] ?? '');
            if ($couponCode) {
                $cs = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
                $cs->execute([strtoupper($couponCode)]);
                $cp = $cs->fetch();
                if ($cp && (!$cp['expires_at'] || strtotime($cp['expires_at']) >= time()) && (!$cp['max_uses'] || $cp['used_count'] < $cp['max_uses']) && $total >= $cp['min_order']) {
                    $discount = $cp['discount_type'] === 'percent' ? ($total * $cp['discount_value'] / 100) : $cp['discount_value'];
                    $discount = min($discount, $total);
                    $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$cp['id']]);
                }
            }

            $finalTotal = $total - $discount;

            $stmt = $pdo->prepare("INSERT INTO orders (order_number, total_amount, status) VALUES (?, ?, 'confirmed')");
            $stmt->execute([$orderNum, $finalTotal]);
            $orderId = $pdo->lastInsertId();

            $ins = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($orderItems as $item) {
                $ins->execute([$orderId, $item['pid'], $item['name'], $item['price'], $item['qty'], $item['sub']]);
                $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?")->execute([$item['qty'], $item['pid'], $item['qty']]);
            }

            $pdo->commit();
            $_SESSION['cart'] = [];
            echo json_encode(['ok' => true, 'order_number' => $orderNum, 'total' => $finalTotal, 'discount' => $discount, 'count' => 0]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'msg' => 'เกิดข้อผิดพลาดในการสั่งซื้อ']);
        }
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
}
