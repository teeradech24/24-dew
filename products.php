<?php
require_once 'auth.php';
require_once 'db.php';

$message = '';
$messageType = '';

// Handle DELETE
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'ลบสินค้าเรียบร้อยแล้ว';
    $messageType = 'success';
}

// Handle ADD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $price = (float) ($_POST['price'] ?? 0);
    $stock = (int) ($_POST['stock_quantity'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($name && $category_id > 0 && $price > 0) {
        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, price, stock_quantity) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$category_id, $name, $description, $price, $stock]);
        $newId = $pdo->lastInsertId();
        
        // Handle image upload
        if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $filename = 'product_' . $newId . '.' . $ext;
                $dest = __DIR__ . '/assets/images/products/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?")->execute(['assets/images/products/' . $filename, $newId]);
                }
            }
        }
        
        $message = 'เพิ่มสินค้าเรียบร้อยแล้ว';
        $messageType = 'success';
    } else {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        $messageType = 'danger';
    }
}

// Handle EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $price = (float) ($_POST['price'] ?? 0);
    $stock = (int) ($_POST['stock_quantity'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($id > 0 && $name && $category_id > 0 && $price > 0) {
        $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, stock_quantity = ? WHERE id = ?");
        $stmt->execute([$category_id, $name, $description, $price, $stock, $id]);
        
        // Handle image upload
        if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $filename = 'product_' . $id . '.' . $ext;
                $dest = __DIR__ . '/assets/images/products/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?")->execute(['assets/images/products/' . $filename, $id]);
                }
            }
        }
        
        $message = 'แก้ไขสินค้าเรียบร้อยแล้ว';
        $messageType = 'success';
    } else {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        $messageType = 'danger';
    }
}

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Fetch products with category name
$products = $pdo->query("
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products — GamePro Inventory</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="logo-icon">🎮</div>
                <span>GamePro</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-title">Menu</div>
            <a href="index.php" class="nav-link">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="products.php" class="nav-link active">
                <span class="nav-icon">📦</span>
                <span>Products</span>
            </a>
            <a href="sales.php" class="nav-link">
                <span class="nav-icon">💰</span>
                <span>Sales</span>
            </a>
            <a href="admin_contacts.php" class="nav-link">
                <span class="nav-icon">📧</span>
                <span>Messages</span>
            </a>
            <a href="admin_orders.php" class="nav-link">
                <span class="nav-icon">📋</span>
                <span>Orders</span>
            </a>
            <a href="admin_users.php" class="nav-link">
                <span class="nav-icon">👥</span>
                <span>Members</span>
            </a>
            <a href="admin_coupons.php" class="nav-link">
                <span class="nav-icon">🎟️</span>
                <span>Coupons</span>
            </a>
            <div class="nav-section-title">หน้าร้าน</div>
            <a href="showcase.php" class="nav-link">
                <span class="nav-icon">🛍️</span>
                <span>Showcase</span>
            </a>
            <div class="nav-section-title">บัญชี</div>
            <a href="logout.php" class="nav-link">
                <span class="nav-icon">🚪</span>
                <span>ออกจากระบบ</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            GamePro Inventory v1.0
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;">
            <div>
                <h1 class="page-title">📦 Products</h1>
                <p class="page-subtitle">จัดการสินค้าทั้งหมด (<?= count($products) ?> รายการ)</p>
            </div>
            <button class="btn btn-primary" onclick="openAddModal()">+ เพิ่มสินค้า</button>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= $messageType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- Products Table -->
        <div class="card">
            <div class="table-wrapper">
                <?php if (count($products) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อสินค้า</th>
                            <th>หมวดหมู่</th>
                            <th class="text-right">ราคา</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td><span class="badge badge-info">#<?= $p['id'] ?></span></td>
                            <td>
                                <strong><?= htmlspecialchars($p['name']) ?></strong>
                                <?php if ($p['description']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($p['description'], 0, 60, '...')) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['category_name']) ?></td>
                            <td class="text-right price">฿<?= number_format($p['price'], 2) ?></td>
                            <td class="text-center">
                                <?php if ($p['stock_quantity'] < 5): ?>
                                    <span class="badge badge-danger">⚠ <?= $p['stock_quantity'] ?></span>
                                <?php elseif ($p['stock_quantity'] < 10): ?>
                                    <span class="badge badge-warning"><?= $p['stock_quantity'] ?></span>
                                <?php else: ?>
                                    <span class="badge badge-success"><?= $p['stock_quantity'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="flex items-center gap-1" style="justify-content:center;">
                                    <button class="btn btn-sm btn-secondary" onclick='openEditModal(<?= json_encode($p) ?>)'>✏️ แก้ไข</button>
                                    <a href="products.php?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('ต้องการลบสินค้า \'<?= htmlspecialchars(addslashes($p['name'])) ?>\' หรือไม่?')">🗑️ ลบ</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <p>ยังไม่มีสินค้า — เพิ่มสินค้าใหม่เลย!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Add Product Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">➕ เพิ่มสินค้าใหม่</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">ชื่อสินค้า *</label>
                    <input type="text" name="name" class="form-control" required placeholder="เช่น NVIDIA RTX 4090">
                </div>
                <div class="form-group">
                    <label class="form-label">หมวดหมู่ *</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">รายละเอียด</label>
                    <textarea name="description" class="form-control" placeholder="รายละเอียดสินค้า (ไม่บังคับ)"></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">ราคา (฿) *</label>
                        <input type="number" name="price" class="form-control" required min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">จำนวน Stock *</label>
                        <input type="number" name="stock_quantity" class="form-control" required min="0" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">🖼️ รูปสินค้า</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">💾 บันทึก</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">✏️ แก้ไขสินค้า</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">ชื่อสินค้า *</label>
                    <input type="text" name="name" id="edit-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">หมวดหมู่ *</label>
                    <select name="category_id" id="edit-category" class="form-control" required>
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">รายละเอียด</label>
                    <textarea name="description" id="edit-description" class="form-control"></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">ราคา (฿) *</label>
                        <input type="number" name="price" id="edit-price" class="form-control" required min="0" step="0.01">
                    </div>
                    <div class="form-group">
                        <label class="form-label">จำนวน Stock *</label>
                        <input type="number" name="stock_quantity" id="edit-stock" class="form-control" required min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">🖼️ รูปสินค้า (เปลี่ยนรูปใหม่)</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-success">💾 อัปเดต</button>
            </div>
        </form>
    </div>
</div>

<button class="mobile-menu-btn" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>

<script>
function openAddModal() {
    document.getElementById('addModal').classList.add('active');
}

function openEditModal(product) {
    document.getElementById('edit-id').value = product.id;
    document.getElementById('edit-name').value = product.name;
    document.getElementById('edit-category').value = product.category_id;
    document.getElementById('edit-description').value = product.description || '';
    document.getElementById('edit-price').value = product.price;
    document.getElementById('edit-stock').value = product.stock_quantity;
    document.getElementById('editModal').classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    }
});
</script>
<a href="javascript:history.back()" class="btn-back-float" title="ย้อนกลับ">←</a>
</body>
</html>
