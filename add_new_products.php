<?php
require_once 'db.php';

// เพิ่มสินค้าใหม่ 1 ตัวต่อหมวด พร้อมรูปที่สร้างให้
$newProducts = [
    // Category 1: Graphics Cards
    [1, 'MSI RTX 4080 Super Gaming X Trio', 'GeForce RTX 4080 Super 16GB GDDR6X, Tri Frozr 3 Cooling, RGB Mystic Light, Boost Clock 2610 MHz', 34900.00, 7, 'assets/images/products/msi_rtx4080.png'],

    // Category 2: Processors
    [2, 'AMD Ryzen 5 7600X', '6-Core 12-Thread Desktop Processor, 5.3GHz Max Boost, 38MB Cache, AM5 Socket, ราคาคุ้มค่าสำหรับเกมเมอร์', 7990.00, 18, 'assets/images/products/ryzen5_7600x.png'],

    // Category 3: RAM
    [3, 'TeamGroup T-Force Delta RGB DDR5 32GB', 'DDR5-6000 CL38 Dual Channel Kit, Addressable RGB, Black Heatspreader, Intel XMP 3.0', 3690.00, 22, 'assets/images/products/teamgroup_ddr5.png'],

    // Category 4: Storage
    [4, 'Seagate FireCuda 530 2TB', 'PCIe Gen4 NVMe M.2 SSD, 7300MB/s Read, 6900MB/s Write, PS5 Compatible, Endurance 2550 TBW', 6490.00, 14, 'assets/images/products/seagate_firecuda.png'],

    // Category 5: Monitors
    [5, 'BenQ EX2710U 27"', '27" 4K UHD 144Hz IPS Gaming Monitor, HDRi, treVolo Speaker, 1ms MPRT, FreeSync Premium Pro', 18900.00, 5, 'assets/images/products/benq_monitor.png'],

    // Category 6: Peripherals
    [6, 'Razer BlackShark V2 Pro', 'Wireless Gaming Headset, TriForce Titanium 50mm Driver, THX Spatial Audio, HyperClear Mic, 70h Battery', 6490.00, 11, 'assets/images/products/razer_headset.png'],
];

$stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, price, stock_quantity, image_url) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($newProducts as $p) {
    $stmt->execute($p);
    echo "✅ Added: {$p[1]} (฿" . number_format($p[3], 2) . ")\n";
}

echo "\n🎉 Done! Added " . count($newProducts) . " new products (1 per category).\n";
