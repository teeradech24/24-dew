<?php
require_once 'auth.php';
require_once 'db.php';

$message = '';
$messageType = '';

// Handle delete
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid !== ($_SESSION['user_id'] ?? 0)) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
        $message = "ลบสมาชิก #{$uid} เรียบร้อย";
        $messageType = 'success';
    } else {
        $message = 'ไม่สามารถลบบัญชีตัวเองได้';
        $messageType = 'danger';
    }
}

// Handle role change
if (isset($_POST['change_role'])) {
    $uid = (int)$_POST['user_id'];
    $newRole = $_POST['new_role'];
    if (in_array($newRole, ['admin', 'user'])) {
        $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $uid]);
        $message = "เปลี่ยน role สมาชิก #{$uid} เป็น {$newRole}";
        $messageType = 'success';
    }
}

// Handle points adjustment
if (isset($_POST['adjust_points'])) {
    $uid = (int)$_POST['user_id'];
    $points = (int)$_POST['points_amount'];
    $type = $_POST['adjust_type']; // add or subtract
    if ($type === 'add') {
        $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?")->execute([$points, $uid]);
        try {
            $pdo->prepare("INSERT INTO loyalty_transactions (user_id, type, points, description) VALUES (?, 'earn', ?, ?)")
                ->execute([$uid, $points, "Admin เพิ่มแต้ม +{$points}"]);
        } catch (Exception $e) {}
        $message = "เพิ่ม {$points} แต้มให้สมาชิก #{$uid}";
    } else {
        $pdo->prepare("UPDATE users SET loyalty_points = GREATEST(0, loyalty_points - ?) WHERE id = ?")->execute([$points, $uid]);
        try {
            $pdo->prepare("INSERT INTO loyalty_transactions (user_id, type, points, description) VALUES (?, 'redeem', ?, ?)")
                ->execute([$uid, $points, "Admin หักแต้ม -{$points}"]);
        } catch (Exception $e) {}
        $message = "หัก {$points} แต้มจากสมาชิก #{$uid}";
    }
    $messageType = 'success';
}

// Handle tier change
if (isset($_POST['change_tier'])) {
    $uid = (int)$_POST['user_id'];
    $newTier = $_POST['new_tier'];
    if (in_array($newTier, ['bronze', 'silver', 'gold', 'diamond'])) {
        $pdo->prepare("UPDATE users SET loyalty_tier = ? WHERE id = ?")->execute([$newTier, $uid]);
        $message = "เปลี่ยน tier สมาชิก #{$uid} เป็น {$newTier}";
        $messageType = 'success';
    }
}

// Fetch users
$users = [];
try {
    $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
} catch (Exception $e) {}

$tierLabels = ['bronze'=>'🥉 Bronze','silver'=>'🥈 Silver','gold'=>'🥇 Gold','diamond'=>'💎 Diamond'];
$tierColors = ['bronze'=>'#cd7f32','silver'=>'#888','gold'=>'#b8860b','diamond'=>'#0ea5e9'];

$totalUsers = count($users);
$adminCount = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$userCount = $totalUsers - $adminCount;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👥 Members — GamePro Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .user-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; margin-bottom: 1.5rem; }
        .us-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem; text-align: center; }
        .us-card .us-num { font-size: 1.5rem; font-weight: 800; color: var(--text-primary); }
        .us-card .us-label { font-size: 0.72rem; color: var(--text-muted); font-weight: 600; }

        .user-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 0.5rem; overflow: hidden; }
        .user-header { display: grid; grid-template-columns: 50px 1fr 80px 90px 90px 60px; align-items: center; padding: 0.75rem 1rem; gap: 0.5rem; cursor: pointer; transition: var(--transition); }
        .user-header:hover { background: var(--bg-tertiary); }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; color: #fff; }
        .user-info .user-name { font-weight: 700; font-size: 0.88rem; color: var(--text-primary); }
        .user-info .user-email { font-size: 0.72rem; color: var(--text-muted); }
        .user-role { font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 100px; text-align: center; }
        .user-tier { font-size: 0.7rem; font-weight: 700; text-align: center; }
        .user-points { font-size: 0.85rem; font-weight: 700; color: var(--text-primary); text-align: right; }
        .user-toggle { text-align: right; color: var(--text-muted); font-size: 0.9rem; }

        .user-detail { display: none; padding: 1rem; border-top: 1px solid var(--border); }
        .user-detail.open { display: block; }
        .ud-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .ud-section { padding: 0.75rem; background: var(--bg-primary); border-radius: var(--radius-sm); }
        .ud-section h4 { font-size: 0.82rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem; }
        .ud-row { display: flex; justify-content: space-between; font-size: 0.78rem; padding: 0.25rem 0; }
        .ud-row .label { color: var(--text-muted); }
        .ud-row .value { color: var(--text-primary); font-weight: 600; }

        .ud-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.75rem; align-items: center; }
        .ud-actions form { display: inline-flex; align-items: center; gap: 0.3rem; }
        .ud-actions select, .ud-actions input[type="number"] { padding: 0.3rem 0.5rem; border: 1px solid var(--border); border-radius: 4px; background: var(--bg-primary); color: var(--text-primary); font-size: 0.75rem; }
        .ud-actions button { padding: 0.3rem 0.6rem; border: 1px solid var(--border); border-radius: 4px; font-size: 0.72rem; font-weight: 600; cursor: pointer; background: var(--bg-primary); color: var(--text-secondary); transition: var(--transition); }
        .ud-actions button:hover { border-color: #7c3aed; color: #7c3aed; }
        .btn-del { background: rgba(239,68,68,0.05) !important; color: #ef4444 !important; border-color: #ef4444 !important; text-decoration: none; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.72rem; font-weight: 600; }
        .btn-del:hover { background: #ef4444 !important; color: #fff !important; }

        @media (max-width: 900px) {
            .user-header { grid-template-columns: 40px 1fr 1fr; }
            .ud-grid { grid-template-columns: 1fr; }
            .user-stats { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo"><div class="logo-icon">🎮</div><span>GamePro</span></div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-title">Menu</div>
            <a href="index.php" class="nav-link"><span class="nav-icon">📊</span><span>Dashboard</span></a>
            <a href="products.php" class="nav-link"><span class="nav-icon">📦</span><span>Products</span></a>
            <a href="sales.php" class="nav-link"><span class="nav-icon">💰</span><span>Sales</span></a>
            <a href="admin_orders.php" class="nav-link"><span class="nav-icon">📋</span><span>Orders</span></a>
            <a href="admin_users.php" class="nav-link active"><span class="nav-icon">👥</span><span>Members</span></a>
            <a href="admin_coupons.php" class="nav-link"><span class="nav-icon">🎟️</span><span>Coupons</span></a>
            <a href="admin_promotions.php" class="nav-link"><span class="nav-icon">📢</span><span>Promotions</span></a>
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
            <h1 class="page-title">👥 Members</h1>
            <p class="page-subtitle">จัดการสมาชิกและระดับ Loyalty</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= $messageType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <div class="user-stats">
            <div class="us-card"><div class="us-num"><?= $totalUsers ?></div><div class="us-label">สมาชิกทั้งหมด</div></div>
            <div class="us-card"><div class="us-num"><?= $userCount ?></div><div class="us-label">👤 Users</div></div>
            <div class="us-card"><div class="us-num"><?= $adminCount ?></div><div class="us-label">🛡️ Admins</div></div>
        </div>

        <?php if (empty($users)): ?>
        <div class="card">
            <div class="empty-state" style="padding:3rem;text-align:center">
                <div style="font-size:3rem;margin-bottom:0.75rem">👥</div>
                <p style="color:var(--text-muted)">ยังไม่มีสมาชิก</p>
            </div>
        </div>
        <?php else: ?>
        <?php
        $avatarColors = ['#7c3aed','#2563eb','#16a34a','#f59e0b','#ef4444','#ec4899','#06b6d4','#8b5cf6'];
        foreach ($users as $i => $u):
            $color = $avatarColors[$i % count($avatarColors)];
            $tier = $u['loyalty_tier'] ?? 'bronze';
            $points = (int)($u['loyalty_points'] ?? 0);
            $totalSpent = (float)($u['total_spent'] ?? 0);
        ?>
        <div class="user-card">
            <div class="user-header" onclick="this.nextElementSibling.classList.toggle('open')">
                <div class="user-avatar" style="background:<?= $color ?>"><?= strtoupper(substr($u['username'], 0, 1)) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($u['username']) ?></div>
                    <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
                </div>
                <div>
                    <span class="user-role" style="background:<?= $u['role'] === 'admin' ? 'rgba(124,58,237,0.1);color:#7c3aed' : 'rgba(107,114,128,0.1);color:#6b7280' ?>"><?= $u['role'] === 'admin' ? '🛡️ Admin' : '👤 User' ?></span>
                </div>
                <div class="user-tier" style="color:<?= $tierColors[$tier] ?? '#cd7f32' ?>"><?= $tierLabels[$tier] ?? '🥉 Bronze' ?></div>
                <div class="user-points"><?= number_format($points) ?> pt</div>
                <div class="user-toggle">▼</div>
            </div>
            <div class="user-detail">
                <div class="ud-grid">
                    <div class="ud-section">
                        <h4>📌 ข้อมูลบัญชี</h4>
                        <div class="ud-row"><span class="label">ID</span><span class="value">#<?= $u['id'] ?></span></div>
                        <div class="ud-row"><span class="label">Username</span><span class="value"><?= htmlspecialchars($u['username']) ?></span></div>
                        <div class="ud-row"><span class="label">Email</span><span class="value"><?= htmlspecialchars($u['email']) ?></span></div>
                        <div class="ud-row"><span class="label">เบอร์โทร</span><span class="value"><?= htmlspecialchars($u['phone'] ?? '-') ?></span></div>
                        <div class="ud-row"><span class="label">สมัครเมื่อ</span><span class="value"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></span></div>
                    </div>
                    <div class="ud-section">
                        <h4>🎮 Loyalty</h4>
                        <div class="ud-row"><span class="label">Tier</span><span class="value" style="color:<?= $tierColors[$tier] ?>"><?= $tierLabels[$tier] ?></span></div>
                        <div class="ud-row"><span class="label">แต้มสะสม</span><span class="value"><?= number_format($points) ?> แต้ม</span></div>
                        <div class="ud-row"><span class="label">ยอดสะสม</span><span class="value">฿<?= number_format($totalSpent, 2) ?></span></div>
                        <div class="ud-row"><span class="label">ที่อยู่</span><span class="value" style="max-width:200px;word-wrap:break-word"><?= htmlspecialchars($u['address'] ?? '-') ?></span></div>
                    </div>
                </div>
                <div class="ud-actions">
                    <!-- Role -->
                    <form method="POST">
                        <input type="hidden" name="change_role" value="1">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <select name="new_role">
                            <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>👤 User</option>
                            <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>🛡️ Admin</option>
                        </select>
                        <button type="submit">เปลี่ยน Role</button>
                    </form>
                    <!-- Tier -->
                    <form method="POST">
                        <input type="hidden" name="change_tier" value="1">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <select name="new_tier">
                            <?php foreach ($tierLabels as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $tier === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">เปลี่ยน Tier</button>
                    </form>
                    <!-- Points -->
                    <form method="POST">
                        <input type="hidden" name="adjust_points" value="1">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <select name="adjust_type"><option value="add">+ เพิ่ม</option><option value="subtract">- หัก</option></select>
                        <input type="number" name="points_amount" value="100" min="1" max="99999" style="width:70px">
                        <button type="submit">ปรับแต้ม</button>
                    </form>
                    <!-- Delete -->
                    <?php if ($u['id'] !== ($_SESSION['user_id'] ?? 0)): ?>
                    <a href="?delete=<?= $u['id'] ?>" class="btn-del" onclick="return confirm('ลบสมาชิก <?= htmlspecialchars($u['username']) ?>?')">🗑️ ลบ</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
<button class="mobile-menu-btn" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>
<a href="javascript:history.back()" class="btn-back-float" title="ย้อนกลับ">←</a>
</body>
</html>
