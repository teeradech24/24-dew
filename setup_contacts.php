<?php
require_once 'db.php';

$pdo->exec("
    CREATE TABLE IF NOT EXISTS contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Sample messages
$msgs = [
    ['สมชาย ใจดี', 'somchai@email.com', 'สอบถามสินค้า', 'อยากทราบว่า RTX 4090 มีของพร้อมส่งไหมครับ?'],
    ['มะลิ สวย', 'mali@email.com', 'แจ้งปัญหาการสั่งซื้อ', 'สั่งซื้อไปแล้วแต่ยังไม่ได้รับของค่ะ Order #GP-20260220-0001'],
    ['ต้น เกมเมอร์', 'ton@email.com', 'ขอบคุณครับ', 'สินค้าดีมากครับ จัดส่งเร็ว แพ็คดี ขอบคุณทีม GamePro!'],
];

$stmt = $pdo->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
foreach ($msgs as $m) {
    $stmt->execute($m);
}

echo "Created contacts table and added " . count($msgs) . " sample messages.\n";
