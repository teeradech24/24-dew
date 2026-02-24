<?php
require_once 'db.php';

// Fetch categories with products
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Fetch all products grouped by category
$productsByCategory = [];
$allProducts = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY c.name, p.name
")->fetchAll();

foreach ($allProducts as $product) {
    $productsByCategory[$product['category_id']][] = $product;
}

// Category icons mapping
$categoryIcons = [
    'Graphics Cards' => '🖥️',
    'Processors' => '⚡',
    'RAM' => '🧩',
    'Storage' => '💾',
    'Monitors' => '🖥️',
    'Peripherals' => '🎮',
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamePro — ร้านขายอุปกรณ์เกมมิ่ง</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* ---- Showcase Layout (no sidebar) ---- */
        .showcase-body {
            min-height: 100vh;
            background: var(--bg-primary);
        }

        /* ---- Top Navbar ---- */
        .top-nav {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow);
        }
        .top-nav-logo {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--text-primary);
        }
        .top-nav-logo .logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #1a1a1a, #444444);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        .top-nav-links {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .top-nav-links a {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
            padding: 0.4rem 0.85rem;
            border-radius: var(--radius-sm);
            transition: var(--transition);
        }
        .top-nav-links a:hover {
            color: var(--text-primary);
            background: var(--bg-tertiary);
        }

        /* ---- Hero Banner ---- */
        .hero {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            padding: 3rem 2rem;
            text-align: center;
        }
        .hero h1 {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        .hero p {
            color: var(--text-secondary);
            font-size: 1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* ---- Category Navigation ---- */
        .cat-nav {
            display: flex;
            gap: 0.5rem;
            padding: 1.25rem 2rem;
            overflow-x: auto;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 64px;
            z-index: 90;
        }
        .cat-nav a {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 1rem;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 100px;
            color: var(--text-secondary);
            font-size: 0.82rem;
            font-weight: 600;
            white-space: nowrap;
            transition: var(--transition);
        }
        .cat-nav a:hover, .cat-nav a.active {
            background: var(--accent);
            color: #ffffff;
            border-color: var(--accent);
        }

        /* ---- Main Showcase Content ---- */
        .showcase-content {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* ---- Category Section ---- */
        .category-section {
            margin-bottom: 3rem;
            scroll-margin-top: 140px;
        }
        .category-header {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--border);
        }
        .category-header h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .category-header .count {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.6rem;
            border-radius: 100px;
        }

        /* ---- Product Grid ---- */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1.25rem;
        }

        /* ---- Product Card ---- */
        .product-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: #999;
        }
        .product-img {
            width: 100%;
            height: 200px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            overflow: hidden;
        }
        .product-img img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 0.5rem;
        }
        .product-info {
            padding: 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .product-category-tag {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 0.3rem;
        }
        .product-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.3rem;
            line-height: 1.3;
        }
        .product-desc {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
            flex: 1;
            line-height: 1.4;
        }
        .product-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .product-price {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-primary);
        }
        .product-stock {
            font-size: 0.75rem;
            font-weight: 600;
        }
        .stock-ok {
            color: #2d8a4e;
        }
        .stock-low {
            color: var(--danger);
        }
        .stock-out {
            color: var(--text-muted);
        }

        /* ---- Responsive ---- */
        @media (max-width: 768px) {
            .hero h1 { font-size: 1.5rem; }
            .showcase-content { padding: 1rem; }
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 0.75rem;
            }
            .product-img { height: 130px; font-size: 2.5rem; }
            .top-nav { padding: 0 1rem; }
            .cat-nav { padding: 0.75rem 1rem; }
        }
    </style>
</head>
<body class="showcase-body">

<!-- Top Navigation Bar -->
<nav class="top-nav">
    <div class="top-nav-logo">
        <div class="logo-icon">🎮</div>
        <span>GamePro</span>
    </div>
    <div class="top-nav-links">
        <a href="showcase.php">🏠 หน้าแรก</a>
        <a href="login.php">🔐 เข้าสู่ระบบ</a>
    </div>
</nav>

<!-- Hero Banner -->
<div class="hero">
    <h1>🎮 GamePro Store</h1>
    <p>จำหน่ายอุปกรณ์เกมมิ่งและคอมพิวเตอร์ครบวงจร ราคาดี มีประกัน</p>
</div>

<!-- Category Quick Nav -->
<div class="cat-nav">
    <?php foreach ($categories as $cat): ?>
        <?php $icon = $categoryIcons[$cat['name']] ?? '📁'; ?>
        <a href="#cat-<?= $cat['id'] ?>"><?= $icon ?> <?= htmlspecialchars($cat['name']) ?></a>
    <?php endforeach; ?>
</div>

<!-- Main Content -->
<div class="showcase-content">
    <?php foreach ($categories as $cat): ?>
        <?php 
        $products = $productsByCategory[$cat['id']] ?? [];
        if (empty($products)) continue;
        $icon = $categoryIcons[$cat['name']] ?? '📁';
        ?>
        <section class="category-section" id="cat-<?= $cat['id'] ?>">
            <div class="category-header">
                <span style="font-size:1.4rem;"><?= $icon ?></span>
                <h2><?= htmlspecialchars($cat['name']) ?></h2>
                <span class="count"><?= count($products) ?> รายการ</span>
            </div>
            <div class="product-grid">
                <?php foreach ($products as $p): ?>
                <div class="product-card">
                    <div class="product-img">
                        <?php if (!empty($p['image_url'])): ?>
                            <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                        <?php else: ?>
                            <?= $icon ?>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-category-tag"><?= htmlspecialchars($cat['name']) ?></div>
                        <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="product-desc"><?= htmlspecialchars($p['description'] ?: 'ไม่มีรายละเอียด') ?></div>
                        <div class="product-footer">
                            <div class="product-price">฿<?= number_format($p['price'], 2) ?></div>
                            <?php if ($p['stock_quantity'] <= 0): ?>
                                <span class="product-stock stock-out">สินค้าหมด</span>
                            <?php elseif ($p['stock_quantity'] < 5): ?>
                                <span class="product-stock stock-low">เหลือ <?= $p['stock_quantity'] ?> ชิ้น</span>
                            <?php else: ?>
                                <span class="product-stock stock-ok">มีสินค้า</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<!-- Footer -->
<footer style="background:var(--bg-secondary);border-top:1px solid var(--border);padding:1.5rem 2rem;text-align:center;color:var(--text-muted);font-size:0.8rem;">
    © 2026 GamePro Store — ระบบจัดการร้านขายอุปกรณ์เกมมิ่ง
</footer>

</body>
</html>
