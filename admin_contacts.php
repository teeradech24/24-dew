<?php
require_once 'auth.php';
require_once 'db.php';

$message = '';
$messageType = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM contacts WHERE id = ?")->execute([$id]);
    $message = 'ลบข้อความเรียบร้อยแล้ว';
    $messageType = 'success';
}

// Handle mark as read
if (isset($_GET['read'])) {
    $id = (int)$_GET['read'];
    $pdo->prepare("UPDATE contacts SET is_read = 1 WHERE id = ?")->execute([$id]);
}

$contacts = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetchAll();
$unreadCount = $pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📧 Messages — GamePro Inventory</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .msg-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 0.75rem; overflow: hidden; transition: var(--transition); }
        .msg-card.unread { border-left: 3px solid #7c3aed; }
        .msg-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; cursor: pointer; }
        .msg-header:hover { background: var(--bg-tertiary); }
        .msg-from { font-weight: 700; font-size: 0.95rem; color: var(--text-primary); }
        .msg-subject { font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.15rem; }
        .msg-date { font-size: 0.75rem; color: var(--text-muted); white-space: nowrap; }
        .msg-body { padding: 1rem 1.25rem; border-top: 1px solid var(--border); display: none; }
        .msg-body.open { display: block; }
        .msg-body p { font-size: 0.9rem; color: var(--text-primary); line-height: 1.6; margin-bottom: 0.75rem; }
        .msg-email { font-size: 0.8rem; color: var(--text-muted); }
        .msg-actions { display: flex; gap: 0.5rem; margin-top: 0.75rem; }
        .msg-actions a { font-size: 0.8rem; padding: 0.3rem 0.7rem; border-radius: var(--radius-sm); font-weight: 600; text-decoration: none; }
        .unread-badge { background: #7c3aed; color: #fff; font-size: 0.65rem; padding: 0.15rem 0.5rem; border-radius: 100px; font-weight: 700; }
    </style>
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="logo-icon">🎮</div>
                <span>GamePro</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-title">Menu</div>
            <a href="index.php" class="nav-link"><span class="nav-icon">📊</span><span>Dashboard</span></a>
            <a href="products.php" class="nav-link"><span class="nav-icon">📦</span><span>Products</span></a>
            <a href="sales.php" class="nav-link"><span class="nav-icon">💰</span><span>Sales</span></a>
            <a href="admin_contacts.php" class="nav-link active"><span class="nav-icon">📧</span><span>Messages</span></a>
            <div class="nav-section-title">หน้าร้าน</div>
            <a href="showcase.php" class="nav-link"><span class="nav-icon">🛍️</span><span>Showcase</span></a>
            <div class="nav-section-title">บัญชี</div>
            <a href="logout.php" class="nav-link"><span class="nav-icon">🚪</span><span>ออกจากระบบ</span></a>
        </nav>
        <div class="sidebar-footer">GamePro Inventory v1.0</div>
    </aside>

    <main class="main-content">
        <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;">
            <div>
                <h1 class="page-title">📧 Messages</h1>
                <p class="page-subtitle">ข้อความจากลูกค้า</p>
            </div>
            <?php if ($unreadCount > 0): ?>
            <span class="unread-badge"><?= $unreadCount ?> ยังไม่อ่าน</span>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= $messageType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <?php if (empty($contacts)): ?>
        <div class="card">
            <div class="empty-state" style="padding:3rem;text-align:center;">
                <div class="empty-icon" style="font-size:3rem;margin-bottom:1rem;">📧</div>
                <p>ยังไม่มีข้อความ</p>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($contacts as $c): ?>
        <div class="msg-card <?= $c['is_read'] ? '' : 'unread' ?>">
            <div class="msg-header" onclick="this.nextElementSibling.classList.toggle('open');<?= $c['is_read'] ? '' : "markRead({$c['id']})" ?>">
                <div>
                    <div class="msg-from"><?= htmlspecialchars($c['name']) ?> <?= $c['is_read'] ? '' : '<span class="unread-badge">ใหม่</span>' ?></div>
                    <div class="msg-subject"><?= htmlspecialchars($c['subject']) ?></div>
                </div>
                <div class="msg-date"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></div>
            </div>
            <div class="msg-body">
                <p><?= nl2br(htmlspecialchars($c['message'])) ?></p>
                <div class="msg-email">📧 <?= htmlspecialchars($c['email']) ?></div>
                <div class="msg-actions">
                    <a href="mailto:<?= htmlspecialchars($c['email']) ?>" style="background:#16a34a;color:#fff;">✉️ ตอบกลับ</a>
                    <a href="admin_contacts.php?delete=<?= $c['id'] ?>" onclick="return confirm('ลบข้อความนี้?')" style="background:#ef4444;color:#fff;">🗑️ ลบ</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>

<button class="mobile-menu-btn" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>

<script>
function markRead(id) {
    fetch('admin_contacts.php?read=' + id);
}
</script>
</body>
</html>
