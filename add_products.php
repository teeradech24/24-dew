<?php
require_once 'db.php';

// เพิ่มสินค้าใหม่ทุกหมวด (ไม่ซ้ำ ID เดิม)
$newProducts = [
    // Graphics Cards (category_id=1)
    [1, 'NVIDIA RTX 4060 Ti', 'GeForce RTX 4060 Ti 8GB GDDR6', 15900.00, 12, 'https://m.media-amazon.com/images/I/61gMSNpxtsL._AC_SL1000_.jpg'],
    [1, 'AMD RX 7600', 'Radeon RX 7600 8GB GDDR6', 9990.00, 20, 'https://m.media-amazon.com/images/I/71UGjp67URL._AC_SL1500_.jpg'],
    [1, 'NVIDIA RTX 4090', 'GeForce RTX 4090 24GB GDDR6X', 62900.00, 3, 'https://m.media-amazon.com/images/I/61LYCJJZr-L._AC_SL1500_.jpg'],

    // Processors (category_id=2)
    [2, 'Intel Core i5-14600K', '14th Gen Intel Core i5, 14 cores', 10900.00, 15, 'https://m.media-amazon.com/images/I/51GRKUQkMlL._AC_SL1500_.jpg'],
    [2, 'AMD Ryzen 9 7950X', '16-Core, 32-Thread Desktop Processor', 19900.00, 6, 'https://m.media-amazon.com/images/I/51T6EjY5jCL._AC_SL1200_.jpg'],
    [2, 'Intel Core i9-14900K', '14th Gen Intel Core i9, 24 cores', 21500.00, 4, 'https://m.media-amazon.com/images/I/51GRKUQkMlL._AC_SL1500_.jpg'],

    // RAM (category_id=3)
    [3, 'Kingston Fury Beast DDR5 32GB', 'DDR5-5600 CL36 Dual Channel', 3290.00, 25, 'https://m.media-amazon.com/images/I/51cz3F6AHUL._AC_SL1500_.jpg'],
    [3, 'Corsair Dominator Platinum DDR5 32GB', 'DDR5-6000 CL30 RGB Kit', 6490.00, 8, 'https://m.media-amazon.com/images/I/71nJ3LhKsmL._AC_SL1500_.jpg'],

    // Storage (category_id=4)
    [4, 'Crucial T700 2TB', 'PCIe Gen5 NVMe M.2 SSD, 12400MB/s', 8990.00, 7, 'https://m.media-amazon.com/images/I/71DWzRGMcnL._AC_SL1500_.jpg'],
    [4, 'Samsung 870 EVO 1TB', '2.5" SATA III SSD, 560MB/s Read', 2790.00, 30, 'https://m.media-amazon.com/images/I/71CJJhOczpL._AC_SL1500_.jpg'],

    // Monitors (category_id=5)
    [5, 'Samsung Odyssey G7 32"', '32" QHD 240Hz Curved VA, 1ms', 16900.00, 5, 'https://m.media-amazon.com/images/I/81fe1cwYh6L._AC_SL1500_.jpg'],
    [5, 'Dell S2722DGM 27"', '27" QHD 165Hz Curved VA, 1ms', 8990.00, 10, 'https://m.media-amazon.com/images/I/71UKdl5PHeL._AC_SL1500_.jpg'],

    // Peripherals (category_id=6)
    [6, 'SteelSeries Arctis Nova Pro', 'Wireless Gaming Headset, Hi-Res Audio', 8990.00, 9, 'https://m.media-amazon.com/images/I/61WgFORobDL._AC_SL1500_.jpg'],
    [6, 'Corsair K70 RGB PRO', 'Mechanical Gaming Keyboard, Cherry MX', 4590.00, 14, 'https://m.media-amazon.com/images/I/71nAVKcXReL._AC_SL1500_.jpg'],
    [6, 'HyperX Cloud III', 'Wired Gaming Headset, DTS, 53mm Driver', 2990.00, 18, 'https://m.media-amazon.com/images/I/61CGnbdCnhL._AC_SL1500_.jpg'],
];

$stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, price, stock_quantity, image_url) VALUES (?, ?, ?, ?, ?, ?)");

$count = 0;
foreach ($newProducts as $p) {
    $stmt->execute($p);
    $count++;
    echo "Added: {$p[1]}\n";
}

echo "\nDone! Added $count new products.\n";
