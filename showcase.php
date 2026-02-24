<?php
require_once 'db.php';

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

// Best sellers (top 6 most expensive as "featured")
$bestSellers = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY p.price DESC 
    LIMIT 6
")->fetchAll();

$categoryIcons = [
    'Graphics Cards' => '🖥️', 'Processors' => '⚡', 'RAM' => '🧩',
    'Storage' => '💾', 'Monitors' => '🖥️', 'Peripherals' => '🎮',
];

// Search
$searchQuery = trim($_GET['q'] ?? '');
$filterCat = (int)($_GET['cat'] ?? 0);
if ($searchQuery || $filterCat) {
    $sql = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE 1=1";
    $params = [];
    if ($searchQuery) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$searchQuery%";
        $params[] = "%$searchQuery%";
    }
    if ($filterCat) {
        $sql .= " AND p.category_id = ?";
        $params[] = $filterCat;
    }
    $sql .= " ORDER BY p.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $searchResults = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamePro — ร้านขายอุปกรณ์เกมมิ่ง</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .showcase-body { min-height: 100vh; background: var(--bg-primary); }

        /* ---- Top Nav ---- */
        .top-nav { background: var(--bg-secondary); border-bottom: 1px solid var(--border); padding: 0 2rem; display: flex; align-items: center; justify-content: space-between; height: 64px; position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow); }
        .top-nav-logo { display: flex; align-items: center; gap: 0.6rem; font-weight: 800; font-size: 1.2rem; color: var(--text-primary); text-decoration: none; }
        .top-nav-logo .logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, #1a1a1a, #444); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
        .top-nav-links { display: flex; align-items: center; gap: 0.5rem; }
        .top-nav-links a { color: var(--text-secondary); font-size: 0.85rem; font-weight: 500; padding: 0.4rem 0.85rem; border-radius: var(--radius-sm); transition: var(--transition); }
        .top-nav-links a:hover { color: var(--text-primary); background: var(--bg-tertiary); }

        /* ---- Banner Slideshow ---- */
        .banner-slider { position: relative; overflow: hidden; background: #1a1a1a; }
        .banner-track { display: flex; transition: transform 0.6s ease; }
        .banner-slide { min-width: 100%; padding: 2.5rem 4rem; display: flex; align-items: center; justify-content: center; gap: 3rem; }
        .banner-text { flex: 1; }
        .banner-slide h2 { font-size: 2rem; font-weight: 800; color: #fff; margin-bottom: 0.4rem; line-height: 1.2; }
        .banner-slide p { color: rgba(255,255,255,0.7); font-size: 0.95rem; max-width: 400px; }
        .banner-slide .banner-tag { display: inline-block; background: rgba(255,255,255,0.15); color: #fff; padding: 0.3rem 0.8rem; border-radius: 100px; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.75rem; }
        .banner-slide .banner-price { font-size: 1.5rem; font-weight: 800; color: #4ade80; margin-top: 0.5rem; }
        .banner-img { flex: 0 0 220px; height: 180px; display: flex; align-items: center; justify-content: center; }
        .banner-img img { max-width: 100%; max-height: 100%; object-fit: contain; filter: drop-shadow(0 10px 30px rgba(0,0,0,0.5)); }
        .slide-1 { background: linear-gradient(135deg, #1a1a1a 0%, #333 100%); }
        .slide-2 { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); }
        .slide-3 { background: linear-gradient(135deg, #1a1a1a 0%, #4a1a2e 100%); }
        .banner-dots { position: absolute; bottom: 1rem; left: 50%; transform: translateX(-50%); display: flex; gap: 0.5rem; }
        .banner-dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.3); cursor: pointer; transition: var(--transition); }
        .banner-dot.active { background: #fff; width: 24px; border-radius: 4px; }
        .banner-arrows { position: absolute; top: 50%; width: 100%; display: flex; justify-content: space-between; padding: 0 1rem; transform: translateY(-50%); pointer-events: none; }
        .banner-arrow { width: 36px; height: 36px; background: rgba(255,255,255,0.15); border: none; border-radius: 50%; color: #fff; font-size: 1.1rem; cursor: pointer; pointer-events: all; display: flex; align-items: center; justify-content: center; transition: var(--transition); }
        .banner-arrow:hover { background: rgba(255,255,255,0.3); }
        @media (max-width: 768px) { .banner-slide { flex-direction: column; padding: 2rem 1.5rem; gap: 1rem; text-align: center; } .banner-img { flex: 0 0 120px; height: 120px; } .banner-slide h2 { font-size: 1.3rem; } }

        /* ---- Search Bar ---- */
        .search-section { background: var(--bg-secondary); border-bottom: 1px solid var(--border); padding: 1rem 2rem; position: sticky; top: 64px; z-index: 90; }
        .search-bar { max-width: 800px; margin: 0 auto; display: flex; gap: 0.5rem; }
        .search-bar input[type="text"] { flex: 1; padding: 0.6rem 1rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-primary); color: var(--text-primary); font-size: 0.9rem; outline: none; transition: var(--transition); }
        .search-bar input[type="text"]:focus { border-color: #999; }
        .search-bar select { padding: 0.6rem 0.8rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-primary); color: var(--text-primary); font-size: 0.85rem; outline: none; }
        .search-bar button { padding: 0.6rem 1.2rem; background: #1a1a1a; color: #fff; border: none; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: var(--transition); }
        .search-bar button:hover { background: #333; }

        /* ---- Category Nav ---- */
        .cat-nav { display: flex; gap: 0.5rem; padding: 0.75rem 2rem; overflow-x: auto; background: var(--bg-secondary); border-bottom: 1px solid var(--border); }
        .cat-nav a { display: flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.9rem; background: var(--bg-primary); border: 1px solid var(--border); border-radius: 100px; color: var(--text-secondary); font-size: 0.78rem; font-weight: 600; white-space: nowrap; transition: var(--transition); }
        .cat-nav a:hover, .cat-nav a.active { background: #1a1a1a; color: #fff; border-color: #1a1a1a; }

        /* ---- Featured / Best Seller ---- */
        .featured-section { max-width: 1280px; margin: 0 auto; padding: 2rem; }
        .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
        .section-header h2 { font-size: 1.3rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem; }
        .section-header .view-all { font-size: 0.82rem; color: var(--text-muted); font-weight: 600; }
        .section-header .view-all:hover { color: var(--text-primary); }
        .featured-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }

        /* ---- Showcase Content ---- */
        .showcase-content { max-width: 1280px; margin: 0 auto; padding: 0 2rem 2rem; }

        /* ---- Category Section ---- */
        .category-section { margin-bottom: 2.5rem; scroll-margin-top: 180px; }
        .category-header { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem; padding-bottom: 0.6rem; border-bottom: 2px solid var(--border); }
        .category-header h2 { font-size: 1.2rem; font-weight: 700; color: var(--text-primary); }
        .category-header .count { background: var(--bg-tertiary); color: var(--text-secondary); font-size: 0.7rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 100px; }

        /* ---- Product Grid ---- */
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }

        /* ---- Product Card ---- */
        .product-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; transition: var(--transition); display: flex; flex-direction: column; text-decoration: none; }
        .product-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: #999; }
        .product-img { width: 100%; height: 180px; background: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: var(--text-muted); border-bottom: 1px solid var(--border); overflow: hidden; }
        .product-img img { width: 100%; height: 100%; object-fit: contain; padding: 0.5rem; }
        .product-info { padding: 0.85rem; flex: 1; display: flex; flex-direction: column; }
        .product-category-tag { font-size: 0.65rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 0.2rem; }
        .product-name { font-size: 0.85rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.2rem; line-height: 1.3; }
        .product-desc { font-size: 0.72rem; color: var(--text-muted); margin-bottom: 0.6rem; flex: 1; line-height: 1.4; }
        .product-footer { display: flex; align-items: center; justify-content: space-between; }
        .product-price { font-size: 1.05rem; font-weight: 800; color: var(--text-primary); }
        .product-stock { font-size: 0.7rem; font-weight: 600; }
        .stock-ok { color: #2d8a4e; }
        .stock-low { color: var(--danger); }
        .stock-out { color: var(--text-muted); }

        /* ---- Search Results ---- */
        .search-results { max-width: 1280px; margin: 0 auto; padding: 2rem; }
        .search-results h2 { font-size: 1.2rem; color: var(--text-primary); margin-bottom: 1rem; font-weight: 700; }
        .no-results { text-align: center; padding: 3rem; color: var(--text-muted); font-size: 1rem; }

        /* ---- Footer ---- */
        .showcase-footer { background: var(--bg-secondary); border-top: 1px solid var(--border); padding: 2.5rem 2rem 1.5rem; }
        .footer-grid { max-width: 1280px; margin: 0 auto; display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 2rem; margin-bottom: 1.5rem; }
        .footer-brand h3 { font-size: 1.1rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.5rem; }
        .footer-brand p { font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; }
        .footer-social { display: flex; gap: 0.5rem; margin-top: 0.75rem; }
        .footer-social a { width: 32px; height: 32px; background: var(--bg-tertiary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; transition: var(--transition); }
        .footer-social a:hover { background: #1a1a1a; transform: scale(1.1); }
        .footer-col h4 { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-primary); margin-bottom: 0.75rem; }
        .footer-col a { display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.4rem; transition: var(--transition); }
        .footer-col a:hover { color: var(--text-primary); }
        .footer-bottom { max-width: 1280px; margin: 0 auto; padding-top: 1rem; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted); }

        @media (max-width: 768px) {
            .top-nav { padding: 0 1rem; }
            .featured-section, .showcase-content, .search-results { padding: 1rem; }
            .product-grid, .featured-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 0.75rem; }
            .product-img { height: 130px; }
            .banner-slide h2 { font-size: 1.3rem; }
            .search-bar { flex-direction: column; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .footer-bottom { flex-direction: column; gap: 0.5rem; }
        }
    </style>
</head>
<body class="showcase-body">

<!-- Top Navigation -->
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

<?php if (!$searchQuery && !$filterCat): ?>
<!-- Banner Slideshow -->
<div class="banner-slider" id="bannerSlider">
    <div class="banner-track" id="bannerTrack">
        <div class="banner-slide slide-1">
            <div class="banner-text">
                <span class="banner-tag">🔥 สินค้ามาใหม่</span>
                <h2>NVIDIA RTX 4090 พร้อมจำหน่ายแล้ว</h2>
                <p>พลังกราฟิกระดับสุดยอด สำหรับเกมเมอร์ตัวจริง</p>
                <div class="banner-price">฿62,900.00</div>
            </div>
            <div class="banner-img"><img src="assets/images/products/product_15.jpg" alt="RTX 4090"></div>
        </div>
        <div class="banner-slide slide-2">
            <div class="banner-text">
                <span class="banner-tag">💎 สินค้าแนะนำ</span>
                <h2>Gaming Peripherals คุณภาพสูง</h2>
                <p>เมาส์ คีย์บอร์ด หูฟัง จากแบรนด์ชั้นนำ พร้อมประกัน</p>
                <div class="banner-price">เริ่มต้น ฿2,990</div>
            </div>
            <div class="banner-img"><img src="assets/images/products/product_26.jpg" alt="Corsair K70"></div>
        </div>
        <div class="banner-slide slide-3">
            <div class="banner-text">
                <span class="banner-tag">⚡ โปรโมชั่นพิเศษ</span>
                <h2>SSD คุณภาพสูง ราคาพิเศษ</h2>
                <p>Samsung, WD, Crucial — NVMe Gen5 เร็วที่สุด</p>
                <div class="banner-price">เริ่มต้น ฿2,790</div>
            </div>
            <div class="banner-img"><img src="assets/images/products/samsung_990pro.png" alt="Samsung 990 Pro"></div>
        </div>
    </div>
    <div class="banner-arrows">
        <button class="banner-arrow" onclick="slideBanner(-1)">‹</button>
        <button class="banner-arrow" onclick="slideBanner(1)">›</button>
    </div>
    <div class="banner-dots" id="bannerDots">
        <span class="banner-dot active" onclick="goToSlide(0)"></span>
        <span class="banner-dot" onclick="goToSlide(1)"></span>
        <span class="banner-dot" onclick="goToSlide(2)"></span>
    </div>
</div>
<?php endif; ?>

<!-- Search Bar -->
<div class="search-section">
    <form class="search-bar" method="GET">
        <input type="text" name="q" placeholder="🔍 ค้นหาสินค้า..." value="<?= htmlspecialchars($searchQuery) ?>">
        <select name="cat">
            <option value="0">ทุกหมวดหมู่</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $filterCat == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">ค้นหา</button>
        <?php if ($searchQuery || $filterCat): ?>
            <a href="showcase.php" style="padding:0.6rem 0.8rem;color:var(--text-muted);font-size:0.85rem;">✕ ล้าง</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($searchQuery || $filterCat): ?>
<!-- Search Results -->
<div class="search-results">
    <h2>ผลการค้นหา <?php if ($searchQuery): ?>"<?= htmlspecialchars($searchQuery) ?>"<?php endif; ?> — พบ <?= count($searchResults) ?> รายการ</h2>
    <?php if (empty($searchResults)): ?>
        <div class="no-results">😔 ไม่พบสินค้าที่ตรงกับการค้นหา</div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($searchResults as $p): ?>
                <?php $icon = $categoryIcons[$p['category_name']] ?? '📁'; ?>
                <a href="product_detail.php?id=<?= $p['id'] ?>" class="product-card">
                    <div class="product-img">
                        <?php if (!empty($p['image_url'])): ?><img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>"><?php else: ?><?= $icon ?><?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-category-tag"><?= htmlspecialchars($p['category_name']) ?></div>
                        <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="product-desc"><?= htmlspecialchars($p['description'] ?: 'ไม่มีรายละเอียด') ?></div>
                        <div class="product-footer">
                            <div class="product-price">฿<?= number_format($p['price'], 2) ?></div>
                            <?php if ($p['stock_quantity'] <= 0): ?><span class="product-stock stock-out">สินค้าหมด</span>
                            <?php elseif ($p['stock_quantity'] < 5): ?><span class="product-stock stock-low">เหลือ <?= $p['stock_quantity'] ?> ชิ้น</span>
                            <?php else: ?><span class="product-stock stock-ok">มีสินค้า</span><?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php else: ?>

<!-- Category Quick Nav -->
<div class="cat-nav">
    <?php foreach ($categories as $cat): ?>
        <?php $icon = $categoryIcons[$cat['name']] ?? '📁'; ?>
        <a href="#cat-<?= $cat['id'] ?>"><?= $icon ?> <?= htmlspecialchars($cat['name']) ?> (<?= count($productsByCategory[$cat['id']] ?? []) ?>)</a>
    <?php endforeach; ?>
</div>

<!-- Best Sellers -->
<div class="featured-section">
    <div class="section-header">
        <h2>🏆 สินค้าแนะนำ</h2>
    </div>
    <div class="featured-grid">
        <?php foreach ($bestSellers as $p): ?>
            <?php $icon = $categoryIcons[$p['category_name']] ?? '📁'; ?>
            <a href="product_detail.php?id=<?= $p['id'] ?>" class="product-card">
                <div class="product-img">
                    <?php if (!empty($p['image_url'])): ?><img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>"><?php else: ?><?= $icon ?><?php endif; ?>
                </div>
                <div class="product-info">
                    <div class="product-category-tag"><?= htmlspecialchars($p['category_name']) ?></div>
                    <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                    <div class="product-footer">
                        <div class="product-price">฿<?= number_format($p['price'], 2) ?></div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<hr style="border:none;border-top:1px solid var(--border);max-width:1280px;margin:0 auto;">

<!-- All Products by Category -->
<div class="showcase-content">
    <?php foreach ($categories as $cat): ?>
        <?php 
        $products = $productsByCategory[$cat['id']] ?? [];
        if (empty($products)) continue;
        $icon = $categoryIcons[$cat['name']] ?? '📁';
        ?>
        <section class="category-section" id="cat-<?= $cat['id'] ?>">
            <div class="category-header">
                <span style="font-size:1.3rem;"><?= $icon ?></span>
                <h2><?= htmlspecialchars($cat['name']) ?></h2>
                <span class="count"><?= count($products) ?> รายการ</span>
            </div>
            <div class="product-grid">
                <?php foreach ($products as $p): ?>
                <a href="product_detail.php?id=<?= $p['id'] ?>" class="product-card">
                    <div class="product-img">
                        <?php if (!empty($p['image_url'])): ?><img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>"><?php else: ?><?= $icon ?><?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-category-tag"><?= htmlspecialchars($cat['name']) ?></div>
                        <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="product-desc"><?= htmlspecialchars($p['description'] ?: 'ไม่มีรายละเอียด') ?></div>
                        <div class="product-footer">
                            <div class="product-price">฿<?= number_format($p['price'], 2) ?></div>
                            <?php if ($p['stock_quantity'] <= 0): ?><span class="product-stock stock-out">สินค้าหมด</span>
                            <?php elseif ($p['stock_quantity'] < 5): ?><span class="product-stock stock-low">เหลือ <?= $p['stock_quantity'] ?> ชิ้น</span>
                            <?php else: ?><span class="product-stock stock-ok">มีสินค้า</span><?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Footer -->
<footer class="showcase-footer">
    <div class="footer-grid">
        <div class="footer-brand">
            <h3>🎮 GamePro Store</h3>
            <p>ร้านจำหน่ายอุปกรณ์เกมมิ่งและคอมพิวเตอร์ครบวงจร ราคาดี มีประกัน สินค้าคุณภาพ บริการหลังการขายดีเยี่ยม พร้อมส่งทั่วประเทศ</p>
            <div class="footer-social">
                <a href="#" title="Facebook">📘</a>
                <a href="#" title="Line">💬</a>
                <a href="#" title="Twitter">🐦</a>
                <a href="#" title="YouTube">▶️</a>
            </div>
        </div>
        <div class="footer-col">
            <h4>สินค้า</h4>
            <?php foreach ($categories as $cat): ?>
                <a href="showcase.php#cat-<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="footer-col">
            <h4>บริการ</h4>
            <a href="#">ประกอบคอมตามสเปค</a>
            <a href="#">รับซ่อมคอมพิวเตอร์</a>
            <a href="#">การรับประกัน</a>
            <a href="#">นโยบายคืนสินค้า</a>
        </div>
        <div class="footer-col">
            <h4>ติดต่อเรา</h4>
            <a href="#">📞 02-123-4567</a>
            <a href="#">📧 support@gamepro.co.th</a>
            <a href="#">📍 กรุงเทพมหานคร</a>
            <a href="#">🕐 10:00 – 20:00 น.</a>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© 2026 GamePro Store — All Rights Reserved</span>
        <span>Made with ❤️ in Thailand</span>
    </div>
</footer>

<!-- Banner Slideshow JS -->
<script>
let currentSlide = 0;
const totalSlides = 3;
const track = document.getElementById('bannerTrack');
const dots = document.querySelectorAll('.banner-dot');

function goToSlide(n) {
    currentSlide = n;
    if (track) track.style.transform = `translateX(-${n * 100}%)`;
    dots.forEach((d, i) => d.classList.toggle('active', i === n));
}

function slideBanner(dir) {
    currentSlide = (currentSlide + dir + totalSlides) % totalSlides;
    goToSlide(currentSlide);
}

// Auto-slide every 5 seconds
setInterval(() => slideBanner(1), 5000);
</script>

</body>
</html>
