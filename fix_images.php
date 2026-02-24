<?php
require_once 'db.php';

// Clear broken image URLs for new products (Amazon URLs that don't work)
$stmt = $pdo->query("SELECT id, name, image_url FROM products WHERE image_url LIKE '%m.media-amazon%' AND id > 12");
$products = $stmt->fetchAll();

$update = $pdo->prepare("UPDATE products SET image_url = NULL WHERE id = ?");
foreach ($products as $p) {
    $update->execute([$p['id']]);
    echo "Cleared broken image for #{$p['id']}: {$p['name']}\n";
}

echo "\nDone! " . count($products) . " products updated.\n";
