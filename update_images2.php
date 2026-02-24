<?php
require_once 'db.php';

$imageMap = [
    6  => 'assets/images/products/gskill_z5.png',
    7  => 'assets/images/products/samsung_990pro.png',
    8  => 'assets/images/products/wd_sn850x.png',
    9  => 'assets/images/products/lg_27gp850.png',
    10 => 'assets/images/products/asus_pg279qm.png',
    12 => 'assets/images/products/razer_deathadder.png',
];

$stmt = $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?");
foreach ($imageMap as $id => $url) {
    $stmt->execute([$url, $id]);
    echo "Updated #$id -> $url\n";
}
echo "Done!\n";
