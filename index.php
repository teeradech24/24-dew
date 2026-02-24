<?php
require_once 'auth.php';
require_once 'db.php';

// Query stats
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 5")->fetchColumn();
$totalSales = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales")->fetchColumn();

// Recent sales (5 latest)
$recentSales = $pdo->query("
    SELECT s.id, s.sale_date, s.total_amount,
           COUNT(si.id) as item_count
    FROM sales s
    LEFT JOIN sale_items si ON s.id = si.sale_id
    GROUP BY s.id
    ORDER BY s.sale_date DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — GamePro Inventory</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="logo-icon">🎮</div>
                <span>GamePro</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-title">Menu</div>
            <a href="index.php" class="nav-link active">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="products.php" class="nav-link">
                <span class="nav-icon">📦</span>
                <span>Products</span>
            </a>
            <a href="sales.php" class="nav-link">
                <span class="nav-icon">💰</span>
                <span>Sales</span>
            </a>
            <div class="nav-section-title">หน้าร้าน</div>
            <a href="showcase.php" class="nav-link">
                <span class="nav-icon">🛍️</span>
                <span>Showcase</span>
            </a>
            <div class="nav-section-title">บัญชี</div>
            <a href="logout.php" class="nav-link">
                <span class="nav-icon">🚪</span>
                <span>ออกจากระบบ</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            GamePro Inventory v1.0
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">ภาพรวม GamePro Inventory</p>
        </div>

        <!-- Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?= number_format($totalProducts) ?></div>
                <div class="stat-label">สินค้าทั้งหมด</div>
            </div>
            <div class="stat-card <?= $lowStock > 0 ? 'danger' : '' ?>">
                <div class="stat-icon">⚠️</div>
                <div class="stat-value"><?= number_format($lowStock) ?></div>
                <div class="stat-label">สินค้า Stock ต่ำ (&lt;5)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value">฿<?= number_format($totalSales, 2) ?></div>
                <div class="stat-label">ยอดขายรวม</div>
            </div>
        </div>

        <!-- Recent Sales -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">🕐 ยอดขายล่าสุด</h2>
                <a href="sales.php" class="btn btn-sm btn-secondary">ดูทั้งหมด →</a>
            </div>
            <div class="table-wrapper">
                <?php if (count($recentSales) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>วันที่</th>
                            <th>จำนวนรายการ</th>
                            <th class="text-right">ยอดรวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSales as $sale): ?>
                        <tr>
                            <td><span class="badge badge-info">#<?= $sale['id'] ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($sale['sale_date'])) ?></td>
                            <td><?= $sale['item_count'] ?> รายการ</td>
                            <td class="text-right price">฿<?= number_format($sale['total_amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">🛒</div>
                    <p>ยังไม่มียอดขาย</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<button class="mobile-menu-btn" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>
</body>
</html>
