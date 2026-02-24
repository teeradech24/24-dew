<?php
require_once 'db.php';

// Promotions / Banners table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS promotions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        subtitle VARCHAR(200) DEFAULT '',
        description TEXT,
        tag VARCHAR(50) DEFAULT '',
        tag_icon VARCHAR(10) DEFAULT '🔥',
        link_url VARCHAR(255) DEFAULT 'showcase.php',
        image_product_id INT DEFAULT NULL,
        bg_gradient VARCHAR(200) DEFAULT 'linear-gradient(135deg, #1a1a2e, #16213e)',
        price_display VARCHAR(50) DEFAULT '',
        price_old VARCHAR(50) DEFAULT '',
        discount_label VARCHAR(20) DEFAULT '',
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        start_date DATETIME DEFAULT NULL,
        end_date DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Flash Sales table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS flash_sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL DEFAULT 'Flash Sale',
        product_id INT NOT NULL,
        sale_price DECIMAL(10,2) NOT NULL,
        original_price DECIMAL(10,2) NOT NULL,
        discount_percent INT DEFAULT 0,
        quantity_limit INT DEFAULT 0,
        quantity_sold INT DEFAULT 0,
        start_date DATETIME NOT NULL,
        end_date DATETIME NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Insert sample promotions
$check = $pdo->query("SELECT COUNT(*) FROM promotions")->fetchColumn();
if ($check == 0) {
    $pdo->exec("
        INSERT INTO promotions (title, subtitle, tag, tag_icon, link_url, price_display, discount_label, sort_order, is_active) VALUES
        ('โปรโมชั่นต้อนรับปี 2026', 'ลดสูงสุด 40% สำหรับสินค้าเกมมิ่ง', 'MEGA SALE', '🎉', 'showcase.php', 'ลดสูงสุด 40%', '-40%', 1, 1),
        ('สมาชิกใหม่รับแต้ม x2', 'สมัครสมาชิกวันนี้ รับแต้มสะสม 2 เท่าเมื่อสั่งซื้อ', 'สมาชิกใหม่', '⭐', 'register.php', 'Double Points!', 'x2', 2, 1),
        ('ส่งฟรีทุกออเดอร์!', 'ไม่มีขั้นต่ำ จัดส่งฟรีทั่วประเทศไทย', 'ส่งฟรี', '🚚', 'showcase.php', 'ฟรี!', 'FREE', 3, 1)
    ");
}

// Insert sample flash sales
$flashCheck = $pdo->query("SELECT COUNT(*) FROM flash_sales")->fetchColumn();
if ($flashCheck == 0) {
    // Get some product IDs
    $products = $pdo->query("SELECT id, price FROM products ORDER BY RAND() LIMIT 4")->fetchAll();
    foreach ($products as $i => $p) {
        $discount = [20, 30, 25, 35][$i] ?? 20;
        $salePrice = round($p['price'] * (100 - $discount) / 100, 2);
        $endDate = date('Y-m-d H:i:s', strtotime('+' . (($i + 1) * 6) . ' hours'));
        $pdo->prepare("INSERT INTO flash_sales (title, product_id, sale_price, original_price, discount_percent, quantity_limit, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)")
            ->execute(["Flash Deal #{$p['id']}", $p['id'], $salePrice, $p['price'], $discount, rand(5, 20), $endDate]);
    }
}

echo "✅ Promotions system setup complete!\n";
echo "- Created 'promotions' table (banners)\n";
echo "- Created 'flash_sales' table\n";
echo "- Inserted sample promotions and flash sale items\n";
