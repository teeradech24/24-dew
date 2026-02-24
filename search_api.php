<?php
require_once 'db.php';
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.price, p.image_url, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.name LIKE ? OR p.description LIKE ?
    ORDER BY p.name
    LIMIT 6
");
$like = "%$q%";
$stmt->execute([$like, $like]);
$results = $stmt->fetchAll();

echo json_encode(['results' => $results]);
