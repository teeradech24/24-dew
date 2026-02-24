<?php session_start(); require_once 'db.php';
$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
$statusLabels = ['pending'=>'⏳ รอดำเนินการ','confirmed'=>'✅ ยืนยันแล้ว','shipped'=>'🚚 จัดส่งแล้ว','completed'=>'🎉 เสร็จสิ้น','cancelled'=>'❌ ยกเลิก'];
$statusColors = ['pending'=>'#f59e0b','confirmed'=>'#16a34a','shipped'=>'#2563eb','completed'=>'#059669','cancelled'=>'#dc2626'];
?><!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📋 ประวัติคำสั่งซื้อ — GamePro</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .showcase-body { min-height: 100vh; background: var(--bg-primary); }
        .top-nav { background: var(--bg-secondary); border-bottom: 1px solid var(--border); padding: 0 2rem; display: flex; align-items: center; justify-content: space-between; height: 64px; position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow); }
        .top-nav-logo { display: flex; align-items: center; gap: 0.6rem; font-weight: 800; font-size: 1.2rem; color: var(--text-primary); text-decoration: none; }
        .top-nav-logo .logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, #1a1a1a, #444); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
        .top-nav-links { display: flex; align-items: center; gap: 0.5rem; }
        .top-nav-links a { color: var(--text-secondary); font-size: 0.85rem; font-weight: 500; padding: 0.4rem 0.85rem; border-radius: var(--radius-sm); transition: var(--transition); }
        .top-nav-links a:hover { color: var(--text-primary); background: var(--bg-tertiary); }
        .theme-toggle { width: 36px; height: 36px; background: var(--bg-tertiary); border: 1px solid var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 1rem; transition: var(--transition); }
        .theme-toggle:hover { background: var(--bg-primary); transform: scale(1.1); }
        [data-theme='dark'] .top-nav-logo .logo-icon { background: linear-gradient(135deg, #333, #555); }
        .cart-badge { background: #dc2626; color: #fff; font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 100px; margin-left: -0.3rem; }
        .container { max-width: 900px; margin: 0 auto; padding: 2rem; }
        .page-title { font-size: 1.5rem; font-weight: 800; color: var(--text-primary); margin-bottom: 1.5rem; }
        .order-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 1rem; overflow: hidden; }
        .order-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); cursor: pointer; }
        .order-header:hover { background: var(--bg-tertiary); }
        .order-num { font-weight: 700; font-size: 0.95rem; color: var(--text-primary); }
        .order-date { font-size: 0.78rem; color: var(--text-muted); }
        .order-status { padding: 0.2rem 0.6rem; border-radius: 100px; font-size: 0.72rem; font-weight: 700; color: #fff; }
        .order-total { font-weight: 800; font-size: 1rem; color: var(--text-primary); }
        .order-body { padding: 1rem 1.25rem; display: none; border-top: 1px solid var(--border); }
        .order-body.open { display: block; }
        .order-item { display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border); font-size: 0.85rem; }
        .order-item:last-child { border-bottom: none; }
        .order-item-name { color: var(--text-primary); font-weight: 600; }
        .order-item-detail { color: var(--text-muted); }
        .empty-state { text-align: center; padding: 4rem 2rem; }
        .empty-state .icon { font-size: 4rem; margin-bottom: 1rem; }
        .empty-state p { color: var(--text-muted); margin-bottom: 1rem; }
        .empty-state a { display: inline-block; padding: 0.7rem 1.5rem; background: #1a1a1a; color: #fff; border-radius: var(--radius-sm); font-weight: 600; }
        [data-theme='dark'] .empty-state a { background: #f0f0f0; color: #1a1a1a; }
    </style>
</head>
<body class="showcase-body">
<nav class="top-nav">
    <a href="showcase.php" class="top-nav-logo"><div class="logo-icon">🎮</div><span>GamePro</span></a>
    <div class="top-nav-links">
        <a href="showcase.php">🏠 หน้าแรก</a>
        <a href="cart.php">🛒 ตะกร้า <span class="cart-badge" id="cartBadge">0</span></a>
        <a href="orders.php" style="color:var(--text-primary);font-weight:600">📋 คำสั่งซื้อ</a>
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <a href="profile.php">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'โปรไฟล์') ?></a>
            <a href="logout.php">🚪 ออก</a>
        <?php else: ?>
            <a href="login.php">🔐 เข้าสู่ระบบ</a>
        <?php endif; ?>
        <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
    </div>
</nav>

<div class="container">
    <h1 class="page-title">📋 ประวัติคำสั่งซื้อ</h1>
    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <div class="icon">📋</div>
            <p>ยังไม่มีคำสั่งซื้อ</p>
            <a href="showcase.php">🏠 เลือกซื้อสินค้า</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $o):
            $items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $items->execute([$o['id']]);
            $orderItems = $items->fetchAll();
            $color = $statusColors[$o['status']] ?? '#666';
            $label = $statusLabels[$o['status']] ?? $o['status'];
        ?>
        <div class="order-card">
            <div class="order-header" onclick="this.nextElementSibling.classList.toggle('open')">
                <div>
                    <div class="order-num"><?= $o['order_number'] ?></div>
                    <div class="order-date"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></div>
                </div>
                <div style="display:flex;align-items:center;gap:1rem">
                    <span class="order-status" style="background:<?= $color ?>"><?= $label ?></span>
                    <span class="order-total">฿<?= number_format($o['total_amount'], 2) ?></span>
                </div>
            </div>
            <div class="order-body">
                <?php foreach ($orderItems as $item): ?>
                <div class="order-item">
                    <span class="order-item-name"><?= htmlspecialchars($item['product_name']) ?></span>
                    <span class="order-item-detail"><?= $item['quantity'] ?> × ฿<?= number_format($item['price'], 2) ?> = ฿<?= number_format($item['subtotal'], 2) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function toggleTheme() {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-theme') === 'dark';
    html.setAttribute('data-theme', isDark ? 'light' : 'dark');
    localStorage.setItem('theme', isDark ? 'light' : 'dark');
    document.querySelectorAll('.theme-toggle').forEach(b => b.textContent = isDark ? '🌙' : '☀️');
}
(function() {
    const saved = localStorage.getItem('theme');
    if (saved) document.documentElement.setAttribute('data-theme', saved);
    document.querySelectorAll('.theme-toggle').forEach(b => b.textContent = saved === 'dark' ? '☀️' : '🌙');
})();
(async function() {
    try { const r = await fetch('cart_api.php?action=count'); const d = await r.json(); document.getElementById('cartBadge').textContent = d.count||0; } catch(e){}
})();
</script>
<a href="javascript:history.back()" class="btn-back-float" title="ย้อนกลับ">←</a>
</body>
</html>
