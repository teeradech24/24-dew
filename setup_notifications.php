<?php
require_once 'db.php';

$pdo->exec("
    CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('stock_low','new_order','new_contact','general') DEFAULT 'general',
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Sample notifications
$notifs = [
    ['stock_low', '⚠️ สินค้าใกล้หมด', 'AMD Ryzen 7 7800X3D เหลือเพียง 3 ชิ้น'],
    ['new_order', '🛍️ คำสั่งซื้อใหม่', 'ได้รับคำสั่งซื้อ #GP-20260224-0001 ยอด ฿19,900'],
    ['new_contact', '📧 ข้อความใหม่', 'ได้รับข้อความจาก สมชาย ใจดี เรื่อง: สอบถามสินค้า'],
    ['stock_low', '⚠️ สินค้าใกล้หมด', 'G.Skill Trident Z5 RGB 32GB เหลือเพียง 2 ชิ้น'],
    ['general', '🎉 ยินดีต้อนรับ', 'ระบบ GamePro Inventory พร้อมใช้งานแล้ว!'],
];

$stmt = $pdo->prepare("INSERT INTO notifications (type, title, message) VALUES (?, ?, ?)");
foreach ($notifs as $n) {
    $stmt->execute($n);
}

echo "Created notifications table and added " . count($notifs) . " sample notifications.\n";
