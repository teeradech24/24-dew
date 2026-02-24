<?php
require_once 'auth.php';
require_once 'db.php';

$message = '';
$messageType = '';

// Handle status update
if (isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['new_status'];
    $allowed = ['pending','confirmed','shipped','completed','cancelled'];
    if (in_array($newStatus, $allowed)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newStatus, $orderId]);
        $message = "อัปเดตสถานะคำสั่งซื้อ #{$orderId} เป็น {$newStatus} เรียบร้อย";
        $messageType = 'success';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $orderId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$orderId]);
    $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);
    $message = "ลบคำสั่งซื้อ #{$orderId} เรียบร้อย";
    $messageType = 'success';
}

// Filter
$statusFilter = $_GET['status'] ?? 'all';
$where = '';
if ($statusFilter !== 'all' && in_array($statusFilter, ['pending','confirmed','shipped','completed','cancelled'])) {
    $where = "WHERE o.status = " . $pdo->quote($statusFilter);
}

// Fetch orders
$orders = $pdo->query("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
           (SELECT GROUP_CONCAT(CONCAT(product_name, ' x', quantity) SEPARATOR ', ') FROM order_items WHERE order_id = o.id) as items_summary
    FROM orders o
    {$where}
    ORDER BY o.created_at DESC
")->fetchAll();

// Stats
$totalOrders = count($orders);
$pendingCount = 0; $confirmedCount = 0; $shippedCount = 0; $completedCount = 0; $cancelledCount = 0;
$totalRevenue = 0;
foreach ($orders as $o) {
    $totalRevenue += $o['total_amount'];
    switch ($o['status']) {
        case 'pending': $pendingCount++; break;
        case 'confirmed': $confirmedCount++; break;
        case 'shipped': $shippedCount++; break;
        case 'completed': $completedCount++; break;
        case 'cancelled': $cancelledCount++; break;
    }
}

$statusLabels = ['pending'=>'⏳ รอดำเนินการ','confirmed'=>'✅ ยืนยัน','shipped'=>'🚚 จัดส่ง','completed'=>'🎉 สำเร็จ','cancelled'=>'❌ ยกเลิก'];
$statusColors = ['pending'=>'#f59e0b','confirmed'=>'#16a34a','shipped'=>'#2563eb','completed'=>'#059669','cancelled'=>'#dc2626'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📋 Orders — GamePro Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .order-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; margin-bottom: 1.5rem; }
        .os-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem; text-align: center; transition: var(--transition); cursor: pointer; text-decoration: none; }
        .os-card:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
        .os-card.active { border-bottom: 3px solid #7c3aed; }
        .os-card .os-num { font-size: 1.5rem; font-weight: 800; color: var(--text-primary); }
        .os-card .os-label { font-size: 0.72rem; color: var(--text-muted); font-weight: 600; margin-top: 0.15rem; }

        .filter-tabs { display: flex; gap: 0.3rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .filter-tab { padding: 0.4rem 0.9rem; border-radius: 100px; font-size: 0.78rem; font-weight: 600; text-decoration: none; color: var(--text-secondary); background: var(--bg-tertiary); border: 1px solid var(--border); transition: var(--transition); }
        .filter-tab:hover { color: var(--text-primary); }
        .filter-tab.active { background: var(--text-primary); color: var(--bg-primary); border-color: var(--text-primary); }

        .order-row { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 0.5rem; overflow: hidden; }
        .order-row-header { display: grid; grid-template-columns: 140px 1fr 100px 120px 100px 80px; align-items: center; padding: 0.75rem 1rem; gap: 0.5rem; cursor: pointer; transition: var(--transition); font-size: 0.85rem; }
        .order-row-header:hover { background: var(--bg-tertiary); }
        .order-num { font-weight: 700; color: var(--text-primary); font-size: 0.82rem; }
        .order-cust { font-size: 0.8rem; color: var(--text-secondary); }
        .order-date { font-size: 0.75rem; color: var(--text-muted); }
        .order-amount { font-weight: 800; color: var(--text-primary); text-align: right; }
        .order-status-badge { padding: 0.2rem 0.6rem; border-radius: 100px; font-size: 0.68rem; font-weight: 700; color: #fff; display: inline-block; text-align: center; }
        .order-toggle { font-size: 0.9rem; color: var(--text-muted); text-align: right; cursor: pointer; transition: var(--transition); }

        .order-detail { display: none; padding: 1rem; border-top: 1px solid var(--border); }
        .order-detail.open { display: block; }
        .order-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .detail-section h4 { font-size: 0.82rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem; }
        .detail-row { display: flex; justify-content: space-between; font-size: 0.78rem; padding: 0.3rem 0; border-bottom: 1px solid var(--border); }
        .detail-row:last-child { border-bottom: none; }
        .detail-row .label { color: var(--text-muted); }
        .detail-row .value { color: var(--text-primary); font-weight: 600; }

        .order-items-list { margin-top: 0.5rem; }
        .oi { display: flex; justify-content: space-between; font-size: 0.78rem; padding: 0.25rem 0; color: var(--text-secondary); }
        .oi .oi-name { font-weight: 600; color: var(--text-primary); }

        .status-actions { display: flex; gap: 0.3rem; flex-wrap: wrap; margin-top: 0.75rem; }
        .status-btn { padding: 0.35rem 0.7rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.72rem; font-weight: 600; cursor: pointer; background: var(--bg-primary); color: var(--text-secondary); transition: var(--transition); }
        .status-btn:hover { border-color: #7c3aed; color: #7c3aed; }
        .status-btn.active-status { background: #7c3aed; color: #fff; border-color: #7c3aed; }
        .btn-delete-order { padding: 0.35rem 0.7rem; border: 1px solid #ef4444; border-radius: var(--radius-sm); font-size: 0.72rem; font-weight: 600; cursor: pointer; background: rgba(239,68,68,0.05); color: #ef4444; text-decoration: none; }
        .btn-delete-order:hover { background: #ef4444; color: #fff; }

        @media (max-width: 900px) {
            .order-row-header { grid-template-columns: 1fr 1fr; }
            .order-detail-grid { grid-template-columns: 1fr; }
            .order-stats { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo"><div class="logo-icon">🎮</div><span>GamePro</span></div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-title">Menu</div>
            <a href="index.php" class="nav-link"><span class="nav-icon">📊</span><span>Dashboard</span></a>
            <a href="products.php" class="nav-link"><span class="nav-icon">📦</span><span>Products</span></a>
            <a href="sales.php" class="nav-link"><span class="nav-icon">💰</span><span>Sales</span></a>
            <a href="admin_orders.php" class="nav-link active"><span class="nav-icon">📋</span><span>Orders</span></a>
            <a href="admin_users.php" class="nav-link"><span class="nav-icon">👥</span><span>Members</span></a>
            <a href="admin_coupons.php" class="nav-link"><span class="nav-icon">🎟️</span><span>Coupons</span></a>
            <a href="admin_contacts.php" class="nav-link"><span class="nav-icon">📧</span><span>Messages</span></a>
            <div class="nav-section-title">หน้าร้าน</div>
            <a href="showcase.php" class="nav-link"><span class="nav-icon">🛍️</span><span>Showcase</span></a>
            <div class="nav-section-title">บัญชี</div>
            <a href="logout.php" class="nav-link"><span class="nav-icon">🚪</span><span>ออกจากระบบ</span></a>
        </nav>
        <div class="sidebar-footer">GamePro Admin v1.0</div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">📋 Orders Management</h1>
                <p class="page-subtitle">จัดการคำสั่งซื้อจากหน้าร้าน</p>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= $messageType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="order-stats">
            <a href="?status=all" class="os-card <?= $statusFilter === 'all' ? 'active' : '' ?>">
                <div class="os-num"><?= $totalOrders ?></div>
                <div class="os-label">ทั้งหมด</div>
            </a>
            <a href="?status=pending" class="os-card <?= $statusFilter === 'pending' ? 'active' : '' ?>">
                <div class="os-num" style="color:#f59e0b"><?= $pendingCount ?></div>
                <div class="os-label">⏳ รอดำเนินการ</div>
            </a>
            <a href="?status=confirmed" class="os-card <?= $statusFilter === 'confirmed' ? 'active' : '' ?>">
                <div class="os-num" style="color:#16a34a"><?= $confirmedCount ?></div>
                <div class="os-label">✅ ยืนยันแล้ว</div>
            </a>
            <a href="?status=shipped" class="os-card <?= $statusFilter === 'shipped' ? 'active' : '' ?>">
                <div class="os-num" style="color:#2563eb"><?= $shippedCount ?></div>
                <div class="os-label">🚚 จัดส่งแล้ว</div>
            </a>
            <a href="?status=completed" class="os-card <?= $statusFilter === 'completed' ? 'active' : '' ?>">
                <div class="os-num" style="color:#059669"><?= $completedCount ?></div>
                <div class="os-label">🎉 สำเร็จ</div>
            </a>
            <a href="?status=cancelled" class="os-card <?= $statusFilter === 'cancelled' ? 'active' : '' ?>">
                <div class="os-num" style="color:#dc2626"><?= $cancelledCount ?></div>
                <div class="os-label">❌ ยกเลิก</div>
            </a>
        </div>

        <!-- Revenue -->
        <div class="card" style="margin-bottom:1rem;padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:0.85rem;color:var(--text-secondary);font-weight:600">💰 รายได้รวม (<?= $statusFilter === 'all' ? 'ทั้งหมด' : $statusLabels[$statusFilter] ?? $statusFilter ?>)</span>
            <span style="font-size:1.3rem;font-weight:800;color:var(--text-primary)">฿<?= number_format($totalRevenue, 2) ?></span>
        </div>

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
        <div class="card">
            <div class="empty-state" style="padding:3rem;text-align:center">
                <div style="font-size:3rem;margin-bottom:0.75rem">📋</div>
                <p style="color:var(--text-muted)">ไม่มีคำสั่งซื้อ</p>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($orders as $o):
            $color = $statusColors[$o['status']] ?? '#666';
            $label = $statusLabels[$o['status']] ?? $o['status'];
            $items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $items->execute([$o['id']]);
            $orderItems = $items->fetchAll();
        ?>
        <div class="order-row">
            <div class="order-row-header" onclick="this.nextElementSibling.classList.toggle('open')">
                <div class="order-num"><?= $o['order_number'] ?></div>
                <div class="order-cust">
                    <?= htmlspecialchars($o['shipping_name'] ?? $o['customer_name'] ?? 'ไม่ระบุ') ?>
                    <?php if ($o['shipping_phone'] ?? ''): ?>
                    <span style="color:var(--text-muted);font-size:0.72rem"> · <?= $o['shipping_phone'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="order-date"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></div>
                <div class="order-amount">฿<?= number_format($o['total_amount'], 2) ?></div>
                <div><span class="order-status-badge" style="background:<?= $color ?>"><?= $label ?></span></div>
                <div class="order-toggle">▼</div>
            </div>
            <div class="order-detail">
                <div class="order-detail-grid">
                    <div class="detail-section">
                        <h4>📦 ข้อมูลจัดส่ง</h4>
                        <div class="detail-row"><span class="label">ชื่อ</span><span class="value"><?= htmlspecialchars($o['shipping_name'] ?? '-') ?></span></div>
                        <div class="detail-row"><span class="label">เบอร์โทร</span><span class="value"><?= htmlspecialchars($o['shipping_phone'] ?? '-') ?></span></div>
                        <div class="detail-row"><span class="label">ที่อยู่</span><span class="value"><?= htmlspecialchars($o['shipping_address'] ?? '-') ?></span></div>
                        <div class="detail-row"><span class="label">ชำระเงิน</span><span class="value"><?= $o['payment_method'] === 'transfer' ? '🏦 โอนเงิน' : ($o['payment_method'] === 'promptpay' ? '📱 PromptPay' : ($o['payment_method'] === 'cod' ? '💵 COD' : ($o['payment_method'] ?? '-'))) ?></span></div>
                        <?php if (($o['points_earned'] ?? 0) > 0): ?>
                        <div class="detail-row"><span class="label">แต้มที่ได้</span><span class="value" style="color:#7c3aed">+<?= $o['points_earned'] ?> แต้ม</span></div>
                        <?php endif; ?>
                        <?php if (($o['points_used'] ?? 0) > 0): ?>
                        <div class="detail-row"><span class="label">แต้มที่ใช้</span><span class="value" style="color:#dc2626">-<?= $o['points_used'] ?> แต้ม</span></div>
                        <?php endif; ?>
                        <?php if (($o['discount_amount'] ?? 0) > 0): ?>
                        <div class="detail-row"><span class="label">ส่วนลดรวม</span><span class="value" style="color:#16a34a">-฿<?= number_format($o['discount_amount'], 2) ?></span></div>
                        <?php endif; ?>
                    </div>
                    <div class="detail-section">
                        <h4>🛒 รายการสินค้า</h4>
                        <div class="order-items-list">
                            <?php foreach ($orderItems as $item): ?>
                            <div class="oi">
                                <span class="oi-name"><?= htmlspecialchars($item['product_name']) ?> × <?= $item['quantity'] ?></span>
                                <span>฿<?= number_format($item['subtotal'], 2) ?></span>
                            </div>
                            <?php endforeach; ?>
                            <div class="oi" style="border-top:1px solid var(--border);padding-top:0.4rem;margin-top:0.3rem;font-weight:700;color:var(--text-primary)">
                                <span>รวม</span>
                                <span>฿<?= number_format($o['total_amount'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Status Actions -->
                <div class="status-actions">
                    <span style="font-size:0.75rem;color:var(--text-muted);font-weight:600;margin-right:0.3rem;line-height:2">อัปเดตสถานะ:</span>
                    <?php foreach (['pending','confirmed','shipped','completed','cancelled'] as $s): ?>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="update_status" value="1">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <input type="hidden" name="new_status" value="<?= $s ?>">
                        <button type="submit" class="status-btn <?= $o['status'] === $s ? 'active-status' : '' ?>"
                            <?= $o['status'] === $s ? 'disabled' : '' ?>
                            onclick="return confirm('เปลี่ยนสถานะเป็น <?= $statusLabels[$s] ?>?')">
                            <?= $statusLabels[$s] ?>
                        </button>
                    </form>
                    <?php endforeach; ?>
                    <a href="?delete=<?= $o['id'] ?>&status=<?= $statusFilter ?>" class="btn-delete-order" onclick="return confirm('ลบคำสั่งซื้อนี้?')">🗑️ ลบ</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
<button class="mobile-menu-btn" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>
<a href="javascript:history.back()" class="btn-back-float" title="ย้อนกลับ">←</a>
</body>
</html>
