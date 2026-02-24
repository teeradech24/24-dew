<?php
require_once 'db.php';

// Map product IDs (13-27) to Amazon image URLs found by browser
$imageMap = [
    13 => 'https://m.media-amazon.com/images/I/81i5KcFKysL._AC_SL1500_.jpg',  // RTX 4060 Ti
    14 => 'https://m.media-amazon.com/images/I/81asxT0eljL._AC_SL1500_.jpg',  // AMD RX 7600
    15 => 'https://m.media-amazon.com/images/I/81KR0fO8WgL._AC_SL1500_.jpg',  // RTX 4090
    16 => 'https://m.media-amazon.com/images/I/61IrFcuSCeL._AC_SL1500_.jpg',  // i5-14600K
    17 => 'https://m.media-amazon.com/images/I/5116zdA9uyL._AC_SL1200_.jpg',  // Ryzen 9 7950X
    18 => 'https://m.media-amazon.com/images/I/619ytLAytAL._AC_SL1500_.jpg',  // i9-14900K
    19 => 'https://m.media-amazon.com/images/I/717cPftxQgL._AC_SL1500_.jpg',  // Kingston Fury Beast
    20 => 'https://m.media-amazon.com/images/I/61lO3cVo2ZL._AC_SL1500_.jpg',  // Corsair Dominator
    21 => 'https://m.media-amazon.com/images/I/51LpPKFIPCL._AC_SL1000_.jpg',  // Crucial T700
    22 => 'https://m.media-amazon.com/images/I/911ujeCkGfL._AC_SL1500_.jpg',  // Samsung 870 EVO
    23 => 'https://m.media-amazon.com/images/I/81-olLMOeQL._AC_SL1500_.jpg',  // Odyssey G7
    24 => 'https://m.media-amazon.com/images/I/71RKChuJA3L._AC_SL1500_.jpg',  // Dell S2722DGM
    25 => 'https://m.media-amazon.com/images/I/71PYgC+oolL._AC_SL1500_.jpg',  // Arctis Nova Pro
    26 => 'https://m.media-amazon.com/images/I/71Cy-uweiJL._AC_SL1500_.jpg',  // Corsair K70
    27 => 'https://m.media-amazon.com/images/I/81dkzD4hxIL._AC_SL1500_.jpg',  // HyperX Cloud III
];

$imgDir = '/var/www/html/assets/images/products/';
$stmt = $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?");

$ctx = stream_context_create(['http' => [
    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
    'timeout' => 10,
]]);

foreach ($imageMap as $id => $url) {
    $filename = "product_{$id}.jpg";
    $filepath = $imgDir . $filename;
    $localUrl = "assets/images/products/" . $filename;
    
    $data = @file_get_contents($url, false, $ctx);
    if ($data && strlen($data) > 1000) {
        file_put_contents($filepath, $data);
        $stmt->execute([$localUrl, $id]);
        echo "OK #$id -> $localUrl (" . strlen($data) . " bytes)\n";
    } else {
        // Store direct Amazon URL as fallback
        $stmt->execute([$url, $id]);
        echo "DIRECT #$id -> $url (external)\n";
    }
}

echo "\nDone!\n";
