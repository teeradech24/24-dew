<?php
require_once 'db.php';

// Create reviews table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS product_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        reviewer_name VARCHAR(100) NOT NULL,
        rating INT NOT NULL DEFAULT 5,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Add sample reviews
$reviews = [
    [1, 'GamerTH99', 5, 'การ์ดจอสุดคุ้ม ใช้เล่นเกม 1440p ได้ลื่นมาก'],
    [1, 'TechReviewer', 4, 'คุ้มค่าราคา เสียงพัดลมเบา'],
    [2, 'PcBuilder', 5, 'RTX 4070 เทพมาก เล่นเกม 4K ได้สบายๆ'],
    [2, 'StreamerX', 5, 'ใช้สตรีมไปด้วย เล่นเกมไปด้วย ไม่มีสะดุด'],
    [3, 'ProGamer', 5, 'Ryzen 7800X3D เกมมิ่งเทพสุดในรุ่น'],
    [4, 'OcMaster', 4, 'i7-14700K โอเวอร์คล็อกง่าย แรงมาก'],
    [5, 'RamLover', 5, 'Corsair DDR5 สวย แรง RGB สุดๆ'],
    [6, 'MemoryKing', 4, 'G.Skill Trident Z5 ดีไซน์สวยมาก'],
    [7, 'StoragePro', 5, 'Samsung 990 Pro เร็วสุดๆ โหลดเกมไม่ถึง 5 วินาที'],
    [9, 'MonitorFan', 5, 'LG 27GP850 จอสวย สีสดมาก เล่นเกมลื่น'],
    [10, 'RogFan', 5, 'ROG Swift PG279QM จอที่ดีที่สุดที่เคยใช้'],
    [11, 'FPSPro', 5, 'Logitech Superlight 2 เบามาก ใช้เล่น Valorant สุดยอด'],
    [12, 'MouseGuru', 4, 'Razer DeathAdder V3 จับถนัดมือ เหมาะ FPS'],
    [13, 'BudgetGamer', 4, 'RTX 4060 Ti คุ้มสุดในรุ่น 1080p เล่นได้หมด'],
    [14, 'AmdFan', 5, 'RX 7600 ราคาถูก คุ้มค่าที่สุด'],
    [15, 'Enthusiast', 5, 'RTX 4090 แรงที่สุดในโลก ไม่ต้องพูดเยอะ'],
    [15, 'Creator4K', 5, 'ใช้ตัดต่อวิดีโอ 8K สบายมาก'],
    [23, 'CurvedMonitor', 4, 'Odyssey G7 โค้งสวย เล่นเกมมันส์'],
    [25, 'AudioFan', 5, 'Arctis Nova Pro เสียงดี ใส่สบาย ไร้สายดีมาก'],
    [26, 'KeyboardPro', 4, 'Corsair K70 สวิตช์ Cherry กดสนุก RGB สวย'],
    [27, 'HeadsetKing', 5, 'HyperX Cloud III เสียงดีเกินราคา'],
];

$stmt = $pdo->prepare("INSERT INTO product_reviews (product_id, reviewer_name, rating, comment) VALUES (?, ?, ?, ?)");
foreach ($reviews as $r) {
    $stmt->execute($r);
}

echo "Created reviews table and added " . count($reviews) . " sample reviews.\n";
