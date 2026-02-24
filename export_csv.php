<?php
require_once 'auth.php';
require_once 'db.php';

$type = $_GET['type'] ?? 'sales';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="gamepro_' . $type . '_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
// BOM for Excel UTF-8
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

if ($type === 'orders') {
    fputcsv($output, ['Order Number', 'Customer', 'Total (฿)', 'Status', 'Date']);
    $rows = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['order_number'],
            $r['customer_name'],
            number_format($r['total_amount'], 2),
            $r['status'],
            $r['created_at']
        ]);
    }
} else {
    fputcsv($output, ['Sale ID', 'Date', 'Items', 'Total (฿)']);
    $rows = $pdo->query("
        SELECT s.id, s.sale_date, s.total_amount,
               GROUP_CONCAT(CONCAT(p.name, ' x', si.quantity) SEPARATOR ', ') as items_summary
        FROM sales s
        LEFT JOIN sale_items si ON s.id = si.sale_id
        LEFT JOIN products p ON si.product_id = p.id
        GROUP BY s.id
        ORDER BY s.sale_date DESC
    ")->fetchAll();
    foreach ($rows as $r) {
        fputcsv($output, [
            '#' . $r['id'],
            $r['sale_date'],
            $r['items_summary'],
            number_format($r['total_amount'], 2)
        ]);
    }
}

fclose($output);
exit;
