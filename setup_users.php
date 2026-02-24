<?php
require_once 'db.php';

// Create users table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin','user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Insert default admin (password: admin1234)
$adminHash = password_hash('admin1234', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
$stmt->execute(['admin', 'admin@gamepro.com', $adminHash]);

// Insert sample user (password: user1234)
$userHash = password_hash('user1234', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'user')");
$stmt->execute(['user1', 'user1@gamepro.com', $userHash]);

echo "Created users table and added default accounts.\n";
echo "Admin: admin / admin1234\n";
echo "User: user1 / user1234\n";
