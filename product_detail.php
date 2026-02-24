<?php
require_once 'db.php';

// Get product ID
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: showcase.php');
    exit;
}

// Fetch product
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: showcase.php');
    exit;
}

// Related products (same category, exclude current)
$relatedStmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? ORDER BY RAND() LIMIT 4");
$relatedStmt->execute([$product['category_id'], $id]);
$relatedProducts = $relatedStmt->fetchAll();

$categoryIcons = [
    'Graphics Cards' => '🖥️', 'Processors' => '⚡', 'RAM' => '🧩',
    'Storage' => '💾', 'Monitors' => '🖥️', 'Peripherals' => '🎮',
];
$icon = $categoryIcons[$product['category_name']] ?? '📁';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> — GamePro</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .showcase-body { min-height: 100vh; background: var(--bg-primary); }

        .top-nav { background: var(--bg-secondary); border-bottom: 1px solid var(--border); padding: 0 2rem; display: flex; align-items: center; justify-content: space-between; height: 64px; position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow); }
        .top-nav-logo { display: flex; align-items: center; gap: 0.6rem; font-weight: 800; font-size: 1.2rem; color: var(--text-primary); text-decoration: none; }
        .top-nav-logo .logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, #1a1a1a, #444); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
        .top-nav-links { display: flex; align-items: center; gap: 0.5rem; }
        .top-nav-links a { color: var(--text-secondary); font-size: 0.85rem; font-weight: 500; padding: 0.4rem 0.85rem; border-radius: var(--radius-sm); transition: var(--transition); }
        .top-nav-links a:hover { color: var(--text-primary); background: var(--bg-tertiary); }

        .detail-container { max-width: 1100px; margin: 0 auto; padding: 2rem; }

        .breadcrumb { display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; color: var(--text-muted); margin-bottom: 1.5rem; flex-wrap: wrap; }
        .breadcrumb a { color: var(--text-secondary); }
        .breadcrumb a:hover { color: var(--text-primary); }

        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2.5rem; margin-bottom: 3rem; }

        .detail-image { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; padding: 2rem; min-height: 400px; overflow: hidden; }
        .detail-image img { max-width: 100%; max-height: 380px; object-fit: contain; }
        .detail-image .emoji-fallback { font-size: 8rem; }

        .detail-info { display: flex; flex-direction: column; gap: 1rem; }
        .detail-category { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); }
        .detail-name { font-size: 1.8rem; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
        .detail-desc { font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; }
        .detail-price { font-size: 2rem; font-weight: 800; color: var(--text-primary); }
        .detail-stock { font-size: 0.9rem; font-weight: 600; padding: 0.5rem 1rem; border-radius: var(--radius-sm); display: inline-block; width: fit-content; }
        .detail-stock.in-stock { background: rgba(45, 138, 78, 0.1); color: #2d8a4e; }
        .detail-stock.low-stock { background: var(--danger-bg); color: var(--danger); }
        .detail-stock.out-of-stock { background: var(--bg-tertiary); color: var(--text-muted); }

        .detail-specs { margin-top: 0.5rem; }
        .detail-specs table { width: 100%; border-collapse: collapse; }
        .detail-specs td { padding: 0.6rem 0; border-bottom: 1px solid var(--border); font-size: 0.85rem; }
        .detail-specs td:first-child { color: var(--text-muted); width: 35%; }
        .detail-specs td:last-child { color: var(--text-primary); font-weight: 500; }

        .related-section { margin-top: 2rem; }
        .related-section h3 { font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-primary); }
        .related-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
        .related-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; transition: var(--transition); text-decoration: none; }
        .related-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
        .related-card .r-img { height: 140px; background: #fff; display: flex; align-items: center; justify-content: center; overflow: hidden; border-bottom: 1px solid var(--border); }
        .related-card .r-img img { max-width: 100%; max-height: 100%; object-fit: contain; padding: 0.5rem; }
        .related-card .r-info { padding: 0.75rem; }
        .related-card .r-name { font-size: 0.82rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.3rem; line-height: 1.3; }
        .related-card .r-price { font-size: 0.95rem; font-weight: 800; color: var(--text-primary); }

        .back-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.6rem 1.2rem; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text-secondary); font-size: 0.85rem; font-weight: 600; transition: var(--transition); margin-bottom: 1.5rem; }
        .back-btn:hover { background: var(--bg-tertiary); color: var(--text-primary); }

        @media (max-width: 768px) {
            .detail-grid { grid-template-columns: 1fr; gap: 1.5rem; }
            .detail-container { padding: 1rem; }
            .detail-name { font-size: 1.3rem; }
            .detail-image { min-height: 250px; }
        }

        /* Footer */
        .showcase-footer { background: var(--bg-secondary); border-top: 1px solid var(--border); padding: 2.5rem 2rem 1.5rem; }
        .footer-grid { max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 2rem; margin-bottom: 1.5rem; }
        .footer-brand h3 { font-size: 1.1rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.5rem; }
        .footer-brand p { font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; }
        .footer-col h4 { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-primary); margin-bottom: 0.75rem; }
        .footer-col a { display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.4rem; transition: var(--transition); }
        .footer-col a:hover { color: var(--text-primary); }
        .footer-bottom { max-width: 1100px; margin: 0 auto; padding-top: 1rem; border-top: 1px solid var(--border); text-align: center; font-size: 0.75rem; color: var(--text-muted); }
        @media (max-width: 768px) { .footer-grid { grid-template-columns: 1fr 1fr; } }
    </style>
</head>
<body class="showcase-body">

<nav class="top-nav">
    <a href="showcase.php" class="top-nav-logo">
        <div class="logo-icon">🎮</div>
        <span>GamePro</span>
    </a>
    <div class="top-nav-links">
        <a href="showcase.php">🏠 หน้าแรก</a>
        <a href="login.php">🔐 เข้าสู่ระบบ</a>
    </div>
</nav>

<div class="detail-container">
    <div class="breadcrumb">
        <a href="showcase.php">หน้าแรก</a> <span>›</span>
        <a href="showcase.php#cat-<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name']) ?></a> <span>›</span>
        <span style="color:var(--text-primary)"><?= htmlspecialchars($product['name']) ?></span>
    </div>

    <div class="detail-grid">
        <div class="detail-image">
            <?php if (!empty($product['image_url'])): ?>
                <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            <?php else: ?>
                <span class="emoji-fallback"><?= $icon ?></span>
            <?php endif; ?>
        </div>

        <div class="detail-info">
            <div class="detail-category"><?= $icon ?> <?= htmlspecialchars($product['category_name']) ?></div>
            <h1 class="detail-name"><?= htmlspecialchars($product['name']) ?></h1>
            <p class="detail-desc"><?= htmlspecialchars($product['description'] ?: 'ไม่มีรายละเอียดเพิ่มเติม') ?></p>
            <div class="detail-price">฿<?= number_format($product['price'], 2) ?></div>

            <?php if ($product['stock_quantity'] <= 0): ?>
                <div class="detail-stock out-of-stock">❌ สินค้าหมด</div>
            <?php elseif ($product['stock_quantity'] < 5): ?>
                <div class="detail-stock low-stock">⚠️ เหลือ <?= $product['stock_quantity'] ?> ชิ้น</div>
            <?php else: ?>
                <div class="detail-stock in-stock">✅ มีสินค้า (<?= $product['stock_quantity'] ?> ชิ้น)</div>
            <?php endif; ?>

            <div class="detail-specs">
                <table>
                    <tr><td>รหัสสินค้า</td><td>#<?= $product['id'] ?></td></tr>
                    <tr><td>หมวดหมู่</td><td><?= htmlspecialchars($product['category_name']) ?></td></tr>
                    <tr><td>ราคา</td><td>฿<?= number_format($product['price'], 2) ?></td></tr>
                    <tr><td>สต็อก</td><td><?= $product['stock_quantity'] ?> ชิ้น</td></tr>
                    <tr><td>วันที่เพิ่ม</td><td><?= date('d/m/Y', strtotime($product['created_at'])) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <?php if (!empty($relatedProducts)): ?>
    <div class="related-section">
        <h3>🔗 สินค้าที่เกี่ยวข้อง</h3>
        <div class="related-grid">
            <?php foreach ($relatedProducts as $rp): ?>
            <a href="product_detail.php?id=<?= $rp['id'] ?>" class="related-card">
                <div class="r-img">
                    <?php if (!empty($rp['image_url'])): ?>
                        <img src="<?= htmlspecialchars($rp['image_url']) ?>" alt="<?= htmlspecialchars($rp['name']) ?>">
                    <?php else: ?>
                        <span style="font-size:2.5rem"><?= $icon ?></span>
                    <?php endif; ?>
                </div>
                <div class="r-info">
                    <div class="r-name"><?= htmlspecialchars($rp['name']) ?></div>
                    <div class="r-price">฿<?= number_format($rp['price'], 2) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Footer -->
<footer class="showcase-footer">
    <div class="footer-grid">
        <div class="footer-brand">
            <h3>🎮 GamePro Store</h3>
            <p>ร้านจำหน่ายอุปกรณ์เกมมิ่งและคอมพิวเตอร์ครบวงจร ราคาดี มีประกัน สินค้าคุณภาพ บริการหลังการขายดีเยี่ยม</p>
        </div>
        <div class="footer-col">
            <h4>หมวดหมู่</h4>
            <a href="showcase.php#cat-1">Graphics Cards</a>
            <a href="showcase.php#cat-2">Processors</a>
            <a href="showcase.php#cat-3">RAM</a>
            <a href="showcase.php#cat-4">Storage</a>
        </div>
        <div class="footer-col">
            <h4>เพิ่มเติม</h4>
            <a href="showcase.php#cat-5">Monitors</a>
            <a href="showcase.php#cat-6">Peripherals</a>
            <a href="showcase.php">สินค้าทั้งหมด</a>
        </div>
        <div class="footer-col">
            <h4>ติดต่อเรา</h4>
            <a href="#">📞 02-123-4567</a>
            <a href="#">📧 support@gamepro.co.th</a>
            <a href="#">📍 กรุงเทพมหานคร</a>
            <a href="#">🕐 10:00 – 20:00 น.</a>
        </div>
    </div>
    <div class="footer-bottom">© 2026 GamePro Store — ระบบจัดการร้านขายอุปกรณ์เกมมิ่ง</div>
</footer>

</body>
</html>
