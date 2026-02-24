<?php
session_start();
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

// Badge logic
function getBadge($product) {
    $daysSinceCreated = (time() - strtotime($product['created_at'])) / 86400;
    if ($daysSinceCreated < 7) return '<div class="product-badge"><span class="badge-new">NEW</span></div>';
    if ($product['price'] >= 15000) return '<div class="product-badge"><span class="badge-hot">HOT</span></div>';
    if ($product['stock_quantity'] > 0 && $product['stock_quantity'] < 5) return '<div class="product-badge"><span class="badge-sale">SALE</span></div>';
    return '';
}

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
<html lang="th" data-theme="light">
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

        /* ---- Banner Slideshow (Premium) ---- */
        .banner-slider { position: relative; overflow: hidden; background: #0a0a0f; height: 420px; }
        .banner-particles { position: absolute; inset: 0; z-index: 1; }
        .banner-track { display: flex; transition: transform 0.8s cubic-bezier(0.22, 1, 0.36, 1); position: relative; z-index: 2; height: 100%; }
        .banner-slide { min-width: 100%; padding: 3rem 5rem; display: flex; align-items: center; justify-content: center; gap: 4rem; position: relative; }

        /* Slide backgrounds with animated gradient */
        .slide-1 { background: linear-gradient(135deg, #0a0a1a 0%, #1a0a2e 40%, #0a1a2e 100%); }
        .slide-2 { background: linear-gradient(135deg, #0a1020 0%, #0a2040 40%, #102040 100%); }
        .slide-3 { background: linear-gradient(135deg, #1a0a10 0%, #2a0a1a 40%, #1a1020 100%); }
        .slide-4 { background: linear-gradient(135deg, #0a1a0a 0%, #0a2a1a 40%, #102010 100%); }

        /* Glow orbs per slide */
        .banner-slide::before { content: ''; position: absolute; width: 500px; height: 500px; border-radius: 50%; filter: blur(120px); opacity: 0.3; animation: pulseGlow 4s ease-in-out infinite alternate; }
        .slide-1::before { background: radial-gradient(circle, #7c3aed, transparent); top: -40%; right: 10%; }
        .slide-2::before { background: radial-gradient(circle, #3b82f6, transparent); top: -30%; left: 5%; }
        .slide-3::before { background: radial-gradient(circle, #ef4444, transparent); bottom: -40%; right: 20%; }
        .slide-4::before { background: radial-gradient(circle, #10b981, transparent); top: -30%; right: 15%; }
        @keyframes pulseGlow { 0% { transform: scale(1); opacity: 0.2; } 100% { transform: scale(1.3); opacity: 0.4; } }

        .banner-text { flex: 1; position: relative; z-index: 3; max-width: 520px; }
        .banner-slide h2 { font-size: 2.4rem; font-weight: 900; color: #fff; margin-bottom: 0.5rem; line-height: 1.15; letter-spacing: -0.02em; }
        .banner-slide h2 .text-gradient { background: linear-gradient(90deg, #a78bfa, #60a5fa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .slide-2 h2 .text-gradient { background: linear-gradient(90deg, #60a5fa, #38bdf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .slide-3 h2 .text-gradient { background: linear-gradient(90deg, #f87171, #fb923c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .slide-4 h2 .text-gradient { background: linear-gradient(90deg, #34d399, #60a5fa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .banner-slide p { color: rgba(255,255,255,0.65); font-size: 0.95rem; line-height: 1.6; max-width: 420px; margin-bottom: 1.2rem; }

        /* Tags */
        .banner-tag { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.9rem; border-radius: 100px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 1rem; backdrop-filter: blur(8px); }
        .tag-fire { background: rgba(239,68,68,0.2); color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); }
        .tag-gem { background: rgba(124,58,237,0.2); color: #c4b5fd; border: 1px solid rgba(124,58,237,0.3); }
        .tag-bolt { background: rgba(245,158,11,0.2); color: #fcd34d; border: 1px solid rgba(245,158,11,0.3); }
        .tag-new { background: rgba(16,185,129,0.2); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.3); }

        /* Price area */
        .banner-price-row { display: flex; align-items: baseline; gap: 0.75rem; margin-bottom: 1.2rem; }
        .banner-price { font-size: 2rem; font-weight: 900; color: #fff; }
        .banner-price-old { font-size: 1rem; color: rgba(255,255,255,0.35); text-decoration: line-through; }
        .banner-discount { display: inline-block; background: linear-gradient(135deg, #ef4444, #f97316); color: #fff; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.72rem; font-weight: 800; }

        /* CTA Buttons */
        .banner-cta { display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; }
        .btn-cta-primary { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.6rem; background: linear-gradient(135deg, #7c3aed, #6d28d9); color: #fff; border: none; border-radius: 10px; font-size: 0.88rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease; text-decoration: none; box-shadow: 0 4px 20px rgba(124,58,237,0.4); }
        .btn-cta-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(124,58,237,0.5); }
        .slide-2 .btn-cta-primary { background: linear-gradient(135deg, #2563eb, #1d4ed8); box-shadow: 0 4px 20px rgba(37,99,235,0.4); }
        .slide-2 .btn-cta-primary:hover { box-shadow: 0 8px 30px rgba(37,99,235,0.5); }
        .slide-3 .btn-cta-primary { background: linear-gradient(135deg, #dc2626, #b91c1c); box-shadow: 0 4px 20px rgba(220,38,38,0.4); }
        .slide-3 .btn-cta-primary:hover { box-shadow: 0 8px 30px rgba(220,38,38,0.5); }
        .slide-4 .btn-cta-primary { background: linear-gradient(135deg, #059669, #047857); box-shadow: 0 4px 20px rgba(5,150,105,0.4); }
        .slide-4 .btn-cta-primary:hover { box-shadow: 0 8px 30px rgba(5,150,105,0.5); }
        .btn-cta-secondary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.7rem 1.4rem; background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.85); border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; font-size: 0.85rem; font-weight: 600; cursor: pointer; backdrop-filter: blur(8px); transition: all 0.3s ease; text-decoration: none; }
        .btn-cta-secondary:hover { background: rgba(255,255,255,0.15); color: #fff; transform: translateY(-1px); }

        /* Feature badges row */
        .banner-features { display: flex; gap: 1.25rem; margin-top: 1rem; }
        .banner-feat { display: flex; align-items: center; gap: 0.35rem; font-size: 0.72rem; color: rgba(255,255,255,0.55); font-weight: 500; }
        .banner-feat span { font-size: 0.85rem; }

        /* Image area */
        .banner-img { flex: 0 0 300px; height: 300px; position: relative; display: flex; align-items: center; justify-content: center; z-index: 3; }
        .banner-img img { max-width: 100%; max-height: 100%; object-fit: contain; filter: drop-shadow(0 20px 40px rgba(0,0,0,0.6)); animation: floatProduct 6s ease-in-out infinite; }
        @keyframes floatProduct { 0%, 100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-15px) rotate(1deg); } }

        /* Glass card behind image */
        .banner-img::before { content: ''; position: absolute; width: 260px; height: 260px; border-radius: 30px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); backdrop-filter: blur(20px); transform: rotate(12deg); z-index: -1; }
        .banner-img::after { content: ''; position: absolute; width: 200px; height: 200px; border-radius: 50%; background: radial-gradient(circle, rgba(124,58,237,0.15), transparent 70%); z-index: -1; animation: pulseGlow 3s ease-in-out infinite alternate; }
        .slide-2 .banner-img::after { background: radial-gradient(circle, rgba(59,130,246,0.15), transparent 70%); }
        .slide-3 .banner-img::after { background: radial-gradient(circle, rgba(239,68,68,0.15), transparent 70%); }
        .slide-4 .banner-img::after { background: radial-gradient(circle, rgba(16,185,129,0.15), transparent 70%); }

        /* Countdown Timer */
        .banner-countdown { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
        .cd-box { background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 0.4rem 0.65rem; text-align: center; backdrop-filter: blur(10px); min-width: 48px; }
        .cd-box .cd-num { font-size: 1.1rem; font-weight: 800; color: #fff; display: block; line-height: 1.2; }
        .cd-box .cd-label { font-size: 0.55rem; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.08em; }

        /* Dots & Arrows */
        .banner-dots { position: absolute; bottom: 1.5rem; left: 50%; transform: translateX(-50%); display: flex; gap: 0.5rem; z-index: 10; }
        .banner-dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.25); cursor: pointer; transition: all 0.4s ease; border: none; }
        .banner-dot.active { background: #fff; width: 28px; border-radius: 4px; box-shadow: 0 0 8px rgba(255,255,255,0.5); }
        .banner-dot:hover { background: rgba(255,255,255,0.5); }
        .banner-arrows { position: absolute; top: 50%; width: 100%; display: flex; justify-content: space-between; padding: 0 1.5rem; transform: translateY(-50%); pointer-events: none; z-index: 10; }
        .banner-arrow { width: 44px; height: 44px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: 50%; color: #fff; font-size: 1.2rem; cursor: pointer; pointer-events: all; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; backdrop-filter: blur(10px); }
        .banner-arrow:hover { background: rgba(255,255,255,0.15); transform: scale(1.1); border-color: rgba(255,255,255,0.25); }

        /* Progress bar (auto-slide indicator) */
        .banner-progress { position: absolute; bottom: 0; left: 0; height: 3px; background: linear-gradient(90deg, #7c3aed, #60a5fa); z-index: 10; animation: slideProgress 5s linear infinite; }
        @keyframes slideProgress { 0% { width: 0%; } 100% { width: 100%; } }

        @media (max-width: 900px) {
            .banner-slider { height: 500px; }
            .banner-slide { flex-direction: column; padding: 2rem 1.5rem; gap: 1rem; text-align: center; }
            .banner-img { flex: 0 0 160px; height: 160px; }
            .banner-img::before { width: 140px; height: 140px; }
            .banner-slide h2 { font-size: 1.5rem; }
            .banner-cta { justify-content: center; }
            .banner-features { justify-content: center; }
            .banner-countdown { justify-content: center; }
            .banner-price-row { justify-content: center; }
            .banner-text { max-width: 100%; }
        }
        @media (max-width: 480px) {
            .banner-slider { height: 520px; }
            .banner-slide { padding: 1.5rem 1rem; }
            .banner-slide h2 { font-size: 1.25rem; }
            .banner-img { flex: 0 0 130px; height: 130px; }
        }

        /* ---- Search Bar ---- */
        .search-section { background: var(--bg-secondary); border-bottom: 1px solid var(--border); padding: 1rem 2rem; position: sticky; top: 64px; z-index: 90; }
        .search-bar { max-width: 800px; margin: 0 auto; display: flex; gap: 0.5rem; }
        .search-bar input[type="text"] { flex: 1; padding: 0.6rem 1rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-primary); color: var(--text-primary); font-size: 0.9rem; outline: none; transition: var(--transition); }
        .search-bar input[type="text"]:focus { border-color: #999; }
        .search-bar select { padding: 0.6rem 0.8rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-primary); color: var(--text-primary); font-size: 0.85rem; outline: none; }
        .search-bar button { padding: 0.6rem 1.2rem; background: #1a1a1a; color: #fff; border: none; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: var(--transition); }
        .search-bar button:hover { background: #333; }
        .search-wrapper { position: relative; flex: 1; }
        .ac-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); box-shadow: var(--shadow-lg); z-index: 200; display: none; max-height: 320px; overflow-y: auto; margin-top: 4px; }
        .ac-dropdown.open { display: block; }
        .ac-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.8rem; cursor: pointer; transition: var(--transition); text-decoration: none; color: var(--text-primary); }
        .ac-item:hover, .ac-item.active { background: var(--bg-tertiary); }
        .ac-item-img { width: 40px; height: 40px; border-radius: 4px; background: #fff; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
        [data-theme='dark'] .ac-item-img { background: #1a1a1a; }
        .ac-item-img img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .ac-item-info { flex: 1; min-width: 0; }
        .ac-item-name { font-size: 0.82rem; font-weight: 600; color: var(--text-primary); }
        .ac-item-cat { font-size: 0.7rem; color: var(--text-muted); }
        .ac-item-price { font-size: 0.85rem; font-weight: 800; color: var(--text-primary); white-space: nowrap; }

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

        /* ---- Product Badge ---- */
        .product-badge { position: absolute; top: 0.5rem; left: 0.5rem; z-index: 2; }
        .product-badge span { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
        .badge-new { background: #2563eb; color: #fff; }
        .badge-hot { background: #dc2626; color: #fff; }
        .badge-sale { background: #16a34a; color: #fff; }
        .product-card { position: relative; }

        /* ---- Dark Mode Toggle ---- */
        .theme-toggle { width: 36px; height: 36px; background: var(--bg-tertiary); border: 1px solid var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 1rem; transition: var(--transition); }
        .theme-toggle:hover { background: var(--bg-primary); transform: scale(1.1); }
        [data-theme='dark'] .product-img { background: #1a1a1a; }
        [data-theme='dark'] .top-nav-logo .logo-icon { background: linear-gradient(135deg, #333, #555); }
        [data-theme='dark'] .banner-slider { background: #0a0a0a; }
        [data-theme='dark'] .cat-nav a:hover, [data-theme='dark'] .cat-nav a.active { background: #f0f0f0; color: #1a1a1a; border-color: #f0f0f0; }
        [data-theme='dark'] .search-bar button { background: #f0f0f0; color: #1a1a1a; }

        /* ---- Hamburger Menu ---- */
        .hamburger { display: none; width: 36px; height: 36px; background: var(--bg-tertiary); border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; align-items: center; justify-content: center; font-size: 1.2rem; }
        .mobile-menu { display: none; position: fixed; top: 64px; left: 0; right: 0; background: var(--bg-secondary); border-bottom: 1px solid var(--border); padding: 1rem; z-index: 95; box-shadow: var(--shadow-lg); }
        .mobile-menu.open { display: block; animation: slideDown 0.3s ease; }
        .mobile-menu a { display: block; padding: 0.6rem 0.5rem; color: var(--text-secondary); font-weight: 500; border-bottom: 1px solid var(--border); }
        .mobile-menu a:last-child { border-bottom: none; }
        .mobile-menu a:hover { color: var(--text-primary); }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        /* ---- Recently Viewed ---- */
        .recently-viewed { max-width: 1280px; margin: 0 auto; padding: 0 2rem 2rem; }
        .recently-viewed h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-primary); }
        .rv-scroll { display: flex; gap: 0.75rem; overflow-x: auto; padding-bottom: 0.5rem; }
        .rv-card { flex: 0 0 150px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); overflow: hidden; transition: var(--transition); text-decoration: none; }
        .rv-card:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
        .rv-card .rv-img { height: 100px; background: #fff; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        [data-theme='dark'] .rv-card .rv-img { background: #1a1a1a; }
        .rv-card .rv-img img { max-width: 100%; max-height: 100%; object-fit: contain; padding: 0.3rem; }
        .rv-card .rv-info { padding: 0.5rem; }
        .rv-card .rv-name { font-size: 0.72rem; font-weight: 600; color: var(--text-primary); line-height: 1.3; margin-bottom: 0.2rem; }
        .rv-card .rv-price { font-size: 0.8rem; font-weight: 800; color: var(--text-primary); }
        .cart-badge { background: #dc2626; color: #fff; font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 100px; margin-left: -0.3rem; }
        .toast { position: fixed; top: 80px; right: 1rem; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0.8rem 1.2rem; box-shadow: var(--shadow-lg); z-index: 999; font-size: 0.85rem; font-weight: 600; color: var(--text-primary); transform: translateX(120%); transition: transform 0.3s ease; }
        .toast.show { transform: translateX(0); }
        .toast.success { border-left: 3px solid #16a34a; }
        .toast.heart { border-left: 3px solid #ef4444; }

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
            .top-nav-links { display: none; }
            .hamburger { display: flex; }
            .featured-section, .showcase-content, .search-results { padding: 1rem; }
            .product-grid, .featured-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 0.75rem; }
            .product-img { height: 130px; }
            .banner-slide h2 { font-size: 1.3rem; }
            .search-bar { flex-direction: column; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .footer-bottom { flex-direction: column; gap: 0.5rem; }
            .recently-viewed { padding: 0 1rem 1rem; }
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
        <a href="cart.php">🛒 ตะกร้า <span class="cart-badge" id="cartBadge">0</span></a>
        <a href="compare.php">⚖️ เทียบ</a>
        <a href="wishlist.php">❤️ Wishlist</a>
        <a href="orders.php">📋 คำสั่งซื้อ</a>
        <a href="contact.php">📧 ติดต่อ</a>
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <a href="profile.php">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'โปรไฟล์') ?></a>
            <a href="logout.php">🚪 ออก</a>
        <?php else: ?>
            <a href="login.php">🔐 เข้าสู่ระบบ</a>
        <?php endif; ?>
        <button class="theme-toggle" onclick="toggleTheme()" title="สลับธีม" id="themeBtn">🌙</button>
    </div>
    <div style="display:flex;gap:0.5rem;align-items:center;">
        <button class="theme-toggle" onclick="toggleTheme()" title="สลับธีม" id="themeBtnMobile" style="display:none">🌙</button>
        <button class="hamburger" onclick="toggleMobileMenu()" id="hamburgerBtn">☰</button>
    </div>
</nav>
<div class="mobile-menu" id="mobileMenu">
    <a href="showcase.php">🏠 หน้าแรก</a>
    <a href="cart.php">🛒 ตะกร้าสินค้า</a>
    <a href="compare.php">⚖️ เทียบสินค้า</a>
    <a href="wishlist.php">❤️ Wishlist</a>
    <a href="orders.php">📋 คำสั่งซื้อ</a>
    <a href="contact.php">📧 ติดต่อเรา</a>
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
        <a href="profile.php">👤 โปรไฟล์</a>
        <a href="logout.php">🚪 ออกจากระบบ</a>
    <?php else: ?>
        <a href="login.php">🔐 เข้าสู่ระบบ</a>
    <?php endif; ?>
    <a href="#" onclick="toggleTheme();return false;" id="mobileThemeLink">🌙 Dark Mode</a>
</div>

<?php if (!$searchQuery && !$filterCat): ?>
<!-- Banner Slideshow (Premium) -->
<div class="banner-slider" id="bannerSlider">
    <canvas class="banner-particles" id="particleCanvas"></canvas>
    <div class="banner-progress" id="bannerProgress"></div>
    <div class="banner-track" id="bannerTrack">

        <!-- Slide 1: RTX 4090 -->
        <div class="banner-slide slide-1">
            <div class="banner-text">
                <span class="banner-tag tag-fire">🔥 สินค้ายอดนิยม</span>
                <h2>NVIDIA <span class="text-gradient">RTX 4090</span><br>พลังกราฟิกระดับสูงสุด</h2>
                <p>GPU ระดับ Flagship สำหรับเกมเมอร์ตัวจริง เล่นเกม 4K Ultra ลื่นไหลไม่มีสะดุด</p>
                <div class="banner-price-row">
                    <span class="banner-price">฿62,900</span>
                    <span class="banner-price-old">฿69,900</span>
                    <span class="banner-discount">-10%</span>
                </div>
                <div class="banner-cta">
                    <a href="product_detail.php?id=15" class="btn-cta-primary">🛒 ซื้อเลย</a>
                    <a href="compare.php" class="btn-cta-secondary">⚖️ เทียบสินค้า</a>
                </div>
                <div class="banner-features">
                    <div class="banner-feat"><span>🛡️</span> รับประกัน 3 ปี</div>
                    <div class="banner-feat"><span>🚚</span> ส่งฟรีทั่วประเทศ</div>
                    <div class="banner-feat"><span>⭐</span> 5.0 รีวิว</div>
                </div>
            </div>
            <div class="banner-img"><img src="assets/images/products/product_15.jpg" alt="RTX 4090"></div>
        </div>

        <!-- Slide 2: Gaming Peripherals -->
        <div class="banner-slide slide-2">
            <div class="banner-text">
                <span class="banner-tag tag-gem">💎 สินค้าแนะนำ</span>
                <h2>Gaming <span class="text-gradient">Peripherals</span><br>คุณภาพระดับโปร</h2>
                <p>เมาส์ คีย์บอร์ด หูฟัง จากแบรนด์ชั้นนำ Corsair, Logitech, Razer</p>
                <div class="banner-price-row">
                    <span class="banner-price">เริ่มต้น ฿2,490</span>
                </div>
                <div class="banner-cta">
                    <a href="showcase.php#cat-6" class="btn-cta-primary">🎮 ดูสินค้า</a>
                    <a href="contact.php" class="btn-cta-secondary">💬 สอบถามเพิ่มเติม</a>
                </div>
                <div class="banner-features">
                    <div class="banner-feat"><span>🔧</span> สวิตช์แท้</div>
                    <div class="banner-feat"><span>🌈</span> RGB Sync</div>
                    <div class="banner-feat"><span>🏆</span> Esports Grade</div>
                </div>
            </div>
            <div class="banner-img"><img src="assets/images/products/product_26.jpg" alt="Corsair K70"></div>
        </div>

        <!-- Slide 3: Flash Sale SSD -->
        <div class="banner-slide slide-3">
            <div class="banner-text">
                <span class="banner-tag tag-bolt">⚡ FLASH SALE</span>
                <h2>NVMe <span class="text-gradient">Gen5 SSD</span><br>เร็วที่สุดในโลก</h2>
                <p>Samsung 990 Pro, Crucial T700 — อ่านเขียน 12,400 MB/s</p>
                <div class="banner-countdown" id="countdown">
                    <div class="cd-box"><span class="cd-num" id="cdH">08</span><span class="cd-label">ชั่วโมง</span></div>
                    <div class="cd-box"><span class="cd-num" id="cdM">45</span><span class="cd-label">นาที</span></div>
                    <div class="cd-box"><span class="cd-num" id="cdS">30</span><span class="cd-label">วินาที</span></div>
                </div>
                <div class="banner-price-row">
                    <span class="banner-price">฿2,790</span>
                    <span class="banner-price-old">฿3,990</span>
                    <span class="banner-discount">-30%</span>
                </div>
                <div class="banner-cta">
                    <a href="showcase.php#cat-4" class="btn-cta-primary">🔥 ช้อปเลย!</a>
                    <a href="compare.php" class="btn-cta-secondary">⚖️ เทียบรุ่น</a>
                </div>
            </div>
            <div class="banner-img"><img src="assets/images/products/samsung_990pro.png" alt="Samsung 990 Pro"></div>
        </div>

        <!-- Slide 4: New Arrivals -->
        <div class="banner-slide slide-4">
            <div class="banner-text">
                <span class="banner-tag tag-new">✨ สินค้ามาใหม่</span>
                <h2>Ryzen 7 <span class="text-gradient">7800X3D</span><br>ชิป Gaming อันดับ 1</h2>
                <p>3D V-Cache ทำให้ FPS สูงกว่าคู่แข่งทุกรุ่น เหมาะสำหรับเกมเมอร์สายจัด</p>
                <div class="banner-price-row">
                    <span class="banner-price">฿15,900</span>
                </div>
                <div class="banner-cta">
                    <a href="product_detail.php?id=4" class="btn-cta-primary">🚀 ดูรายละเอียด</a>
                    <a href="showcase.php#cat-2" class="btn-cta-secondary">⚡ CPU ทั้งหมด</a>
                </div>
                <div class="banner-features">
                    <div class="banner-feat"><span>🧠</span> 3D V-Cache</div>
                    <div class="banner-feat"><span>🎮</span> Best for Gaming</div>
                    <div class="banner-feat"><span>⭐</span> 5.0 Rating</div>
                </div>
            </div>
            <div class="banner-img"><img src="assets/images/products/ryzen_7800x3d.png" alt="Ryzen 7 7800X3D"></div>
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
        <span class="banner-dot" onclick="goToSlide(3)"></span>
    </div>
</div>
<?php endif; ?>

<!-- Search Bar -->
<div class="search-section">
    <form class="search-bar" method="GET" autocomplete="off">
        <div class="search-wrapper">
            <input type="text" name="q" id="searchInput" placeholder="🔍 ค้นหาสินค้า..." value="<?= htmlspecialchars($searchQuery) ?>">
            <div class="ac-dropdown" id="acDropdown"></div>
        </div>
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
                    <?= getBadge($p) ?>
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
                <?= getBadge($p) ?>
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
                    <?= getBadge($p) ?>
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

<!-- Recently Viewed Section -->
<div class="recently-viewed" id="recentlyViewed" style="display:none;">
    <h3>🕐 สินค้าที่เพิ่งดู</h3>
    <div class="rv-scroll" id="rvScroll"></div>
</div>

<!-- Banner Slideshow JS + Dark Mode + Recently Viewed -->
<script>
// Banner Slideshow
let currentSlide = 0;
const totalSlides = 4;
const track = document.getElementById('bannerTrack');
const dots = document.querySelectorAll('.banner-dot');
const progress = document.getElementById('bannerProgress');

function goToSlide(n) {
    currentSlide = n;
    if (track) track.style.transform = `translateX(-${n * 100}%)`;
    dots.forEach((d, i) => d.classList.toggle('active', i === n));
    // Restart progress bar
    if (progress) { progress.style.animation = 'none'; progress.offsetHeight; progress.style.animation = 'slideProgress 5s linear'; }
}

function slideBanner(dir) {
    currentSlide = (currentSlide + dir + totalSlides) % totalSlides;
    goToSlide(currentSlide);
}
setInterval(() => slideBanner(1), 5000);

// Particle Canvas
(function() {
    const canvas = document.getElementById('particleCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let particles = [];
    function resize() { canvas.width = canvas.offsetWidth; canvas.height = canvas.offsetHeight; }
    resize();
    window.addEventListener('resize', resize);

    for (let i = 0; i < 50; i++) {
        particles.push({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            r: Math.random() * 2 + 0.5,
            dx: (Math.random() - 0.5) * 0.4,
            dy: (Math.random() - 0.5) * 0.3,
            opacity: Math.random() * 0.4 + 0.1
        });
    }

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        particles.forEach(p => {
            p.x += p.dx;
            p.y += p.dy;
            if (p.x < 0 || p.x > canvas.width) p.dx *= -1;
            if (p.y < 0 || p.y > canvas.height) p.dy *= -1;
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(255,255,255,${p.opacity})`;
            ctx.fill();
        });
        // Draw subtle lines between nearby particles
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const dist = Math.sqrt(dx*dx + dy*dy);
                if (dist < 100) {
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.strokeStyle = `rgba(255,255,255,${0.03 * (1 - dist/100)})`;
                    ctx.stroke();
                }
            }
        }
        requestAnimationFrame(animate);
    }
    animate();
})();

// Countdown Timer (Flash Sale)
(function() {
    const cdH = document.getElementById('cdH');
    const cdM = document.getElementById('cdM');
    const cdS = document.getElementById('cdS');
    if (!cdH) return;
    // Set end time: today + some hours
    let remaining = 8 * 3600 + 45 * 60 + 30;
    setInterval(() => {
        remaining--;
        if (remaining < 0) remaining = 12 * 3600; // Reset
        const h = Math.floor(remaining / 3600);
        const m = Math.floor((remaining % 3600) / 60);
        const s = remaining % 60;
        cdH.textContent = String(h).padStart(2, '0');
        cdM.textContent = String(m).padStart(2, '0');
        cdS.textContent = String(s).padStart(2, '0');
    }, 1000);
})();

// Dark Mode Toggle
function toggleTheme() {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-theme') === 'dark';
    html.setAttribute('data-theme', isDark ? 'light' : 'dark');
    localStorage.setItem('theme', isDark ? 'light' : 'dark');
    updateThemeIcons();
}

function updateThemeIcons() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const icon = isDark ? '☀️' : '🌙';
    document.querySelectorAll('.theme-toggle').forEach(b => b.textContent = icon);
    const ml = document.getElementById('mobileThemeLink');
    if (ml) ml.textContent = isDark ? '☀️ Light Mode' : '🌙 Dark Mode';
}

// Load saved theme
(function() {
    const saved = localStorage.getItem('theme');
    if (saved) document.documentElement.setAttribute('data-theme', saved);
    updateThemeIcons();
})();

// Hamburger Menu
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const btn = document.getElementById('hamburgerBtn');
    menu.classList.toggle('open');
    btn.textContent = menu.classList.contains('open') ? '✕' : '☰';
}

// Show mobile theme button
if (window.innerWidth <= 768) {
    const mb = document.getElementById('themeBtnMobile');
    if (mb) mb.style.display = 'flex';
}

// Recently Viewed (loads from localStorage)
function loadRecentlyViewed() {
    const rv = JSON.parse(localStorage.getItem('recentlyViewed') || '[]');
    if (rv.length === 0) return;
    const container = document.getElementById('recentlyViewed');
    const scroll = document.getElementById('rvScroll');
    if (!container || !scroll) return;
    container.style.display = 'block';
    scroll.innerHTML = rv.map(p => `
        <a href="product_detail.php?id=${p.id}" class="rv-card">
            <div class="rv-img">${p.img ? `<img src="${p.img}" alt="${p.name}">` : '<span style="font-size:2rem">' + (p.icon||'📁') + '</span>'}</div>
            <div class="rv-info">
                <div class="rv-name">${p.name}</div>
                <div class="rv-price">฿${Number(p.price).toLocaleString('th-TH', {minimumFractionDigits:2})}</div>
            </div>
        </a>
    `).join('');
}
loadRecentlyViewed();

// Load cart count
(async function() {
    try {
        const res = await fetch('cart_api.php?action=count');
        const data = await res.json();
        document.getElementById('cartBadge').textContent = data.count || 0;
    } catch(e) {}
})();

// Search Autocomplete
(function() {
    const input = document.getElementById('searchInput');
    const dropdown = document.getElementById('acDropdown');
    if (!input || !dropdown) return;
    let timer = null;
    let activeIdx = -1;

    input.addEventListener('input', function() {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { dropdown.classList.remove('open'); return; }
        timer = setTimeout(async () => {
            try {
                const res = await fetch('search_api.php?q=' + encodeURIComponent(q));
                const data = await res.json();
                if (!data.results || data.results.length === 0) { dropdown.classList.remove('open'); return; }
                activeIdx = -1;
                dropdown.innerHTML = data.results.map((p, i) => `
                    <a href="product_detail.php?id=${p.id}" class="ac-item" data-idx="${i}">
                        <div class="ac-item-img">${p.image_url ? `<img src="${p.image_url}" alt="">` : '<span style="font-size:1.2rem">📦</span>'}</div>
                        <div class="ac-item-info">
                            <div class="ac-item-name">${p.name}</div>
                            <div class="ac-item-cat">${p.category_name}</div>
                        </div>
                        <div class="ac-item-price">฿${Number(p.price).toLocaleString('th-TH',{minimumFractionDigits:2})}</div>
                    </a>
                `).join('');
                dropdown.classList.add('open');
            } catch(e) {}
        }, 250);
    });

    input.addEventListener('keydown', function(e) {
        const items = dropdown.querySelectorAll('.ac-item');
        if (!items.length || !dropdown.classList.contains('open')) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); activeIdx = Math.min(activeIdx + 1, items.length - 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); activeIdx = Math.max(activeIdx - 1, 0); }
        else if (e.key === 'Enter' && activeIdx >= 0) { e.preventDefault(); items[activeIdx].click(); return; }
        else if (e.key === 'Escape') { dropdown.classList.remove('open'); return; }
        else return;
        items.forEach((it, i) => it.classList.toggle('active', i === activeIdx));
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-wrapper')) dropdown.classList.remove('open');
    });
})();
</script>

<div class="toast" id="toast"></div>

<!-- Back to Top -->
<button id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" style="position:fixed;bottom:2rem;right:2rem;width:48px;height:48px;border-radius:50%;background:var(--bg-secondary);border:1px solid var(--border);box-shadow:var(--shadow-lg);cursor:pointer;font-size:1.3rem;display:none;align-items:center;justify-content:center;z-index:90;transition:var(--transition);">⬆️</button>
<script>
window.addEventListener('scroll', () => {
    const btn = document.getElementById('backToTop');
    btn.style.display = window.scrollY > 400 ? 'flex' : 'none';
});
</script>

<a href="javascript:history.back()" class="btn-back-float" title="ย้อนกลับ">←</a>
</body>
</html>
