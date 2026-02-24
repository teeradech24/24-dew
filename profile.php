<?php
session_start();
require_once 'db.php';

// Require login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) { header('Location: login.php'); exit; }

// Handle password change
$pwMsg = '';
$pwOk = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $newPw = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (!password_verify($current, $user['password_hash'])) {
        $pwMsg = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
    } elseif (strlen($newPw) < 4) {
        $pwMsg = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 4 ตัวอักษร';
    } elseif ($newPw !== $confirm) {
        $pwMsg = 'รหัสผ่านใหม่ไม่ตรงกัน';
    } else {
        $hash = password_hash($newPw, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $userId]);
        $pwMsg = 'เปลี่ยนรหัสผ่านสำเร็จ!';
        $pwOk = true;
    }
}

// Handle profile update
$profileMsg = '';
$profileOk = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $pdo->prepare("UPDATE users SET phone = ?, address = ? WHERE id = ?")->execute([$phone, $address, $userId]);
    $profileMsg = 'อัปเดตข้อมูลสำเร็จ!';
    $profileOk = true;
    // Refresh user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
}

// Get user orders
$orders = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {}

// Get user reviews
$reviews = [];
try {
    $stmt = $pdo->prepare("
        SELECT r.*, p.name as product_name, p.image_url 
        FROM product_reviews r 
        JOIN products p ON r.product_id = p.id 
        WHERE r.reviewer_name = ? 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user['username']]);
    $reviews = $stmt->fetchAll();
} catch (Exception $e) {}

// Loyalty info
$loyaltyPoints = (int)($user['loyalty_points'] ?? 0);
$totalSpent = (float)($user['total_spent'] ?? 0);
$tier = $user['loyalty_tier'] ?? 'bronze';

$tierConfig = [
    'bronze'  => ['label' => '🥉 Bronze',  'color' => '#cd7f32', 'bg' => 'rgba(205,127,50,0.1)',  'rate' => 1.0,  'min' => 0],
    'silver'  => ['label' => '🥈 Silver',  'color' => '#888',    'bg' => 'rgba(192,192,192,0.15)', 'rate' => 1.5,  'min' => 10000],
    'gold'    => ['label' => '🥇 Gold',    'color' => '#b8860b', 'bg' => 'rgba(255,215,0,0.1)',    'rate' => 2.0,  'min' => 30000],
    'diamond' => ['label' => '💎 Diamond', 'color' => '#0ea5e9', 'bg' => 'rgba(185,242,255,0.15)', 'rate' => 3.0,  'min' => 100000],
];
$tc = $tierConfig[$tier] ?? $tierConfig['bronze'];

// Next tier progress
$tierOrder = ['bronze', 'silver', 'gold', 'diamond'];
$currentIdx = array_search($tier, $tierOrder);
$nextTier = null;
$progress = 100;
if ($currentIdx < 3) {
    $next = $tierOrder[$currentIdx + 1];
    $nextTier = $tierConfig[$next];
    $nextTier['name'] = $next;
    $range = $nextTier['min'] - $tc['min'];
    $progress = $range > 0 ? min(100, (($totalSpent - $tc['min']) / $range) * 100) : 100;
}

// Loyalty transactions
$transactions = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM loyalty_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$userId]);
    $transactions = $stmt->fetchAll();
} catch (Exception $e) {}

$statusLabels = ['pending'=>'⏳ รอดำเนินการ','confirmed'=>'✅ ยืนยันแล้ว','shipped'=>'🚚 จัดส่งแล้ว','completed'=>'🎉 เสร็จสิ้น','cancelled'=>'❌ ยกเลิก'];
$statusColors = ['pending'=>'#f59e0b','confirmed'=>'#16a34a','shipped'=>'#2563eb','completed'=>'#059669','cancelled'=>'#dc2626'];

$activeTab = $_GET['tab'] ?? 'info';
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👤 โปรไฟล์ — GamePro</title>
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

        .profile-container { max-width: 900px; margin: 0 auto; padding: 2rem; }

        /* Profile Header */
        .profile-hero { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 2rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1.5rem; }
        .profile-avatar { width: 80px; height: 80px; background: linear-gradient(135deg, #7c3aed, #6d28d9); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #fff; font-weight: 800; flex-shrink: 0; box-shadow: 0 4px 15px rgba(124,58,237,0.3); }
        .profile-meta h1 { font-size: 1.4rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.2rem; }
        .profile-meta .email { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.4rem; }
        .profile-meta .tier-badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.25rem 0.75rem; border-radius: 100px; font-size: 0.78rem; font-weight: 700; }
        .profile-meta .join-date { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem; }

        /* Tabs */
        .profile-tabs { display: flex; gap: 0.25rem; margin-bottom: 1.5rem; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 0.3rem; overflow-x: auto; }
        .profile-tab { padding: 0.6rem 1.2rem; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; color: var(--text-muted); cursor: pointer; transition: var(--transition); white-space: nowrap; text-decoration: none; text-align: center; flex: 1; }
        .profile-tab:hover { color: var(--text-primary); background: var(--bg-tertiary); }
        .profile-tab.active { background: var(--text-primary); color: var(--bg-primary); }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* Info Tab */
        .info-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1rem; }
        .info-card h3 { font-size: 1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.4rem; }
        .info-row { display: flex; align-items: center; padding: 0.6rem 0; border-bottom: 1px solid var(--border); }
        .info-row:last-child { border-bottom: none; }
        .info-label { width: 120px; font-size: 0.82rem; font-weight: 600; color: var(--text-muted); flex-shrink: 0; }
        .info-value { font-size: 0.9rem; color: var(--text-primary); font-weight: 500; }
        .form-group { display: flex; flex-direction: column; gap: 0.3rem; margin-bottom: 0.75rem; }
        .form-group label { font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); }
        .form-group input, .form-group textarea { padding: 0.6rem 0.8rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-primary); color: var(--text-primary); font-size: 0.88rem; outline: none; transition: var(--transition); font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.1); }
        .form-group textarea { resize: vertical; min-height: 70px; }
        .btn-save { padding: 0.65rem 1.5rem; background: #7c3aed; color: #fff; border: none; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 700; cursor: pointer; transition: var(--transition); }
        .btn-save:hover { background: #6d28d9; transform: translateY(-1px); }
        .alert { padding: 0.6rem 1rem; border-radius: var(--radius-sm); font-size: 0.82rem; font-weight: 600; margin-bottom: 0.75rem; }
        .alert-ok { background: rgba(34,197,94,0.1); color: #16a34a; border: 1px solid rgba(34,197,94,0.2); }
        .alert-err { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); }

        /* Orders Tab */
        .order-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 0.75rem; overflow: hidden; }
        .order-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; cursor: pointer; transition: var(--transition); }
        .order-header:hover { background: var(--bg-tertiary); }
        .order-num { font-weight: 700; font-size: 0.9rem; color: var(--text-primary); }
        .order-date { font-size: 0.75rem; color: var(--text-muted); }
        .order-status { padding: 0.2rem 0.6rem; border-radius: 100px; font-size: 0.7rem; font-weight: 700; color: #fff; }
        .order-total { font-weight: 800; font-size: 1rem; color: var(--text-primary); }
        .order-body { padding: 1rem 1.25rem; display: none; border-top: 1px solid var(--border); }
        .order-body.open { display: block; }
        .order-item { display: flex; align-items: center; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid var(--border); font-size: 0.82rem; }
        .order-item:last-child { border-bottom: none; }
        .order-item-name { color: var(--text-primary); font-weight: 600; }
        .order-item-detail { color: var(--text-muted); }

        /* Status Timeline */
        .status-timeline { display: flex; align-items: center; gap: 0; margin: 1rem 0 0.5rem; padding: 0 0.5rem; }
        .timeline-step { display: flex; flex-direction: column; align-items: center; flex: 1; position: relative; }
        .timeline-dot { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; z-index: 2; border: 2px solid var(--border); background: var(--bg-secondary); color: var(--text-muted); }
        .timeline-dot.done { background: #16a34a; border-color: #16a34a; color: #fff; }
        .timeline-dot.current { background: #2563eb; border-color: #2563eb; color: #fff; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(37,99,235,0.4); } 50% { box-shadow: 0 0 0 8px rgba(37,99,235,0); } }
        .timeline-label { font-size: 0.65rem; color: var(--text-muted); margin-top: 0.3rem; text-align: center; font-weight: 500; }
        .timeline-step:not(:last-child)::after { content: ''; position: absolute; top: 14px; left: 50%; width: 100%; height: 2px; background: var(--border); z-index: 1; }
        .timeline-step.done:not(:last-child)::after { background: #16a34a; }

        /* Loyalty Tab */
        .loyalty-hero { background: linear-gradient(135deg, #1a1a2e, #16213e); border-radius: var(--radius); padding: 2rem; margin-bottom: 1rem; color: #fff; position: relative; overflow: hidden; }
        .loyalty-hero::before { content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%; filter: blur(100px); opacity: 0.2; top: -50%; right: -10%; }
        .loyalty-hero.bronze::before { background: #cd7f32; }
        .loyalty-hero.silver::before { background: #c0c0c0; }
        .loyalty-hero.gold::before { background: #ffd700; }
        .loyalty-hero.diamond::before { background: #00d4ff; }
        .loyalty-tier-big { font-size: 2rem; font-weight: 900; margin-bottom: 0.3rem; }
        .loyalty-points-big { font-size: 2.5rem; font-weight: 900; margin-bottom: 0.2rem; }
        .loyalty-points-big small { font-size: 1rem; font-weight: 400; opacity: 0.7; }
        .loyalty-subtitle { font-size: 0.85rem; opacity: 0.7; margin-bottom: 1rem; }
        .loyalty-progress { background: rgba(255,255,255,0.1); border-radius: 100px; height: 8px; overflow: hidden; margin-bottom: 0.4rem; }
        .loyalty-progress-bar { height: 100%; border-radius: 100px; transition: width 1s ease; }
        .loyalty-progress-bar.bronze { background: linear-gradient(90deg, #cd7f32, #daa520); }
        .loyalty-progress-bar.silver { background: linear-gradient(90deg, #c0c0c0, #e0e0e0); }
        .loyalty-progress-bar.gold { background: linear-gradient(90deg, #ffd700, #ffed4a); }
        .loyalty-progress-bar.diamond { background: linear-gradient(90deg, #00d4ff, #7c3aed); }
        .loyalty-next { font-size: 0.75rem; opacity: 0.6; }
        .loyalty-rate { display: inline-flex; align-items: center; gap: 0.3rem; background: rgba(255,255,255,0.1); padding: 0.3rem 0.8rem; border-radius: 100px; font-size: 0.78rem; font-weight: 600; margin-top: 0.5rem; }

        .loyalty-tiers-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1rem; }
        .tier-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 1rem; text-align: center; transition: var(--transition); }
        .tier-card.current { border-color: #7c3aed; box-shadow: 0 0 0 2px rgba(124,58,237,0.2); }
        .tier-card .tier-icon { font-size: 1.5rem; margin-bottom: 0.3rem; }
        .tier-card .tier-name { font-size: 0.8rem; font-weight: 700; color: var(--text-primary); }
        .tier-card .tier-req { font-size: 0.68rem; color: var(--text-muted); }
        .tier-card .tier-rate { font-size: 0.7rem; color: var(--text-secondary); font-weight: 600; margin-top: 0.2rem; }

        .tx-list { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); }
        .tx-list h3 { padding: 1rem 1.25rem 0.75rem; font-size: 0.95rem; font-weight: 700; color: var(--text-primary); }
        .tx-item { display: flex; align-items: center; justify-content: space-between; padding: 0.6rem 1.25rem; border-top: 1px solid var(--border); font-size: 0.82rem; }
        .tx-item .tx-desc { color: var(--text-secondary); }
        .tx-item .tx-date { font-size: 0.72rem; color: var(--text-muted); }
        .tx-item .tx-points { font-weight: 700; }
        .tx-item .tx-points.earn { color: #16a34a; }
        .tx-item .tx-points.redeem { color: #dc2626; }

        /* Reviews Tab */
        .review-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem 1.25rem; margin-bottom: 0.75rem; display: flex; gap: 1rem; align-items: start; }
        .review-product-img { width: 60px; height: 60px; background: #fff; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
        [data-theme='dark'] .review-product-img { background: #1a1a1a; }
        .review-product-img img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .review-content { flex: 1; }
        .review-product-name { font-size: 0.85rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.2rem; }
        .review-stars { color: #f59e0b; font-size: 0.9rem; letter-spacing: 1px; margin-bottom: 0.3rem; }
        .review-text { font-size: 0.82rem; color: var(--text-secondary); line-height: 1.4; }
        .review-date { font-size: 0.7rem; color: var(--text-muted); margin-top: 0.3rem; }

        .empty-state { text-align: center; padding: 3rem 1rem; color: var(--text-muted); }
        .empty-state .icon { font-size: 3rem; margin-bottom: 0.75rem; }
        .empty-state p { font-size: 0.9rem; }

        @media (max-width: 768px) {
            .profile-container { padding: 1rem; }
            .profile-hero { flex-direction: column; text-align: center; }
            .loyalty-tiers-grid { grid-template-columns: repeat(2, 1fr); }
            .profile-tabs { overflow-x: auto; }
        }
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
        <a href="cart.php">🛒 ตะกร้า <span class="cart-badge" id="cartBadge">0</span></a>
        <a href="orders.php">📋 คำสั่งซื้อ</a>
        <a href="profile.php" style="color:var(--text-primary);font-weight:600">👤 <?= htmlspecialchars($user['username']) ?></a>
        <a href="logout.php">🚪 ออก</a>
        <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
    </div>
</nav>

<div class="profile-container">
    <!-- Profile Hero -->
    <div class="profile-hero">
        <div class="profile-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
        <div class="profile-meta">
            <h1><?= htmlspecialchars($user['username']) ?></h1>
            <div class="email"><?= htmlspecialchars($user['email']) ?></div>
            <span class="tier-badge" style="background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>"><?= $tc['label'] ?> · <?= number_format($loyaltyPoints) ?> แต้ม</span>
            <div class="join-date">สมาชิกตั้งแต่ <?= date('d/m/Y', strtotime($user['created_at'])) ?></div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="profile-tabs">
        <a href="?tab=info" class="profile-tab <?= $activeTab === 'info' ? 'active' : '' ?>">👤 ข้อมูลส่วนตัว</a>
        <a href="?tab=orders" class="profile-tab <?= $activeTab === 'orders' ? 'active' : '' ?>">📋 คำสั่งซื้อ (<?= count($orders) ?>)</a>
        <a href="?tab=loyalty" class="profile-tab <?= $activeTab === 'loyalty' ? 'active' : '' ?>">🎮 แต้มสะสม</a>
        <a href="?tab=reviews" class="profile-tab <?= $activeTab === 'reviews' ? 'active' : '' ?>">⭐ รีวิว (<?= count($reviews) ?>)</a>
    </div>

    <!-- Tab: Info -->
    <div class="tab-content <?= $activeTab === 'info' ? 'active' : '' ?>" id="tabInfo">
        <div class="info-card">
            <h3>📌 ข้อมูลบัญชี</h3>
            <div class="info-row"><span class="info-label">ชื่อผู้ใช้</span><span class="info-value"><?= htmlspecialchars($user['username']) ?></span></div>
            <div class="info-row"><span class="info-label">อีเมล</span><span class="info-value"><?= htmlspecialchars($user['email']) ?></span></div>
            <div class="info-row"><span class="info-label">ระดับ</span><span class="info-value"><?= $tc['label'] ?></span></div>
            <div class="info-row"><span class="info-label">สิทธิ์</span><span class="info-value"><?= $user['role'] === 'admin' ? '🛡️ Admin' : '👤 User' ?></span></div>
            <div class="info-row"><span class="info-label">วันสมัคร</span><span class="info-value"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></span></div>
        </div>

        <!-- Update Profile -->
        <div class="info-card">
            <h3>📝 แก้ไขข้อมูลติดต่อ</h3>
            <?php if ($profileMsg): ?>
                <div class="alert <?= $profileOk ? 'alert-ok' : 'alert-err' ?>"><?= $profileOk ? '✅' : '❌' ?> <?= htmlspecialchars($profileMsg) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="update_profile" value="1">
                <div class="form-group">
                    <label>เบอร์โทรศัพท์</label>
                    <input type="tel" name="phone" placeholder="0xx-xxx-xxxx" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>ที่อยู่</label>
                    <textarea name="address" placeholder="ที่อยู่สำหรับจัดส่งสินค้า"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn-save">💾 บันทึก</button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="info-card">
            <h3>🔒 เปลี่ยนรหัสผ่าน</h3>
            <?php if ($pwMsg): ?>
                <div class="alert <?= $pwOk ? 'alert-ok' : 'alert-err' ?>"><?= $pwOk ? '✅' : '❌' ?> <?= htmlspecialchars($pwMsg) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="change_password" value="1">
                <div class="form-group">
                    <label>รหัสผ่านปัจจุบัน</label>
                    <input type="password" name="current_password" required placeholder="รหัสผ่านเดิม">
                </div>
                <div class="form-group">
                    <label>รหัสผ่านใหม่</label>
                    <input type="password" name="new_password" required placeholder="อย่างน้อย 4 ตัวอักษร">
                </div>
                <div class="form-group">
                    <label>ยืนยันรหัสผ่านใหม่</label>
                    <input type="password" name="confirm_password" required placeholder="กรอกอีกครั้ง">
                </div>
                <button type="submit" class="btn-save">🔑 เปลี่ยนรหัสผ่าน</button>
            </form>
        </div>
    </div>

    <!-- Tab: Orders -->
    <div class="tab-content <?= $activeTab === 'orders' ? 'active' : '' ?>" id="tabOrders">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="icon">📋</div>
                <p>ยังไม่มีคำสั่งซื้อ</p>
                <a href="showcase.php" style="display:inline-block;margin-top:1rem;padding:0.6rem 1.2rem;background:#1a1a1a;color:#fff;border-radius:6px;font-weight:600;font-size:0.85rem;text-decoration:none">🏠 เลือกซื้อสินค้า</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $o):
                $items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $items->execute([$o['id']]);
                $orderItems = $items->fetchAll();
                $color = $statusColors[$o['status']] ?? '#666';
                $label = $statusLabels[$o['status']] ?? $o['status'];
                
                // Timeline
                $statusOrder = ['pending', 'confirmed', 'shipped', 'completed'];
                $currentStatusIdx = array_search($o['status'], $statusOrder);
                if ($currentStatusIdx === false) $currentStatusIdx = -1;
            ?>
            <div class="order-card">
                <div class="order-header" onclick="this.parentElement.querySelector('.order-body').classList.toggle('open')">
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
                    <!-- Status Timeline -->
                    <?php if ($o['status'] !== 'cancelled'): ?>
                    <div class="status-timeline">
                        <?php 
                        $steps = [
                            ['icon' => '📝', 'label' => 'สั่งซื้อ'],
                            ['icon' => '✅', 'label' => 'ยืนยัน'],
                            ['icon' => '🚚', 'label' => 'จัดส่ง'],
                            ['icon' => '🎉', 'label' => 'สำเร็จ'],
                        ];
                        foreach ($steps as $i => $step):
                            $isDone = $i < $currentStatusIdx;
                            $isCurrent = $i === $currentStatusIdx;
                        ?>
                        <div class="timeline-step <?= $isDone ? 'done' : '' ?>">
                            <div class="timeline-dot <?= $isDone ? 'done' : ($isCurrent ? 'current' : '') ?>"><?= $step['icon'] ?></div>
                            <div class="timeline-label"><?= $step['label'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php foreach ($orderItems as $item): ?>
                    <div class="order-item">
                        <span class="order-item-name"><?= htmlspecialchars($item['product_name']) ?></span>
                        <span class="order-item-detail"><?= $item['quantity'] ?> × ฿<?= number_format($item['price'], 2) ?> = ฿<?= number_format($item['subtotal'], 2) ?></span>
                    </div>
                    <?php endforeach; ?>

                    <?php if (!empty($o['shipping_name'])): ?>
                    <div style="margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid var(--border);font-size:0.78rem;color:var(--text-muted)">
                        📦 จัดส่งถึง: <?= htmlspecialchars($o['shipping_name']) ?> (<?= htmlspecialchars($o['shipping_phone'] ?? '') ?>)
                        <?php if ($o['payment_method']): ?>
                         · 💰 <?= $o['payment_method'] === 'transfer' ? 'โอนเงิน' : ($o['payment_method'] === 'promptpay' ? 'PromptPay' : 'COD') ?>
                        <?php endif; ?>
                        <?php if ($o['points_earned'] > 0): ?>
                         · ⭐ ได้รับ <?= $o['points_earned'] ?> แต้ม
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Tab: Loyalty -->
    <div class="tab-content <?= $activeTab === 'loyalty' ? 'active' : '' ?>" id="tabLoyalty">
        <!-- Loyalty Hero -->
        <div class="loyalty-hero <?= $tier ?>">
            <div class="loyalty-tier-big"><?= $tc['label'] ?></div>
            <div class="loyalty-points-big"><?= number_format($loyaltyPoints) ?> <small>แต้ม</small></div>
            <div class="loyalty-subtitle">ยอดสะสมทั้งหมด ฿<?= number_format($totalSpent, 2) ?></div>
            
            <?php if ($nextTier): ?>
            <div class="loyalty-progress">
                <div class="loyalty-progress-bar <?= $tier ?>" style="width:<?= round($progress, 1) ?>%"></div>
            </div>
            <div class="loyalty-next">อีก ฿<?= number_format(max(0, $nextTier['min'] - $totalSpent), 2) ?> เลื่อนขั้นเป็น <?= $nextTier['label'] ?></div>
            <?php else: ?>
            <div class="loyalty-next">🎉 คุณอยู่ระดับสูงสุดแล้ว!</div>
            <?php endif; ?>
            
            <div class="loyalty-rate">⭐ รับ <?= $tc['rate'] ?> แต้ม / ฿100</div>
        </div>

        <!-- Tier Cards -->
        <div class="loyalty-tiers-grid">
            <?php foreach ($tierConfig as $tName => $tConf): ?>
            <div class="tier-card <?= $tier === $tName ? 'current' : '' ?>">
                <div class="tier-icon"><?= explode(' ', $tConf['label'])[0] ?></div>
                <div class="tier-name"><?= $tConf['label'] ?></div>
                <div class="tier-req"><?= $tConf['min'] > 0 ? '฿' . number_format($tConf['min']) . '+' : 'เริ่มต้น' ?></div>
                <div class="tier-rate"><?= $tConf['rate'] ?> แต้ม/฿100</div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Transaction History -->
        <div class="tx-list">
            <h3>📜 ประวัติแต้ม</h3>
            <?php if (empty($transactions)): ?>
                <div style="padding:2rem;text-align:center;color:var(--text-muted);font-size:0.85rem">ยังไม่มีรายการ</div>
            <?php else: ?>
                <?php foreach ($transactions as $tx): ?>
                <div class="tx-item">
                    <div>
                        <div class="tx-desc"><?= htmlspecialchars($tx['description']) ?></div>
                        <div class="tx-date"><?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?></div>
                    </div>
                    <div class="tx-points <?= $tx['type'] ?>"><?= $tx['type'] === 'earn' ? '+' : '-' ?><?= number_format($tx['points']) ?> แต้ม</div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tab: Reviews -->
    <div class="tab-content <?= $activeTab === 'reviews' ? 'active' : '' ?>" id="tabReviews">
        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <div class="icon">⭐</div>
                <p>ยังไม่ได้เขียนรีวิว</p>
            </div>
        <?php else: ?>
            <?php foreach ($reviews as $r): ?>
            <div class="review-card">
                <div class="review-product-img">
                    <?php if (!empty($r['image_url'])): ?>
                        <img src="<?= htmlspecialchars($r['image_url']) ?>" alt="">
                    <?php else: ?>
                        <span style="font-size:1.5rem">📦</span>
                    <?php endif; ?>
                </div>
                <div class="review-content">
                    <a href="product_detail.php?id=<?= $r['product_id'] ?>" class="review-product-name" style="text-decoration:none"><?= htmlspecialchars($r['product_name']) ?></a>
                    <div class="review-stars"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?></div>
                    <div class="review-text"><?= htmlspecialchars($r['comment']) ?></div>
                    <div class="review-date"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
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
