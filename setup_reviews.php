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

// Add 3 reviews per product
$reviews = [
    // Product 1: NVIDIA RTX 4070
    [1, 'GamerTH99', 5, 'การ์ดจอสุดคุ้ม ใช้เล่นเกม 1440p ได้ลื่นมาก'],
    [1, 'TechReviewer', 4, 'คุ้มค่าราคา เสียงพัดลมเบา'],
    [1, 'PCBuilder01', 5, 'ใช้ต่อจอ 2K เล่นเกมสบายๆ แรงมาก'],

    // Product 2: AMD RX 7800 XT
    [2, 'PcBuilder', 5, 'RTX 4070 เทพมาก เล่นเกม 4K ได้สบายๆ'],
    [2, 'StreamerX', 5, 'ใช้สตรีมไปด้วย เล่นเกมไปด้วย ไม่มีสะดุด'],
    [2, 'AmdLover', 4, 'คุ้มค่ามาก 16GB VRAM เหลือเฟือ ราคาดี'],

    // Product 3: Intel Core i7-14700K
    [3, 'OcMaster', 4, 'i7-14700K โอเวอร์คล็อกง่าย แรงมาก'],
    [3, 'WorkStation99', 5, 'ใช้ทั้งเล่นเกมและทำงาน Multi-task ได้ดีเยี่ยม'],
    [3, 'IntelFanBoy', 5, 'แรงสุดในรุ่น เหมาะกับเกมเมอร์ตัวจริง'],

    // Product 4: AMD Ryzen 7 7800X3D
    [4, 'ProGamer', 5, 'Ryzen 7800X3D เกมมิ่งเทพสุดในรุ่น'],
    [4, 'TechGuru', 5, '3D V-Cache ทำให้ FPS สูงกว่า Intel มาก'],
    [4, 'BudgetBuilder', 4, 'ราคาสูงนิดนึงแต่ประสิทธิภาพเกมคุ้มค่ามาก'],

    // Product 5: Corsair Vengeance DDR5 32GB
    [5, 'RamLover', 5, 'Corsair DDR5 สวย แรง RGB สุดๆ'],
    [5, 'OverclockerPro', 4, 'ราคาเหมาะสม OC ง่าย stable มาก'],
    [5, 'PCEnthusiast', 5, 'แรม DDR5 ตัวคุ้ม Latency ต่ำ ใช้งานดีมาก'],

    // Product 6: G.Skill Trident Z5 RGB 32GB
    [6, 'MemoryKing', 4, 'G.Skill Trident Z5 ดีไซน์สวยมาก'],
    [6, 'RGBMaster', 5, 'สวยที่สุดในตลาด RGB สีสดใส'],
    [6, 'TechNerd', 4, 'แรงดี แต่ราคาค่อนข้างสูง คุ้มสำหรับคนชอบ RGB'],

    // Product 7: Samsung 990 Pro 2TB
    [7, 'StoragePro', 5, 'Samsung 990 Pro เร็วสุดๆ โหลดเกมไม่ถึง 5 วินาที'],
    [7, 'ContentCreator', 5, 'ตัดต่อวิดีโอ 4K ลื่นมาก ไม่มีสะดุดเลย'],
    [7, 'PCBuilder07', 4, 'SSD ตัวท็อป เร็วจริง คุ้มค่าทุกบาท'],

    // Product 8: WD Black SN850X 1TB
    [8, 'WDFan', 4, 'WD Black SN850X เร็วดี ราคาเบากว่า Samsung'],
    [8, 'PS5User', 5, 'ใส่ PS5 ได้เลย เร็วมาก โหลดเกมง่าย'],
    [8, 'BudgetSSD', 4, 'คุ้มค่ามากสำหรับ SSD Gen4 ระดับนี้'],

    // Product 9: LG 27GP850-B
    [9, 'MonitorFan', 5, 'LG 27GP850 จอสวย สีสดมาก เล่นเกมลื่น'],
    [9, 'DesignerPro', 5, 'สีแม่นมาก sRGB 99% ใช้แต่งรูปได้เลย'],
    [9, 'FPSGamer', 4, '180Hz ลื่นมาก IPS สีสวย เหมาะกับ Competitive'],

    // Product 10: ASUS ROG Swift PG279QM
    [10, 'RogFan', 5, 'ROG Swift PG279QM จอที่ดีที่สุดที่เคยใช้'],
    [10, 'EsportsPlayer', 5, '240Hz G-Sync ลื่นไม่มีฉีก เหมาะกับ FPS มาก'],
    [10, 'MonitorReview', 4, 'ราคาแพงแต่คุณภาพคุ้มค่ามาก IPS สีสดใส'],

    // Product 11: Logitech G Pro X Superlight 2
    [11, 'FPSPro', 5, 'Logitech Superlight 2 เบามาก ใช้เล่น Valorant สุดยอด'],
    [11, 'MouseCollector', 5, 'เมาส์ที่ดีที่สุดที่เคยใช้ sensor เทพ เบา 63g'],
    [11, 'CompGamer', 4, 'จับถนัดมือ เหมาะมากกับเกม FPS'],

    // Product 12: Razer DeathAdder V3
    [12, 'MouseGuru', 4, 'Razer DeathAdder V3 จับถนัดมือ เหมาะ FPS'],
    [12, 'GamingFan', 5, 'Ergonomic ที่สุด จับสบายมือ มาก เล่นวันละ 8 ชม.'],
    [12, 'RazerFan', 4, 'Sensor Focus Pro 30K แม่นมาก Tracking ดีเยี่ยม'],

    // Product 13: NVIDIA RTX 4060 Ti
    [13, 'BudgetGamer', 4, 'RTX 4060 Ti คุ้มสุดในรุ่น 1080p เล่นได้หมด'],
    [13, 'CasualGamer', 5, 'เล่น 1080p Ultra ได้สบาย ทุกเกม 60fps ขึ้นไป'],
    [13, 'FirstPCBuild', 4, 'การ์ดจอแรกที่ซื้อ ไม่ผิดหวัง คุ้มค่ามาก'],

    // Product 14: AMD RX 7600
    [14, 'AmdFan', 5, 'RX 7600 ราคาถูก คุ้มค่าที่สุด'],
    [14, 'BudgetKing', 4, 'ในราคาหมื่นต้นๆ ได้ของดีระดับนี้ คุ้มมาก'],
    [14, 'StudentGamer', 5, 'นักศึกษาก็ซื้อได้ เล่นเกมได้ทุกเกม 1080p'],

    // Product 15: NVIDIA RTX 4090
    [15, 'Enthusiast', 5, 'RTX 4090 แรงที่สุดในโลก ไม่ต้องพูดเยอะ'],
    [15, 'Creator4K', 5, 'ใช้ตัดต่อวิดีโอ 8K สบายมาก'],
    [15, 'MaxPerformance', 5, 'มีเงินต้องซื้อ เล่นเกม 4K Ultra Max ลื่นหมด'],

    // Product 16: Intel Core i5-14600K
    [16, 'ValueGamer', 5, 'i5-14600K ราคาคุ้ม แรงมาก เหมาะกับเกมเมอร์'],
    [16, 'WorkFromHome', 4, 'ใช้ทำงานและเล่นเกมได้ดีมาก 14 cores คุ้มราคา'],
    [16, 'UpgradeKing', 5, 'อัพจาก i5-12400 มา สัมผัสได้เลยว่าแรงขึ้นมาก'],

    // Product 17: AMD Ryzen 9 7950X
    [17, 'ProCreator', 5, 'Ryzen 9 7950X แรงสุดๆ 16 cores Render ไวมาก'],
    [17, 'DevOps', 5, 'ใช้รัน VM หลายตัวพร้อมกันได้สบาย ไม่มีกระตุก'],
    [17, 'StreamerPro', 4, 'Stream + Game พร้อมกันได้ไม่มีปัญหาเลย'],

    // Product 18: Intel Core i9-14900K
    [18, 'TopEnd', 5, 'i9-14900K ตัวท็อปสุด 24 cores แรงไม่มีใครเทียบ'],
    [18, 'OcMasterElite', 4, 'โอเวอร์คล็อกได้ดี แต่ต้องมีชุดน้ำดีๆ'],
    [18, 'HeavyUser', 5, 'ทำงานหนักๆ ได้หมด ทั้ง Render, Compile, Game'],

    // Product 19: Kingston Fury Beast DDR5
    [19, 'RamReviewer', 4, 'Kingston Fury Beast ราคาคุ้ม เร็วดี stable'],
    [19, 'BudgetBuilder2', 5, 'แรม DDR5 ราคาถูกที่สุดที่ใช้ได้ดี'],
    [19, 'OverclockerTH', 4, 'OC ไม่ได้เยอะมาก แต่ใช้งานปกติดีมาก'],

    // Product 20: Corsair Dominator Platinum DDR5
    [20, 'PremiumUser', 5, 'Corsair Dominator สวยที่สุดในตลาด DDR5'],
    [20, 'RGBLover', 5, 'iCUE RGB สุดยอด สีสดสวย ควบคุมง่าย'],
    [20, 'TopTierRAM', 4, 'แรงดี CL30 แต่ราคาค่อนข้างแพง'],

    // Product 21: Crucial T700 2TB
    [21, 'Gen5Pioneer', 5, 'Crucial T700 Gen5 เร็วที่สุด 12400MB/s จริงๆ'],
    [21, 'EarlyAdopter', 4, 'เร็วมากจริง แต่ร้อนพอควร ต้องมี heatsink'],
    [21, 'DataHoarder', 5, '2TB พื้นที่เยอะ เร็ว คุ้มค่ามาก'],

    // Product 22: Samsung 870 EVO 1TB
    [22, 'SATAFan', 5, 'Samsung 870 EVO ตัวเก่า SSD SATA ที่ดีที่สุด'],
    [22, 'OldPCUpgrade', 5, 'อัพเกรดโน้ตบุ๊คเก่า เปลี่ยนจาก HDD มา เร็วขึ้น 10 เท่า'],
    [22, 'DataStorage', 4, 'ใช้เก็บข้อมูลเสมรอง เร็วกว่า HDD มาก ราคาคุ้ม'],

    // Product 23: Samsung Odyssey G7
    [23, 'CurvedMonitor', 4, 'Odyssey G7 โค้งสวย เล่นเกมมันส์'],
    [23, 'ImmersiveGamer', 5, '32 นิ้ว โค้ง 1000R immersive มาก เล่นเกมรู้สึกอยู่ในเกม'],
    [23, 'MovieLover', 4, 'ดูหนังก็สวย เล่นเกมก็ลื่น 240Hz คุ้มมาก'],

    // Product 24: Dell S2722DGM
    [24, 'DellFan', 4, 'Dell S2722DGM จอดีราคาเหมาะสม'],
    [24, 'BudgetMonitor', 5, 'จอ 2K 165Hz ราคาต่ำกว่าหมื่น คุ้มมากๆ'],
    [24, 'StudentSetup', 4, 'ใช้เรียนออนไลน์และเล่นเกม สมราคา'],

    // Product 25: SteelSeries Arctis Nova Pro
    [25, 'AudioFan', 5, 'Arctis Nova Pro เสียงดี ใส่สบาย ไร้สายดีมาก'],
    [25, 'MusicGamer', 5, 'Hi-Res Audio จริงๆ เสียงดีกว่าหูฟังทุกตัวที่เคยใช้'],
    [25, 'LongSession', 4, 'ใส่ 6 ชม. ต่อเนื่องไม่ปวดหู น้ำหนักเบา'],

    // Product 26: Corsair K70 RGB PRO
    [26, 'KeyboardPro', 4, 'Corsair K70 สวิตช์ Cherry กดสนุก RGB สวย'],
    [26, 'TypingFan', 5, 'พิมพ์งานก็ดี เล่นเกมก็เยี่ยม Cherry MX Red ลื่นมาก'],
    [26, 'DeskSetup', 4, 'Premium build สวยมาก ไวร์คีย์บอร์ดที่น่าซื้อที่สุด'],

    // Product 27: HyperX Cloud III
    [27, 'HeadsetKing', 5, 'HyperX Cloud III เสียงดีเกินราคา'],
    [27, 'VoiceChat', 4, 'ไมค์ชัดมาก เพื่อนในเกมได้ยินเสียงเราชัดเจน'],
    [27, 'ComfortFirst', 5, 'ใส่สบายมาก หนังนิ่ม ไม่ร้อน เสียงชัด'],
];

$stmt = $pdo->prepare("INSERT INTO product_reviews (product_id, reviewer_name, rating, comment) VALUES (?, ?, ?, ?)");
foreach ($reviews as $r) {
    $stmt->execute($r);
}

echo "Created reviews table and added " . count($reviews) . " reviews (3 per product).\n";
