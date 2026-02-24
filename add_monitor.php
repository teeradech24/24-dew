<?php
require_once 'db.php';

// เพิ่มจอ AOC 27G2SP
$stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, price, stock_quantity, image_url) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([
    5,
    'AOC 27G2SP 27"',
    '27" FHD IPS 165Hz Gaming Monitor, 1ms MPRT, FreeSync Premium, HDR10, Frameless Design, ขาตั้งปรับเอียง/สูงได้',
    6990.00,
    16,
    'assets/images/products/aoc_27g2sp.png'
]);

echo "✅ เพิ่มจอ AOC 27G2SP 27\" (฿6,990.00) เรียบร้อยแล้ว!\n";
