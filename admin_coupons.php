<?php
require_once 'auth.php';
require_once 'db.php';

$message = '';
$messageType = '';

// Handle add coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $type = $_POST['discount_type'] ?? 'percent';
    $value = (float)($_POST['discount_value'] ?? 0);
    $minOrder = (float)($_POST['min_order'] ?? 0);
    $maxUses = (int)($_POST['max_uses'] ?? 0) ?: null;
    $expires = trim($_POST['expires_at'] ?? '') ?: null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($code && $value > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_order, max_uses, expires_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $type, $value, $minOrder, $maxUses, $expires, $isActive]);
            $message = "เพิ่มคูปอง {$code} เรียบร้อย";
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'โค้ดคูปองซ้ำ หรือเกิดข้อผิดพลาด';
            $messageType = 'danger';
        }
    } else {
        $message = 'กรุณากรอกข้อมูลให้ครบ';
        $messageType = 'danger';
    }
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $type = $_POST['discount_type'] ?? 'percent';
    $value = (float)($_POST['discount_value'] ?? 0);
    $minOrder = (float)($_POST['min_order'] ?? 0);
    $maxUses = (int)($_POST['max_uses'] ?? 0) ?: null;
    $expires = trim($_POST['expires_at'] ?? '') ?: null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $pdo->prepare("UPDATE coupons SET code=?, discount_type=?, discount_value=?, min_order=?, max_uses=?, expires_at=?, is_active=? WHERE id=?")
        ->execute([$code, $type, $value, $minOrder, $maxUses, $expires, $isActive, $id]);
    $message = "แก้ไขคูปอง {$code} เรียบร้อย";
    $messageType = 'success';
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([$id]);
    $message = "ลบคูปอง #{$id} เรียบร้อย";
    $messageType = 'success';
}

// Handle toggle
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE coupons SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
    $message = "สลับสถานะคูปองเรียบร้อย";
    $messageType = 'success';
}

// Fetch coupons
$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
$activeCount = count(array_filter($coupons, fn($c) => $c['is_active']));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎟️ Coupons — GamePro Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .coupon-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; margin-bottom: 1.5rem; }
        .cs-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem; text-align: center; }
        .cs-card .cs-num { font-size: 1.5rem; font-weight: 800; color: var(--text-primary); }
        .cs-card .cs-label { font-size: 0.72rem; color: var(--text-muted); font-weight: 600; }

        .add-form { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem; margin-bottom: 1.5rem; }
        .add-form h3 { font-size: 0.95rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem; }
        .fg { display: flex; flex-direction: column; gap: 0.25rem; }
        .fg label { font-size: 0.72rem; font-weight: 600; color: var(--text-muted); }
        .fg input, .fg select { padding: 0.5rem 0.65rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-primary); color: var(--text-primary); font-size: 0.82rem; font-family: inherit; }
        .fg input:focus, .fg select:focus { outline: none; border-color: #7c3aed; box-shadow: 0 0 0 2px rgba(124,58,237,0.1); }
        .form-actions { display: flex; gap: 0.5rem; align-items: end; }
        .btn-add { padding: 0.5rem 1.2rem; background: #7c3aed; color: #fff; border: none; border-radius: var(--radius-sm); font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: var(--transition); white-space: nowrap; }
        .btn-add:hover { background: #6d28d9; }

        .coupon-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 0.5rem; overflow: hidden; }
        .coupon-header { display: grid; grid-template-columns: 120px 100px 100px 80px 80px 80px 60px; align-items: center; padding: 0.75rem 1rem; gap: 0.5rem; font-size: 0.82rem; }
        .coupon-code { font-weight: 800; font-family: monospace; font-size: 0.9rem; color: var(--text-primary); letter-spacing: 0.02em; }
        .coupon-discount { font-weight: 700; color: #16a34a; }
        .coupon-min { color: var(--text-muted); font-size: 0.75rem; }
        .coupon-uses { color: var(--text-secondary); font-size: 0.78rem; }
        .coupon-expires { color: var(--text-muted); font-size: 0.72rem; }
        .coupon-status { text-align: center; }
        .coupon-actions { display: flex; gap: 0.3rem; justify-content: flex-end; }
        .coupon-actions a { font-size: 0.82rem; padding: 0.2rem 0.4rem; border-radius: 4px; text-decoration: none; transition: var(--transition); }
        .coupon-actions a:hover { background: var(--bg-tertiary); }
        .active-badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 100px; font-size: 0.68rem; font-weight: 700; }
        .active-badge.on { background: rgba(22,163,74,0.1); color: #16a34a; }
        .active-badge.off { background: rgba(239,68,68,0.1); color: #ef4444; }

        .expired { opacity: 0.5; }

        /* Edit modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
        .modal-overlay.show { display: flex; }
        .modal-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; }
        .modal-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: 1rem; }
        .modal-close { float: right; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted); }

        @media (max-width: 900px) {
            .coupon-header { grid-template-columns: 1fr 1fr; }
            .form-grid { grid-template-columns: 1fr 1fr; }
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
            <a href="admin_users.php" class="nav-link"><span class="nav-icon">👥</span><span>Members</span></a>
            <a href="admin_coupons.php" class="nav-link active"><span class="nav-icon">🎟️</span><span>Coupons</span></a>
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
            <h1 class="page-title">🎟️ Coupons</h1>
            <p class="page-subtitle">จัดการคูปองส่วนลด</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= $messageType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <div class="coupon-stats">
            <div class="cs-card"><div class="cs-num"><?= count($coupons) ?></div><div class="cs-label">คูปองทั้งหมด</div></div>
            <div class="cs-card"><div class="cs-num" style="color:#16a34a"><?= $activeCount ?></div><div class="cs-label">✅ ใช้ได้</div></div>
            <div class="cs-card"><div class="cs-num" style="color:#ef4444"><?= count($coupons) - $activeCount ?></div><div class="cs-label">❌ ปิดใช้งาน</div></div>
        </div>

        <!-- Add Form -->
        <div class="add-form">
            <h3>➕ เพิ่มคูปองใหม่</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="fg">
                        <label>โค้ดคูปอง *</label>
                        <input type="text" name="code" required placeholder="SALE20" maxlength="30" style="text-transform:uppercase;font-weight:700;letter-spacing:0.05em">
                    </div>
                    <div class="fg">
                        <label>ประเภทส่วนลด</label>
                        <select name="discount_type">
                            <option value="percent">% เปอร์เซ็นต์</option>
                            <option value="fixed">฿ จำนวนเงิน</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label>ค่าส่วนลด *</label>
                        <input type="number" name="discount_value" required step="0.01" min="0.01" placeholder="10">
                    </div>
                    <div class="fg">
                        <label>ขั้นต่ำ (฿)</label>
                        <input type="number" name="min_order" step="0.01" min="0" value="0" placeholder="0">
                    </div>
                    <div class="fg">
                        <label>ใช้ได้สูงสุด (ครั้ง)</label>
                        <input type="number" name="max_uses" min="0" placeholder="ไม่จำกัด">
                    </div>
                    <div class="fg">
                        <label>หมดอายุ</label>
                        <input type="datetime-local" name="expires_at">
                    </div>
                    <div class="fg" style="justify-content:end">
                        <label style="display:flex;align-items:center;gap:0.4rem;cursor:pointer">
                            <input type="checkbox" name="is_active" checked> เปิดใช้งาน
                        </label>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-add">🎟️ เพิ่มคูปอง</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Coupons List -->
        <?php if (empty($coupons)): ?>
        <div class="card">
            <div class="empty-state" style="padding:3rem;text-align:center">
                <div style="font-size:3rem;margin-bottom:0.75rem">🎟️</div>
                <p style="color:var(--text-muted)">ยังไม่มีคูปอง</p>
            </div>
        </div>
        <?php else: ?>
        <div class="card" style="padding:0">
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr>
                        <th style="padding:0.6rem 1rem;text-align:left;font-size:0.75rem;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border)">โค้ด</th>
                        <th style="padding:0.6rem 0.5rem;text-align:left;font-size:0.75rem;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border)">ส่วนลด</th>
                        <th style="padding:0.6rem 0.5rem;text-align:left;font-size:0.75rem;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border)">ขั้นต่ำ</th>
                        <th style="padding:0.6rem 0.5rem;text-align:center;font-size:0.75rem;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border)">ใช้แล้ว</th>
                        <th style="padding:0.6rem 0.5rem;text-align:left;font-size:0.75rem;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border)">หมดอายุ</th>
                        <th style="padding:0.6rem 0.5rem;text-align:center;font-size:0.75rem;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border)">สถานะ</th>
                        <th style="padding:0.6rem 1rem;text-align:right;font-size:0.75rem;color:var(--text-muted);font-weight:700;border-bottom:1px solid var(--border)">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($coupons as $c):
                    $isExpired = $c['expires_at'] && strtotime($c['expires_at']) < time();
                    $isFull = $c['max_uses'] && $c['used_count'] >= $c['max_uses'];
                ?>
                <tr style="border-bottom:1px solid var(--border);<?= $isExpired || !$c['is_active'] ? 'opacity:0.5' : '' ?>">
                    <td style="padding:0.6rem 1rem;font-weight:800;font-family:monospace;font-size:0.88rem;color:var(--text-primary);letter-spacing:0.02em"><?= htmlspecialchars($c['code']) ?></td>
                    <td style="padding:0.6rem 0.5rem;font-weight:700;color:#16a34a;font-size:0.85rem"><?= $c['discount_type'] === 'percent' ? $c['discount_value'] . '%' : '฿' . number_format($c['discount_value'], 2) ?></td>
                    <td style="padding:0.6rem 0.5rem;font-size:0.78rem;color:var(--text-muted)"><?= $c['min_order'] > 0 ? '฿' . number_format($c['min_order'], 2) : '-' ?></td>
                    <td style="padding:0.6rem 0.5rem;text-align:center;font-size:0.82rem;color:var(--text-secondary)"><?= $c['used_count'] ?><?= $c['max_uses'] ? '/' . $c['max_uses'] : '' ?></td>
                    <td style="padding:0.6rem 0.5rem;font-size:0.72rem;color:var(--text-muted)">
                        <?php if ($c['expires_at']): ?>
                            <?= date('d/m/Y H:i', strtotime($c['expires_at'])) ?>
                            <?php if ($isExpired): ?><span style="color:#ef4444;font-weight:700"> (หมดอายุ)</span><?php endif; ?>
                        <?php else: ?>
                            ไม่มี
                        <?php endif; ?>
                    </td>
                    <td style="padding:0.6rem 0.5rem;text-align:center">
                        <a href="?toggle=<?= $c['id'] ?>" class="active-badge <?= $c['is_active'] ? 'on' : 'off' ?>" style="text-decoration:none"><?= $c['is_active'] ? '✅ ON' : '❌ OFF' ?></a>
                    </td>
                    <td style="padding:0.6rem 1rem;text-align:right">
                        <a href="#" onclick="editCoupon(<?= htmlspecialchars(json_encode($c)) ?>);return false" style="color:#7c3aed;font-size:0.8rem;font-weight:600;text-decoration:none;margin-right:0.5rem">✏️ แก้ไข</a>
                        <a href="?delete=<?= $c['id'] ?>" onclick="return confirm('ลบคูปอง <?= $c['code'] ?>?')" style="color:#ef4444;font-size:0.8rem;font-weight:600;text-decoration:none">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-card">
        <button class="modal-close" onclick="document.getElementById('editModal').classList.remove('show')">✕</button>
        <h3>✏️ แก้ไขคูปอง</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <div class="form-grid">
                <div class="fg"><label>โค้ด</label><input type="text" name="code" id="editCode" required></div>
                <div class="fg"><label>ประเภท</label><select name="discount_type" id="editType"><option value="percent">%</option><option value="fixed">฿</option></select></div>
                <div class="fg"><label>ค่าส่วนลด</label><input type="number" name="discount_value" id="editValue" step="0.01" required></div>
                <div class="fg"><label>ขั้นต่ำ</label><input type="number" name="min_order" id="editMin" step="0.01"></div>
                <div class="fg"><label>ใช้ได้สูงสุด</label><input type="number" name="max_uses" id="editMaxUses"></div>
                <div class="fg"><label>หมดอายุ</label><input type="datetime-local" name="expires_at" id="editExpires"></div>
                <div class="fg" style="justify-content:end"><label><input type="checkbox" name="is_active" id="editActive"> เปิดใช้งาน</label></div>
                <div class="form-actions"><button type="submit" class="btn-add">💾 บันทึก</button></div>
            </div>
        </form>
    </div>
</div>

<button class="mobile-menu-btn" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>

<script>
function editCoupon(c) {
    document.getElementById('editId').value = c.id;
    document.getElementById('editCode').value = c.code;
    document.getElementById('editType').value = c.discount_type;
    document.getElementById('editValue').value = c.discount_value;
    document.getElementById('editMin').value = c.min_order;
    document.getElementById('editMaxUses').value = c.max_uses || '';
    if (c.expires_at) {
        const d = new Date(c.expires_at);
        document.getElementById('editExpires').value = d.toISOString().slice(0, 16);
    } else {
        document.getElementById('editExpires').value = '';
    }
    document.getElementById('editActive').checked = c.is_active == 1;
    document.getElementById('editModal').classList.add('show');
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('show');
});
</script>
<a href="javascript:history.back()" class="btn-back-float" title="ย้อนกลับ">←</a>
</body>
</html>
