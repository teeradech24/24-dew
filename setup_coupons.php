<?php
require_once 'db.php';

// Create coupons table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS coupons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(30) NOT NULL UNIQUE,
        discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
        discount_value DECIMAL(10,2) NOT NULL,
        min_order DECIMAL(10,2) DEFAULT 0,
        max_uses INT DEFAULT NULL,
        used_count INT DEFAULT 0,
        expires_at DATETIME DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Insert sample coupons
$coupons = [
    ['GAMEPRO10', 'percent', 10, 1000, 100, '2026-12-31 23:59:59'],
    ['SAVE500', 'fixed', 500, 5000, 50, '2026-12-31 23:59:59'],
    ['NEWUSER20', 'percent', 20, 2000, 200, '2026-06-30 23:59:59'],
    ['FLASH15', 'percent', 15, 3000, 30, '2026-03-31 23:59:59'],
    ['MEGA1000', 'fixed', 1000, 10000, 20, '2026-12-31 23:59:59'],
];

$stmt = $pdo->prepare("INSERT IGNORE INTO coupons (code, discount_type, discount_value, min_order, max_uses, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($coupons as $c) {
    $stmt->execute($c);
}

echo "Created coupons table with " . count($coupons) . " sample coupons.\n";
echo "Codes: GAMEPRO10 (10%), SAVE500 (฿500), NEWUSER20 (20%), FLASH15 (15%), MEGA1000 (฿1000)\n";
