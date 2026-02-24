<?php
/**
 * One-time script to update product image URLs in the database.
 * Run via: docker exec stock_php_99 php /var/www/html/update_images.php
 */
require_once 'db.php';

$imageMap = [
    1  => 'assets/images/products/rtx_4070.png',
    2  => 'assets/images/products/rx_7800xt.png',
    3  => 'assets/images/products/i7_14700k.png',
    4  => 'assets/images/products/ryzen_7800x3d.png',
    5  => 'assets/images/products/corsair_ddr5.png',
    6  => 'assets/images/products/gskill_z5.svg',
    7  => 'assets/images/products/samsung_990pro.svg',
    8  => 'assets/images/products/wd_sn850x.svg',
    9  => 'assets/images/products/lg_27gp850.svg',
    10 => 'assets/images/products/asus_pg279qm.svg',
    11 => 'assets/images/products/logitech_superlight.svg',
    12 => 'assets/images/products/razer_deathadder.svg',
];

$stmt = $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?");

foreach ($imageMap as $id => $url) {
    $stmt->execute([$url, $id]);
    echo "Updated product #$id -> $url\n";
}

echo "\nDone! All product images updated.\n";
