<?php
session_start();
require_once 'db.php';

$ids = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));
$products = [];
if (!empty($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name,
               COALESCE(AVG(r.rating), 0) as avg_rating,
               COUNT(r.id) as review_count
        FROM products p
        JOIN categories c ON p.category_id = c.id
        LEFT JOIN product_reviews r ON p.id = r.product_id
        WHERE p.id IN ($placeholders)
        GROUP BY p.id
    ");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();
}

$categoryIcons = [
    'Graphics Cards' => '🖥️', 'Processors' => '⚡', 'RAM' => '🧩',
    'Storage' => '💾', 'Monitors' => '🖥️', 'Peripherals' => '🎮',
];
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚖️ เทียบสินค้า — GamePro</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .showcase-body { min-height: 100vh; background: var(--bg-primary); }
        .top-nav { background: var(--bg-secondary); border-bottom: 1px solid var(--border); padding: 0 2rem; display: flex; align-items: center; justify-content: space-between; height: 64px; position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow); }
        .top-nav-logo { display: flex; align-items: center; gap: 0.6rem; font-weight: 800; font-size: 1.2rem; color: var(--text-primary); text-decoration: none; }
        .top-nav-logo .logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, #1a1a1a, #444); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
        .top-nav-links { display: flex; align-items: center; gap: 0.5rem; }
        .top-nav-links a { color: var(--text-secondary); font-size: 0.85rem; font-weight: 500; padding: 0.4rem 0.85rem; border-radius: var(--radius-sm); transition: var(--transition); text-decoration: none; }
        .top-nav-links a:hover { color: var(--text-primary); background: var(--bg-tertiary); }
        .theme-toggle { width: 36px; height: 36px; background: var(--bg-tertiary); border: 1px solid var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 1rem; transition: var(--transition); }
        .theme-toggle:hover { background: var(--bg-primary); transform: scale(1.1); }
        [data-theme='dark'] .top-nav-logo .logo-icon { background: linear-gradient(135deg, #333, #555); }
        .cart-badge { background: #dc2626; color: #fff; font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 100px; margin-left: -0.3rem; }

        .compare-container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .compare-title { font-size: 1.5rem; font-weight: 800; color: var(--text-primary); margin-bottom: 1.5rem; }
        .compare-badge { background: #7c3aed; color: #fff; font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 100px; margin-left: 0.5rem; }

        .compare-table { width: 100%; border-collapse: collapse; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
        .compare-table th { background: var(--bg-tertiary); padding: 0.85rem 1rem; text-align: left; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); width: 120px; vertical-align: top; }
        .compare-table td { padding: 0.85rem 1rem; border-bottom: 1px solid var(--border); color: var(--text-primary); font-size: 0.9rem; text-align: center; vertical-align: middle; }
        .compare-table tr:last-child td { border-bottom: none; }
        .compare-img { width: 120px; height: 120px; object-fit: contain; border-radius: var(--radius-sm); background: #fff; padding: 8px; }
        [data-theme='dark'] .compare-img { background: #222; }
        .compare-emoji { font-size: 3rem; }
        .compare-name { font-weight: 700; font-size: 0.95rem; }
        .compare-price { font-weight: 800; font-size: 1.1rem; color: var(--text-primary); }
        .compare-stock { font-weight: 600; }
        .stock-ok { color: #16a34a; }
        .stock-low { color: #f59e0b; }
        .stock-out { color: #ef4444; }
        .compare-stars { color: #f59e0b; font-size: 1rem; }
        .compare-remove { background: #ef4444; color: #fff; border: none; padding: 0.3rem 0.7rem; border-radius: var(--radius-sm); font-size: 0.75rem; font-weight: 600; cursor: pointer; }
        .compare-remove:hover { background: #dc2626; }
        .btn-add-compare { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.7rem 1.2rem; background: #7c3aed; color: #fff; border: none; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: var(--transition); }
        .btn-add-compare:hover { background: #6d28d9; transform: translateY(-1px); }

        .empty-compare { text-align: center; padding: 4rem 2rem; }
        .empty-compare .icon { font-size: 4rem; margin-bottom: 1rem; }
        .empty-compare p { color: var(--text-muted); margin-bottom: 1rem; }
        .empty-compare a { display: inline-block; padding: 0.7rem 1.5rem; background: #1a1a1a; color: #fff; border-radius: var(--radius-sm); font-weight: 600; text-decoration: none; }
        [data-theme='dark'] .empty-compare a { background: #f0f0f0; color: #1a1a1a; }
    </style>
</head>
<body class="showcase-body">

<nav class="top-nav">
    <a href="showcase.php" class="top-nav-logo"><div class="logo-icon">🎮</div><span>GamePro</span></a>
    <div class="top-nav-links">
        <a href="showcase.php">🏠 หน้าแรก</a>
        <a href="cart.php">🛒 ตะกร้า <span class="cart-badge" id="cartBadge">0</span></a>
        <a href="compare.php" style="color:var(--text-primary);font-weight:600">⚖️ เทียบสินค้า</a>
        <a href="contact.php">📧 ติดต่อเรา</a>
        <a href="login.php">🔐 เข้าสู่ระบบ</a>
        <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
    </div>
</nav>

<div class="compare-container">
    <h1 class="compare-title">⚖️ เทียบสินค้า <span class="compare-badge" id="compareBadge"><?= count($products) ?></span></h1>

    <?php if (empty($products)): ?>
    <div class="empty-compare">
        <div class="icon">⚖️</div>
        <p>ยังไม่ได้เลือกสินค้าเปรียบเทียบ</p>
        <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:1.5rem;">กดปุ่ม "⚖️ เทียบ" จากหน้าสินค้าเพื่อเพิ่มสินค้ามาเทียบ</p>
        <a href="showcase.php">🏠 เลือกสินค้า</a>
    </div>
    <?php else: ?>
    <table class="compare-table">
        <tr>
            <th>รูปภาพ</th>
            <?php foreach ($products as $p): ?>
            <td>
                <?php if (!empty($p['image_url'])): ?>
                    <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="compare-img">
                <?php else: ?>
                    <span class="compare-emoji"><?= $categoryIcons[$p['category_name']] ?? '📁' ?></span>
                <?php endif; ?>
            </td>
            <?php endforeach; ?>
        </tr>
        <tr>
            <th>ชื่อสินค้า</th>
            <?php foreach ($products as $p): ?>
            <td class="compare-name"><a href="product_detail.php?id=<?= $p['id'] ?>" style="color:var(--text-primary)"><?= htmlspecialchars($p['name']) ?></a></td>
            <?php endforeach; ?>
        </tr>
        <tr>
            <th>หมวดหมู่</th>
            <?php foreach ($products as $p): ?>
            <td><?= $categoryIcons[$p['category_name']] ?? '' ?> <?= htmlspecialchars($p['category_name']) ?></td>
            <?php endforeach; ?>
        </tr>
        <tr>
            <th>ราคา</th>
            <?php foreach ($products as $p): ?>
            <td class="compare-price">฿<?= number_format($p['price'], 2) ?></td>
            <?php endforeach; ?>
        </tr>
        <tr>
            <th>รายละเอียด</th>
            <?php foreach ($products as $p): ?>
            <td style="font-size:0.82rem;color:var(--text-secondary)"><?= htmlspecialchars($p['description'] ?? '-') ?></td>
            <?php endforeach; ?>
        </tr>
        <tr>
            <th>Stock</th>
            <?php foreach ($products as $p):
                $cls = $p['stock_quantity'] <= 0 ? 'stock-out' : ($p['stock_quantity'] < 5 ? 'stock-low' : 'stock-ok');
            ?>
            <td class="compare-stock <?= $cls ?>"><?= $p['stock_quantity'] ?> ชิ้น</td>
            <?php endforeach; ?>
        </tr>
        <tr>
            <th>คะแนนรีวิว</th>
            <?php foreach ($products as $p):
                $stars = '';
                for ($i = 1; $i <= 5; $i++) $stars .= $i <= round($p['avg_rating']) ? '★' : '☆';
            ?>
            <td><span class="compare-stars"><?= $stars ?></span> <span style="font-size:0.8rem;color:var(--text-muted)">(<?= $p['review_count'] ?> รีวิว)</span></td>
            <?php endforeach; ?>
        </tr>
        <tr>
            <th>จัดการ</th>
            <?php foreach ($products as $p): ?>
            <td>
                <button class="compare-remove" onclick="removeCompare(<?= $p['id'] ?>)">🗑️ นำออก</button>
            </td>
            <?php endforeach; ?>
        </tr>
    </table>

    <div style="margin-top:1.5rem;display:flex;gap:0.75rem;">
        <a href="showcase.php" class="btn-add-compare">+ เพิ่มสินค้าเปรียบเทียบ</a>
        <button onclick="clearCompare()" style="padding:0.7rem 1.2rem;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-sm);font-size:0.85rem;font-weight:600;cursor:pointer;color:var(--text-primary);">🗑️ ล้างทั้งหมด</button>
    </div>
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

function getCompareList() {
    return JSON.parse(localStorage.getItem('compare') || '[]');
}

function removeCompare(id) {
    let list = getCompareList().filter(x => x !== id);
    localStorage.setItem('compare', JSON.stringify(list));
    window.location.href = 'compare.php?ids=' + list.join(',');
}

function clearCompare() {
    localStorage.setItem('compare', '[]');
    window.location.href = 'compare.php';
}

// If no ids in URL, load from localStorage
(function() {
    const url = new URL(window.location);
    if (!url.searchParams.get('ids')) {
        const list = getCompareList();
        if (list.length > 0) {
            window.location.href = 'compare.php?ids=' + list.join(',');
        }
    }
})();

(async function() {
    try { const r = await fetch('cart_api.php?action=count'); const d = await r.json(); document.getElementById('cartBadge').textContent = d.count||0; } catch(e){}
})();
</script>
<a href="javascript:history.back()" class="btn-back-float" title="ย้อนกลับ">←</a>
</body>
</html>
