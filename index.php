<?php
require_once 'auth.php';
require_once 'db.php';

// Query stats
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 5 AND stock_quantity > 0")->fetchColumn();
$totalSales = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales")->fetchColumn();
$totalOrders = 0;
try { $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(); } catch(Exception $e) {}

// Sales last 7 days
$salesByDay = $pdo->query("
    SELECT DATE(sale_date) as day, SUM(total_amount) as total
    FROM sales
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(sale_date)
    ORDER BY day
")->fetchAll();

$labels = [];
$data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d/m', strtotime($d));
    $found = 0;
    foreach ($salesByDay as $s) {
        if ($s['day'] === $d) { $found = $s['total']; break; }
    }
    $data[] = (float)$found;
}

// Top 5 products by sales
$topProducts = $pdo->query("
    SELECT p.name, SUM(si.quantity) as total_sold
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    GROUP BY si.product_id
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll();

// Low stock products
$lowStockProducts = $pdo->query("SELECT id, name, stock_quantity, price FROM products WHERE stock_quantity < 5 AND stock_quantity > 0 ORDER BY stock_quantity ASC LIMIT 10")->fetchAll();
$outOfStock = $pdo->query("SELECT id, name, price FROM products WHERE stock_quantity = 0")->fetchAll();

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

// Notification count
$unreadNotifs = 0;
try { $unreadNotifs = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn(); } catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — GamePro Inventory</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        .chart-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; }
        .chart-card h3 { font-size: 1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; }
        .charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
        .notif-bell { position: relative; cursor: pointer; font-size: 1.2rem; background: none; border: none; padding: 0.4rem; }
        .notif-badge { position: absolute; top: -2px; right: -6px; background: #ef4444; color: #fff; font-size: 0.6rem; font-weight: 700; padding: 0.1rem 0.35rem; border-radius: 100px; min-width: 16px; text-align: center; }
        .notif-dropdown { position: absolute; top: 100%; right: 0; width: 320px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-lg); z-index: 200; display: none; max-height: 400px; overflow-y: auto; }
        .notif-dropdown.open { display: block; }
        .notif-dropdown-header { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .notif-dropdown-header h4 { font-size: 0.9rem; font-weight: 700; color: var(--text-primary); margin: 0; }
        .notif-dropdown-header a { font-size: 0.75rem; color: #7c3aed; cursor: pointer; text-decoration: none; }
        .notif-item { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); font-size: 0.82rem; transition: var(--transition); cursor: pointer; }
        .notif-item:hover { background: var(--bg-tertiary); }
        .notif-item.unread { background: rgba(124,58,237,0.05); }
        .notif-item .title { font-weight: 600; color: var(--text-primary); margin-bottom: 0.2rem; }
        .notif-item .msg { color: var(--text-secondary); font-size: 0.78rem; }
        .notif-item .time { color: var(--text-muted); font-size: 0.7rem; margin-top: 0.2rem; }
        .notif-empty { padding: 2rem; text-align: center; color: var(--text-muted); font-size: 0.85rem; }
        .low-stock-table td, .low-stock-table th { padding: 0.6rem 0.8rem; font-size: 0.85rem; }
        @media (max-width: 900px) {
            .charts-grid { grid-template-columns: 1fr; }
        }
    </style>
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
            <a href="admin_contacts.php" class="nav-link">
                <span class="nav-icon">📧</span>
                <span>Messages</span>
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
        <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">ภาพรวม GamePro Inventory</p>
            </div>
            <div style="position:relative;">
                <button class="notif-bell" onclick="toggleNotifs()">
                    🔔
                    <?php if ($unreadNotifs > 0): ?>
                    <span class="notif-badge" id="notifBadge"><?= $unreadNotifs ?></span>
                    <?php endif; ?>
                </button>
                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-dropdown-header">
                        <h4>🔔 การแจ้งเตือน</h4>
                        <a onclick="markAllRead()">อ่านทั้งหมด</a>
                    </div>
                    <div id="notifList"><div class="notif-empty">กำลังโหลด...</div></div>
                </div>
            </div>
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
            <div class="stat-card">
                <div class="stat-icon">🛍️</div>
                <div class="stat-value"><?= number_format($totalOrders) ?></div>
                <div class="stat-label">คำสั่งซื้อทั้งหมด</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>📈 ยอดขาย 7 วันย้อนหลัง</h3>
                <canvas id="salesChart" height="220"></canvas>
            </div>
            <div class="chart-card">
                <h3>🏆 Top 5 สินค้าขายดี</h3>
                <canvas id="topChart" height="220"></canvas>
            </div>
        </div>

        <!-- Low Stock Warning -->
        <?php if (!empty($lowStockProducts) || !empty($outOfStock)): ?>
        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header">
                <h2 class="card-title">⚠️ สินค้าใกล้หมด / หมดแล้ว</h2>
                <span class="badge badge-danger"><?= count($lowStockProducts) + count($outOfStock) ?> รายการ</span>
            </div>
            <div class="table-wrapper">
                <table class="low-stock-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อสินค้า</th>
                            <th class="text-right">ราคา</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($outOfStock as $p): ?>
                        <tr>
                            <td><span class="badge badge-info">#<?= $p['id'] ?></span></td>
                            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                            <td class="text-right price">฿<?= number_format($p['price'], 2) ?></td>
                            <td class="text-center"><span class="badge badge-danger">0</span></td>
                            <td class="text-center"><span class="badge badge-danger">หมดแล้ว</span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php foreach ($lowStockProducts as $p): ?>
                        <tr>
                            <td><span class="badge badge-info">#<?= $p['id'] ?></span></td>
                            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                            <td class="text-right price">฿<?= number_format($p['price'], 2) ?></td>
                            <td class="text-center"><span class="badge badge-warning"><?= $p['stock_quantity'] ?></span></td>
                            <td class="text-center"><span class="badge badge-warning">ใกล้หมด</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

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

<script>
// Sales Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
new Chart(salesCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'ยอดขาย (฿)',
            data: <?= json_encode($data) ?>,
            backgroundColor: 'rgba(124, 58, 237, 0.7)',
            borderColor: '#7c3aed',
            borderWidth: 1,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => '฿' + ctx.parsed.y.toLocaleString('th-TH', {minimumFractionDigits: 2})
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: v => '฿' + (v/1000).toFixed(0) + 'K'
                }
            }
        }
    }
});

// Top Products Chart
<?php
$topLabels = array_map(fn($p) => mb_substr($p['name'], 0, 15), $topProducts);
$topData = array_map(fn($p) => (int)$p['total_sold'], $topProducts);
?>
const topCtx = document.getElementById('topChart').getContext('2d');
new Chart(topCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($topLabels) ?>,
        datasets: [{
            data: <?= json_encode($topData) ?>,
            backgroundColor: ['#7c3aed', '#2563eb', '#16a34a', '#f59e0b', '#ef4444'],
            borderWidth: 2,
            borderColor: getComputedStyle(document.documentElement).getPropertyValue('--bg-secondary').trim() || '#fff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { font: { size: 11 }, padding: 12 }
            }
        }
    }
});

// Notifications
function toggleNotifs() {
    const dd = document.getElementById('notifDropdown');
    dd.classList.toggle('open');
    if (dd.classList.contains('open')) loadNotifs();
}

async function loadNotifs() {
    try {
        const r = await fetch('notifications_api.php?action=get');
        const d = await r.json();
        const list = document.getElementById('notifList');
        if (!d.notifications || d.notifications.length === 0) {
            list.innerHTML = '<div class="notif-empty">ไม่มีการแจ้งเตือน</div>';
            return;
        }
        list.innerHTML = d.notifications.map(n => `
            <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}" onclick="markRead(${n.id}, this)">
                <div class="title">${n.title}</div>
                <div class="msg">${n.message}</div>
                <div class="time">${n.created_at}</div>
            </div>
        `).join('');
    } catch(e) {
        document.getElementById('notifList').innerHTML = '<div class="notif-empty">โหลดไม่สำเร็จ</div>';
    }
}

async function markRead(id, el) {
    const form = new FormData();
    form.append('action', 'mark_read');
    form.append('id', id);
    await fetch('notifications_api.php', { method: 'POST', body: form });
    if (el) el.classList.remove('unread');
    updateBadge();
}

async function markAllRead() {
    const form = new FormData();
    form.append('action', 'mark_all_read');
    await fetch('notifications_api.php', { method: 'POST', body: form });
    loadNotifs();
    updateBadge();
}

async function updateBadge() {
    const r = await fetch('notifications_api.php?action=unread_count');
    const d = await r.json();
    const badge = document.getElementById('notifBadge');
    if (badge) {
        badge.textContent = d.count;
        badge.style.display = d.count > 0 ? '' : 'none';
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', e => {
    if (!e.target.closest('.notif-bell') && !e.target.closest('.notif-dropdown')) {
        document.getElementById('notifDropdown').classList.remove('open');
    }
});

// Auto check stock alerts
fetch('notifications_api.php?action=check_stock').catch(()=>{});
</script>
<a href="javascript:history.back()" class="btn-back-float" title="ย้อนกลับ">←</a>
</body>
</html>
