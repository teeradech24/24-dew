<?php
require_once 'auth.php';
require_once 'db.php';

$message = '';
$messageType = '';
$tab = $_GET['tab'] ?? 'banners';

// ========== BANNER ACTIONS ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_banner') {
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $tag = trim($_POST['tag'] ?? '');
        $tagIcon = trim($_POST['tag_icon'] ?? '🔥');
        $link = trim($_POST['link_url'] ?? 'showcase.php');
        $productId = (int)($_POST['image_product_id'] ?? 0) ?: null;
        $priceDisplay = trim($_POST['price_display'] ?? '');
        $discountLabel = trim($_POST['discount_label'] ?? '');
        $startDate = trim($_POST['start_date'] ?? '') ?: null;
        $endDate = trim($_POST['end_date'] ?? '') ?: null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if ($title) {
            $pdo->prepare("INSERT INTO promotions (title, subtitle, tag, tag_icon, link_url, image_product_id, price_display, discount_label, start_date, end_date, is_active, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$title, $subtitle, $tag, $tagIcon, $link, $productId, $priceDisplay, $discountLabel, $startDate, $endDate, $isActive, $sortOrder]);
            $message = "เพิ่มแบนเนอร์ \"{$title}\" เรียบร้อย";
            $messageType = 'success';
        }
    }
    if ($_POST['action'] === 'edit_banner') {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $tag = trim($_POST['tag'] ?? '');
        $tagIcon = trim($_POST['tag_icon'] ?? '🔥');
        $link = trim($_POST['link_url'] ?? 'showcase.php');
        $productId = (int)($_POST['image_product_id'] ?? 0) ?: null;
        $priceDisplay = trim($_POST['price_display'] ?? '');
        $discountLabel = trim($_POST['discount_label'] ?? '');
        $startDate = trim($_POST['start_date'] ?? '') ?: null;
        $endDate = trim($_POST['end_date'] ?? '') ?: null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        $pdo->prepare("UPDATE promotions SET title=?, subtitle=?, tag=?, tag_icon=?, link_url=?, image_product_id=?, price_display=?, discount_label=?, start_date=?, end_date=?, is_active=?, sort_order=? WHERE id=?")
            ->execute([$title, $subtitle, $tag, $tagIcon, $link, $productId, $priceDisplay, $discountLabel, $startDate, $endDate, $isActive, $sortOrder, $id]);
        $message = "แก้ไขแบนเนอร์เรียบร้อย";
        $messageType = 'success';
    }
    if ($_POST['action'] === 'add_flash') {
        $productId = (int)$_POST['product_id'];
        $salePrice = (float)$_POST['sale_price'];
        $originalPrice = (float)$_POST['original_price'];
        $discount = (int)$_POST['discount_percent'];
        $qtyLimit = (int)($_POST['quantity_limit'] ?? 0);
        $startDate = $_POST['start_date'] ?? date('Y-m-d H:i:s');
        $endDate = $_POST['end_date'] ?? date('Y-m-d H:i:s', strtotime('+24 hours'));
        $fsTitle = trim($_POST['title'] ?? 'Flash Sale');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($productId && $salePrice > 0) {
            $pdo->prepare("INSERT INTO flash_sales (title, product_id, sale_price, original_price, discount_percent, quantity_limit, start_date, end_date, is_active) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$fsTitle, $productId, $salePrice, $originalPrice, $discount, $qtyLimit, $startDate, $endDate, $isActive]);
            $message = "เพิ่ม Flash Sale เรียบร้อย";
            $messageType = 'success';
            $tab = 'flash';
        }
    }
}

if (isset($_GET['delete_banner'])) {
    $pdo->prepare("DELETE FROM promotions WHERE id = ?")->execute([(int)$_GET['delete_banner']]);
    $message = "ลบแบนเนอร์เรียบร้อย"; $messageType = 'success';
}
if (isset($_GET['toggle_banner'])) {
    $pdo->prepare("UPDATE promotions SET is_active = NOT is_active WHERE id = ?")->execute([(int)$_GET['toggle_banner']]);
    $message = "สลับสถานะแบนเนอร์เรียบร้อย"; $messageType = 'success';
}
if (isset($_GET['delete_flash'])) {
    $pdo->prepare("DELETE FROM flash_sales WHERE id = ?")->execute([(int)$_GET['delete_flash']]);
    $message = "ลบ Flash Sale เรียบร้อย"; $messageType = 'success'; $tab = 'flash';
}
if (isset($_GET['toggle_flash'])) {
    $pdo->prepare("UPDATE flash_sales SET is_active = NOT is_active WHERE id = ?")->execute([(int)$_GET['toggle_flash']]);
    $message = "สลับสถานะ Flash Sale เรียบร้อย"; $messageType = 'success'; $tab = 'flash';
}

// Fetch data
$banners = []; $flashSales = []; $products = [];
try { $banners = $pdo->query("SELECT p.*, pr.name as product_name, pr.image_url as product_image FROM promotions p LEFT JOIN products pr ON p.image_product_id = pr.id ORDER BY sort_order ASC, created_at DESC")->fetchAll(); } catch(Exception $e) {}
try { $flashSales = $pdo->query("SELECT fs.*, p.name as product_name, p.image_url, p.price as current_price, c.name as category_name FROM flash_sales fs JOIN products p ON fs.product_id = p.id JOIN categories c ON p.category_id = c.id ORDER BY fs.end_date ASC")->fetchAll(); } catch(Exception $e) {}
try { $products = $pdo->query("SELECT id, name, price FROM products ORDER BY name")->fetchAll(); } catch(Exception $e) {}

$activeBanners = count(array_filter($banners, fn($b) => $b['is_active']));
$activeFlash = count(array_filter($flashSales, fn($f) => $f['is_active'] && strtotime($f['end_date']) > time()));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📢 Promotions — GamePro Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .promo-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1.5rem; }
        .ps-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem; text-align: center; }
        .ps-card .num { font-size: 1.5rem; font-weight: 800; color: var(--text-primary); }
        .ps-card .label { font-size: 0.72rem; color: var(--text-muted); font-weight: 600; }

        .tab-btns { display: flex; gap: 0.3rem; margin-bottom: 1.5rem; }
        .tab-btn { padding: 0.6rem 1.5rem; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; text-decoration: none; color: var(--text-secondary); background: var(--bg-secondary); border: 1px solid var(--border); transition: var(--transition); }
        .tab-btn:hover { color: var(--text-primary); }
        .tab-btn.active { background: var(--text-primary); color: var(--bg-primary); border-color: var(--text-primary); }

        .add-form { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem; margin-bottom: 1.5rem; }
        .add-form h3 { font-size: 0.95rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem; }
        .fg { display: flex; flex-direction: column; gap: 0.25rem; }
        .fg label { font-size: 0.72rem; font-weight: 600; color: var(--text-muted); }
        .fg input, .fg select, .fg textarea { padding: 0.5rem 0.65rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-primary); color: var(--text-primary); font-size: 0.82rem; font-family: inherit; }
        .fg input:focus, .fg select:focus, .fg textarea:focus { outline: none; border-color: #7c3aed; box-shadow: 0 0 0 2px rgba(124,58,237,0.1); }
        .fg textarea { resize: vertical; min-height: 50px; }
        .btn-add { padding: 0.55rem 1.3rem; background: #7c3aed; color: #fff; border: none; border-radius: var(--radius-sm); font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: var(--transition); }
        .btn-add:hover { background: #6d28d9; }

        /* Banner card */
        .banner-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 0.5rem; overflow: hidden; }
        .bc-header { display: grid; grid-template-columns: 60px 1fr 80px 80px 60px; align-items: center; padding: 0.75rem 1rem; gap: 0.75rem; }
        .bc-order { width: 36px; height: 36px; border-radius: 50%; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; color: var(--text-primary); }
        .bc-info .bc-title { font-weight: 700; font-size: 0.88rem; color: var(--text-primary); }
        .bc-info .bc-sub { font-size: 0.72rem; color: var(--text-muted); margin-top: 0.1rem; }
        .bc-tag { font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 100px; background: rgba(124,58,237,0.1); color: #7c3aed; text-align: center; }
        .bc-status { text-align: center; }
        .bc-actions { display: flex; gap: 0.3rem; justify-content: flex-end; }
        .bc-actions a { font-size: 0.82rem; padding: 0.2rem 0.3rem; border-radius: 4px; text-decoration: none; }
        .status-dot { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 100px; font-size: 0.68rem; font-weight: 700; }
        .status-dot.on { background: rgba(22,163,74,0.1); color: #16a34a; }
        .status-dot.off { background: rgba(239,68,68,0.1); color: #ef4444; }

        /* Flash card */
        .flash-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 0.5rem; display: grid; grid-template-columns: 70px 1fr 120px 100px 80px 90px; align-items: center; padding: 0.75rem 1rem; gap: 0.75rem; }
        .fc-img { width: 50px; height: 50px; border-radius: 6px; background: #fff; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        [data-theme='dark'] .fc-img { background: #1a1a1a; }
        .fc-img img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .fc-name { font-weight: 700; font-size: 0.85rem; color: var(--text-primary); }
        .fc-cat { font-size: 0.7rem; color: var(--text-muted); }
        .fc-prices { text-align: right; }
        .fc-sale { font-size: 1rem; font-weight: 800; color: #dc2626; }
        .fc-orig { font-size: 0.72rem; color: var(--text-muted); text-decoration: line-through; }
        .fc-discount { background: #dc2626; color: #fff; padding: 0.15rem 0.5rem; border-radius: 100px; font-size: 0.7rem; font-weight: 700; }
        .fc-time { font-size: 0.72rem; color: var(--text-muted); text-align: center; }
        .fc-time .remaining { font-weight: 700; color: var(--text-primary); }
        .fc-progress { margin-top: 0.2rem; }
        .fc-progress-bar { height: 4px; background: var(--bg-tertiary); border-radius: 100px; overflow: hidden; }
        .fc-progress-fill { height: 100%; background: linear-gradient(90deg, #f59e0b, #ef4444); border-radius: 100px; }
        .fc-actions { display: flex; gap: 0.3rem; justify-content: flex-end; }
        .fc-actions a { font-size: 0.82rem; padding: 0.2rem 0.3rem; text-decoration: none; }

        .expired-row { opacity: 0.4; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
        .modal-overlay.show { display: flex; }
        .modal-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; max-width: 600px; width: 92%; max-height: 85vh; overflow-y: auto; }
        .modal-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: 1rem; }
        .modal-close { float: right; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted); }

        @media (max-width: 900px) {
            .promo-stats { grid-template-columns: repeat(2, 1fr); }
            .flash-card { grid-template-columns: 1fr 1fr; }
            .bc-header { grid-template-columns: 40px 1fr; }
        }
    </style>
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-header"><div class="sidebar-logo"><div class="logo-icon">🎮</div><span>GamePro</span></div></div>
        <nav class="sidebar-nav">
            <div class="nav-section-title">Menu</div>
            <a href="index.php" class="nav-link"><span class="nav-icon">📊</span><span>Dashboard</span></a>
            <a href="products.php" class="nav-link"><span class="nav-icon">📦</span><span>Products</span></a>
            <a href="sales.php" class="nav-link"><span class="nav-icon">💰</span><span>Sales</span></a>
            <a href="admin_orders.php" class="nav-link"><span class="nav-icon">📋</span><span>Orders</span></a>
            <a href="admin_users.php" class="nav-link"><span class="nav-icon">👥</span><span>Members</span></a>
            <a href="admin_coupons.php" class="nav-link"><span class="nav-icon">🎟️</span><span>Coupons</span></a>
            <a href="admin_promotions.php" class="nav-link active"><span class="nav-icon">📢</span><span>Promotions</span></a>
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
            <h1 class="page-title">📢 Promotions</h1>
            <p class="page-subtitle">จัดการแบนเนอร์โปรโมชั่นและ Flash Sale</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= $messageType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="promo-stats">
            <div class="ps-card"><div class="num"><?= count($banners) ?></div><div class="label">📢 แบนเนอร์ทั้งหมด</div></div>
            <div class="ps-card"><div class="num" style="color:#16a34a"><?= $activeBanners ?></div><div class="label">✅ แสดงอยู่</div></div>
            <div class="ps-card"><div class="num"><?= count($flashSales) ?></div><div class="label">⚡ Flash Sale ทั้งหมด</div></div>
            <div class="ps-card"><div class="num" style="color:#f59e0b"><?= $activeFlash ?></div><div class="label">🔥 กำลังลด</div></div>
        </div>

        <div class="tab-btns">
            <a href="?tab=banners" class="tab-btn <?= $tab === 'banners' ? 'active' : '' ?>">📢 แบนเนอร์โปรโมชั่น</a>
            <a href="?tab=flash" class="tab-btn <?= $tab === 'flash' ? 'active' : '' ?>">⚡ Flash Sale</a>
        </div>

        <?php if ($tab === 'banners'): ?>
        <!-- ========== BANNERS TAB ========== -->
        <div class="add-form">
            <h3>➕ เพิ่มแบนเนอร์ใหม่</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_banner">
                <div class="form-grid">
                    <div class="fg"><label>หัวข้อ *</label><input type="text" name="title" required placeholder="โปรโมชั่นต้อนรับสมาชิก"></div>
                    <div class="fg"><label>ข้อความรอง</label><input type="text" name="subtitle" placeholder="ลดสูงสุด 50%"></div>
                    <div class="fg"><label>แท็ก</label><input type="text" name="tag" placeholder="MEGA SALE"></div>
                    <div class="fg"><label>ไอคอนแท็ก</label><input type="text" name="tag_icon" value="🔥" maxlength="5" style="text-align:center;font-size:1.2rem"></div>
                    <div class="fg"><label>ลิงก์ปุ่ม</label><input type="text" name="link_url" value="showcase.php"></div>
                    <div class="fg"><label>สินค้าประกอบ (รูป)</label>
                        <select name="image_product_id"><option value="">— ไม่มี —</option>
                        <?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select>
                    </div>
                    <div class="fg"><label>ราคาแสดง</label><input type="text" name="price_display" placeholder="฿2,490"></div>
                    <div class="fg"><label>ป้ายลด</label><input type="text" name="discount_label" placeholder="-30%"></div>
                    <div class="fg"><label>เริ่ม</label><input type="datetime-local" name="start_date"></div>
                    <div class="fg"><label>สิ้นสุด</label><input type="datetime-local" name="end_date"></div>
                    <div class="fg"><label>ลำดับ</label><input type="number" name="sort_order" value="0" min="0"></div>
                    <div class="fg" style="justify-content:end"><label><input type="checkbox" name="is_active" checked> เปิดใช้งาน</label></div>
                </div>
                <div style="margin-top:0.75rem"><button type="submit" class="btn-add">📢 เพิ่มแบนเนอร์</button></div>
            </form>
        </div>

        <?php if (empty($banners)): ?>
        <div class="card"><div class="empty-state" style="padding:3rem;text-align:center"><div style="font-size:3rem;margin-bottom:0.75rem">📢</div><p style="color:var(--text-muted)">ยังไม่มีแบนเนอร์</p></div></div>
        <?php else: ?>
            <?php foreach ($banners as $b): ?>
            <div class="banner-card">
                <div class="bc-header">
                    <div class="bc-order">#<?= $b['sort_order'] ?></div>
                    <div class="bc-info">
                        <div class="bc-title"><?= $b['tag_icon'] ?> <?= htmlspecialchars($b['title']) ?></div>
                        <div class="bc-sub"><?= htmlspecialchars($b['subtitle']) ?><?= $b['product_name'] ? ' · 📦 '.htmlspecialchars($b['product_name']) : '' ?></div>
                    </div>
                    <div><?php if ($b['tag']): ?><span class="bc-tag"><?= htmlspecialchars($b['tag']) ?><?= $b['discount_label'] ? ' '.$b['discount_label'] : '' ?></span><?php endif; ?></div>
                    <div class="bc-status">
                        <a href="?toggle_banner=<?= $b['id'] ?>&tab=banners" class="status-dot <?= $b['is_active'] ? 'on' : 'off' ?>" style="text-decoration:none"><?= $b['is_active'] ? '✅ ON' : '❌ OFF' ?></a>
                    </div>
                    <div class="bc-actions">
                        <a href="#" onclick="editBanner(<?= htmlspecialchars(json_encode($b)) ?>);return false" title="แก้ไข">✏️</a>
                        <a href="?delete_banner=<?= $b['id'] ?>&tab=banners" onclick="return confirm('ลบแบนเนอร์นี้?')" title="ลบ" style="color:#ef4444">🗑️</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php else: ?>
        <!-- ========== FLASH SALE TAB ========== -->
        <div class="add-form">
            <h3>⚡ เพิ่ม Flash Sale</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_flash">
                <div class="form-grid">
                    <div class="fg"><label>ชื่อดีล</label><input type="text" name="title" value="Flash Sale" placeholder="Flash Deal!"></div>
                    <div class="fg"><label>สินค้า *</label>
                        <select name="product_id" id="flashProduct" required onchange="autoFillPrice()">
                            <option value="">— เลือกสินค้า —</option>
                            <?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>"><?= htmlspecialchars($p['name']) ?> (฿<?= number_format($p['price'], 2) ?>)</option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg"><label>ราคาปกติ</label><input type="number" name="original_price" id="origPrice" step="0.01" required></div>
                    <div class="fg"><label>ราคาลด *</label><input type="number" name="sale_price" id="salePrice" step="0.01" required></div>
                    <div class="fg"><label>% ส่วนลด</label><input type="number" name="discount_percent" id="discPercent" min="0" max="99"></div>
                    <div class="fg"><label>จำนวนจำกัด</label><input type="number" name="quantity_limit" value="10" min="0"></div>
                    <div class="fg"><label>เริ่ม</label><input type="datetime-local" name="start_date" value="<?= date('Y-m-d\TH:i') ?>"></div>
                    <div class="fg"><label>สิ้นสุด *</label><input type="datetime-local" name="end_date" value="<?= date('Y-m-d\TH:i', strtotime('+24 hours')) ?>" required></div>
                    <div class="fg" style="justify-content:end"><label><input type="checkbox" name="is_active" checked> เปิดใช้งาน</label></div>
                </div>
                <div style="margin-top:0.75rem"><button type="submit" class="btn-add">⚡ เพิ่ม Flash Sale</button></div>
            </form>
        </div>

        <?php if (empty($flashSales)): ?>
        <div class="card"><div class="empty-state" style="padding:3rem;text-align:center"><div style="font-size:3rem;margin-bottom:0.75rem">⚡</div><p style="color:var(--text-muted)">ยังไม่มี Flash Sale</p></div></div>
        <?php else: ?>
            <?php foreach ($flashSales as $f):
                $isExpired = strtotime($f['end_date']) < time();
                $isNotStarted = strtotime($f['start_date']) > time();
                $progress = $f['quantity_limit'] > 0 ? min(100, round(($f['quantity_sold'] / $f['quantity_limit']) * 100)) : 0;
                $remaining = max(0, strtotime($f['end_date']) - time());
                $hours = floor($remaining / 3600);
                $mins = floor(($remaining % 3600) / 60);
            ?>
            <div class="flash-card <?= $isExpired ? 'expired-row' : '' ?>">
                <div class="fc-img">
                    <?php if ($f['image_url']): ?><img src="<?= htmlspecialchars($f['image_url']) ?>" alt=""><?php else: ?><span style="font-size:1.5rem">📦</span><?php endif; ?>
                </div>
                <div>
                    <div class="fc-name"><?= htmlspecialchars($f['product_name']) ?></div>
                    <div class="fc-cat"><?= htmlspecialchars($f['category_name']) ?> · <?= htmlspecialchars($f['title']) ?></div>
                </div>
                <div class="fc-prices">
                    <div class="fc-sale">฿<?= number_format($f['sale_price'], 2) ?></div>
                    <div class="fc-orig">฿<?= number_format($f['original_price'], 2) ?></div>
                </div>
                <div style="text-align:center">
                    <span class="fc-discount">-<?= $f['discount_percent'] ?>%</span>
                    <div class="fc-progress" style="margin-top:0.3rem">
                        <div style="font-size:0.65rem;color:var(--text-muted);margin-bottom:0.15rem"><?= $f['quantity_sold'] ?>/<?= $f['quantity_limit'] ?: '∞' ?> ขายแล้ว</div>
                        <div class="fc-progress-bar"><div class="fc-progress-fill" style="width:<?= $progress ?>%"></div></div>
                    </div>
                </div>
                <div class="fc-time">
                    <?php if ($isExpired): ?>
                        <span style="color:#ef4444;font-weight:700">หมดเวลา</span>
                    <?php elseif ($isNotStarted): ?>
                        <span style="color:#f59e0b;font-weight:700">ยังไม่เริ่ม</span>
                    <?php else: ?>
                        <div class="remaining"><?= $hours ?>ชม. <?= $mins ?>น.</div>
                        <div style="font-size:0.65rem">เหลือ</div>
                    <?php endif; ?>
                    <a href="?toggle_flash=<?= $f['id'] ?>&tab=flash" class="status-dot <?= $f['is_active'] ? 'on' : 'off' ?>" style="text-decoration:none;margin-top:0.3rem;display:inline-block"><?= $f['is_active'] ? 'ON' : 'OFF' ?></a>
                </div>
                <div class="fc-actions">
                    <a href="?delete_flash=<?= $f['id'] ?>&tab=flash" onclick="return confirm('ลบ Flash Sale นี้?')" style="color:#ef4444" title="ลบ">🗑️</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<!-- Edit Banner Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-card">
        <button class="modal-close" onclick="document.getElementById('editModal').classList.remove('show')">✕</button>
        <h3>✏️ แก้ไขแบนเนอร์</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit_banner">
            <input type="hidden" name="id" id="eId">
            <div class="form-grid">
                <div class="fg"><label>หัวข้อ</label><input type="text" name="title" id="eTitle" required></div>
                <div class="fg"><label>ข้อความรอง</label><input type="text" name="subtitle" id="eSub"></div>
                <div class="fg"><label>แท็ก</label><input type="text" name="tag" id="eTag"></div>
                <div class="fg"><label>ไอคอน</label><input type="text" name="tag_icon" id="eIcon" style="text-align:center;font-size:1.2rem"></div>
                <div class="fg"><label>ลิงก์</label><input type="text" name="link_url" id="eLink"></div>
                <div class="fg"><label>สินค้าประกอบ</label>
                    <select name="image_product_id" id="eProduct"><option value="">— ไม่มี —</option>
                    <?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select>
                </div>
                <div class="fg"><label>ราคาแสดง</label><input type="text" name="price_display" id="ePrice"></div>
                <div class="fg"><label>ป้ายลด</label><input type="text" name="discount_label" id="eDiscount"></div>
                <div class="fg"><label>เริ่ม</label><input type="datetime-local" name="start_date" id="eStart"></div>
                <div class="fg"><label>สิ้นสุด</label><input type="datetime-local" name="end_date" id="eEnd"></div>
                <div class="fg"><label>ลำดับ</label><input type="number" name="sort_order" id="eSort" min="0"></div>
                <div class="fg" style="justify-content:end"><label><input type="checkbox" name="is_active" id="eActive"> เปิดใช้งาน</label></div>
            </div>
            <div style="margin-top:0.75rem"><button type="submit" class="btn-add">💾 บันทึก</button></div>
        </form>
    </div>
</div>

<button class="mobile-menu-btn" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>

<script>
function editBanner(b) {
    document.getElementById('eId').value = b.id;
    document.getElementById('eTitle').value = b.title;
    document.getElementById('eSub').value = b.subtitle || '';
    document.getElementById('eTag').value = b.tag || '';
    document.getElementById('eIcon').value = b.tag_icon || '🔥';
    document.getElementById('eLink').value = b.link_url || '';
    document.getElementById('eProduct').value = b.image_product_id || '';
    document.getElementById('ePrice').value = b.price_display || '';
    document.getElementById('eDiscount').value = b.discount_label || '';
    document.getElementById('eSort').value = b.sort_order || 0;
    document.getElementById('eActive').checked = b.is_active == 1;
    if (b.start_date) document.getElementById('eStart').value = b.start_date.replace(' ', 'T').substring(0, 16);
    if (b.end_date) document.getElementById('eEnd').value = b.end_date.replace(' ', 'T').substring(0, 16);
    document.getElementById('editModal').classList.add('show');
}
document.getElementById('editModal').addEventListener('click', function(e) { if (e.target === this) this.classList.remove('show'); });

function autoFillPrice() {
    const sel = document.getElementById('flashProduct');
    const opt = sel.options[sel.selectedIndex];
    const price = parseFloat(opt.dataset.price || 0);
    document.getElementById('origPrice').value = price.toFixed(2);
    document.getElementById('salePrice').value = (price * 0.8).toFixed(2);
    document.getElementById('discPercent').value = 20;
}
</script>
<a href="javascript:history.back()" class="btn-back-float" title="ย้อนกลับ">←</a>
</body>
</html>
