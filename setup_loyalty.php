<?php
require_once 'db.php';

// Add columns to users table for loyalty
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN loyalty_points INT DEFAULT 0");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN total_spent DECIMAL(12,2) DEFAULT 0");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN loyalty_tier ENUM('bronze','silver','gold','diamond') DEFAULT 'bronze'");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL");
} catch (Exception $e) {}

// Add columns to orders table
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN user_id INT DEFAULT NULL");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_name VARCHAR(100) DEFAULT NULL");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_phone VARCHAR(20) DEFAULT NULL");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_address TEXT DEFAULT NULL");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(30) DEFAULT 'transfer'");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN points_earned INT DEFAULT 0");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN points_used INT DEFAULT 0");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0");
} catch (Exception $e) {}

// Create loyalty_transactions table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS loyalty_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        order_id INT DEFAULT NULL,
        type ENUM('earn','redeem') NOT NULL,
        points INT NOT NULL,
        description VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

echo "✅ Loyalty system tables created/updated successfully.\n";
echo "- Added loyalty columns to users table (loyalty_points, total_spent, loyalty_tier, phone, address)\n";
echo "- Added shipping/payment columns to orders table\n";
echo "- Created loyalty_transactions table\n";
echo "\nTier thresholds:\n";
echo "  🥉 Bronze: 0+ (1 pt / ฿100)\n";
echo "  🥈 Silver: ฿10,000+ (1.5 pt / ฿100)\n";
echo "  🥇 Gold: ฿30,000+ (2 pt / ฿100)\n";
echo "  💎 Diamond: ฿100,000+ (3 pt / ฿100)\n";
