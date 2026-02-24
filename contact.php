<?php
session_start();
require_once 'db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        $stmt = $pdo->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $message]);

        // Create notification for admin
        try {
            $pdo->prepare("INSERT INTO notifications (type, title, message) VALUES ('new_contact', ?, ?)")
                ->execute(["📧 ข้อความใหม่จาก $name", "เรื่อง: $subject"]);
        } catch (Exception $e) {}

        $success = 'ส่งข้อความเรียบร้อยแล้ว! ทีมงานจะติดต่อกลับโดยเร็วที่สุด';
    }
}
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📧 ติดต่อเรา — GamePro</title>
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

        .contact-container { max-width: 700px; margin: 0 auto; padding: 2rem; }
        .contact-title { font-size: 1.8rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.5rem; }
        .contact-desc { color: var(--text-secondary); margin-bottom: 2rem; font-size: 0.95rem; }
        .contact-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 2rem; box-shadow: var(--shadow); }
        .contact-card .form-group { margin-bottom: 1.25rem; }
        .contact-card .form-label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem; color: var(--text-primary); }
        .contact-card .form-control { width: 100%; padding: 0.7rem 0.9rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-primary); color: var(--text-primary); font-size: 0.9rem; font-family: inherit; }
        .contact-card textarea.form-control { min-height: 150px; resize: vertical; }
        .contact-card .form-control:focus { outline: none; border-color: #666; }
        .btn-send { width: 100%; padding: 0.8rem; background: #1a1a1a; color: #fff; border: none; border-radius: var(--radius-sm); font-size: 1rem; font-weight: 700; cursor: pointer; transition: var(--transition); }
        .btn-send:hover { background: #333; transform: translateY(-1px); }
        [data-theme='dark'] .btn-send { background: #f0f0f0; color: #1a1a1a; }
        [data-theme='dark'] .btn-send:hover { background: #ddd; }

        .contact-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 2rem; }
        .info-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem; text-align: center; }
        .info-card .icon { font-size: 2rem; margin-bottom: 0.5rem; }
        .info-card h4 { font-size: 0.9rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.3rem; }
        .info-card p { font-size: 0.8rem; color: var(--text-muted); }

        .alert { padding: 0.75rem 1rem; border-radius: var(--radius-sm); font-size: 0.9rem; margin-bottom: 1.25rem; }
        .alert-success { background: rgba(34,197,94,0.1); color: #22c55e; border: 1px solid rgba(34,197,94,0.2); }
        .alert-danger { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); }
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
        <a href="compare.php">⚖️ เทียบสินค้า</a>
        <a href="contact.php" style="color:var(--text-primary);font-weight:600">📧 ติดต่อเรา</a>
        <a href="login.php">🔐 เข้าสู่ระบบ</a>
        <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
    </div>
</nav>

<div class="contact-container">
    <h1 class="contact-title">📧 ติดต่อเรา</h1>
    <p class="contact-desc">มีคำถาม ข้อเสนอแนะ หรือต้องการแจ้งปัญหา? ติดต่อทีม GamePro ได้ตลอดเวลา</p>

    <?php if ($success): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="contact-card">
        <form method="POST">
            <div class="form-group">
                <label class="form-label">ชื่อ-นามสกุล</label>
                <input type="text" name="name" class="form-control" required placeholder="กรอกชื่อของคุณ" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">อีเมล</label>
                <input type="email" name="email" class="form-control" required placeholder="email@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">หัวข้อ</label>
                <input type="text" name="subject" class="form-control" required placeholder="หัวข้อที่ต้องการติดต่อ" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">ข้อความ</label>
                <textarea name="message" class="form-control" required placeholder="รายละเอียด..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn-send">📤 ส่งข้อความ</button>
        </form>
    </div>

    <div class="contact-info">
        <div class="info-card">
            <div class="icon">📍</div>
            <h4>ที่อยู่</h4>
            <p>123 ถ.เกมเมอร์ กรุงเทพฯ 10100</p>
        </div>
        <div class="info-card">
            <div class="icon">📞</div>
            <h4>โทรศัพท์</h4>
            <p>02-123-4567</p>
        </div>
        <div class="info-card">
            <div class="icon">✉️</div>
            <h4>อีเมล</h4>
            <p>support@gamepro.com</p>
        </div>
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
</body>
</html>
