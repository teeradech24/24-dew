<?php
require_once 'auth.php';
require_once 'db.php';

$message = '';
$messageType = '';

// Handle new sale submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_sale') {
    $productIds = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    if (count($productIds) > 0) {
        try {
            $pdo->beginTransaction();

            // Calculate total and validate stock
            $totalAmount = 0;
            $items = [];

            for ($i = 0; $i < count($productIds); $i++) {
                $pid = (int) $productIds[$i];
                $qty = (int) $quantities[$i];

                if ($pid <= 0 || $qty <= 0) continue;

                // Get product info & check stock
                $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id = ?");
                $stmt->execute([$pid]);
                $product = $stmt->fetch();

                if (!$product) {
                    throw new Exception("ไม่พบสินค้า ID: $pid");
                }
                if ($product['stock_quantity'] < $qty) {
                    throw new Exception("สินค้า '{$product['name']}' มี stock ไม่เพียงพอ (เหลือ {$product['stock_quantity']} ชิ้น)");
                }

                $items[] = [
                    'product_id' => $pid,
                    'quantity' => $qty,
                    'unit_price' => $product['price']
                ];
                $totalAmount += $product['price'] * $qty;
            }

            if (empty($items)) {
                throw new Exception('กรุณาเลือกสินค้าอย่างน้อย 1 รายการ');
            }

            // Create sale
            $stmt = $pdo->prepare("INSERT INTO sales (total_amount) VALUES (?)");
            $stmt->execute([$totalAmount]);
            $saleId = $pdo->lastInsertId();

            // Create sale items & deduct stock
            $stmtItem = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            $stmtStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");

            foreach ($items as $item) {
                $stmtItem->execute([$saleId, $item['product_id'], $item['quantity'], $item['unit_price']]);
                $stmtStock->execute([$item['quantity'], $item['product_id']]);
            }

            $pdo->commit();
            $message = "บันทึกการขาย #$saleId สำเร็จ — ยอดรวม ฿" . number_format($totalAmount, 2);
            $messageType = 'success';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    } else {
        $message = 'กรุณาเลือกสินค้าอย่างน้อย 1 รายการ';
        $messageType = 'danger';
    }
}

// Handle delete sale
if (isset($_GET['delete'])) {
    $saleId = (int) $_GET['delete'];
    try {
        $pdo->beginTransaction();

        // Restore stock from sale items
        $items = $pdo->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = ?");
        $items->execute([$saleId]);
        $stmtRestore = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
        foreach ($items->fetchAll() as $item) {
            $stmtRestore->execute([$item['quantity'], $item['product_id']]);
        }

        // Delete sale (cascade deletes sale_items)
        $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
        $stmt->execute([$saleId]);

        $pdo->commit();
        $message = 'ลบรายการขายและคืน stock เรียบร้อยแล้ว';
        $messageType = 'success';
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Fetch products for sale form
$products = $pdo->query("SELECT id, name, price, stock_quantity FROM products WHERE stock_quantity > 0 ORDER BY name")->fetchAll();

// Fetch all sales with details
$sales = $pdo->query("
    SELECT s.id, s.sale_date, s.total_amount,
           GROUP_CONCAT(CONCAT(p.name, ' x', si.quantity) SEPARATOR ', ') as items_summary,
           COUNT(si.id) as item_count
    FROM sales s
    LEFT JOIN sale_items si ON s.id = si.sale_id
    LEFT JOIN products p ON si.product_id = p.id
    GROUP BY s.id
    ORDER BY s.sale_date DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales — GamePro Inventory</title>
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
            <a href="products.php" class="nav-link">
                <span class="nav-icon">📦</span>
                <span>Products</span>
            </a>
            <a href="sales.php" class="nav-link active">
                <span class="nav-icon">💰</span>
                <span>Sales</span>
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
                <h1 class="page-title">💰 Sales</h1>
                <p class="page-subtitle">บันทึกและจัดการการขายสินค้า</p>
            </div>
            <button class="btn btn-primary" onclick="openSaleModal()">+ สร้างรายการขาย</button>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= $messageType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- Sales History -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">📋 ประวัติการขายทั้งหมด</h2>
                <span class="badge badge-info"><?= count($sales) ?> รายการ</span>
            </div>
            <div class="table-wrapper">
                <?php if (count($sales) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>วันที่</th>
                            <th>รายการสินค้า</th>
                            <th class="text-center">จำนวน</th>
                            <th class="text-right">ยอดรวม</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td><span class="badge badge-info">#<?= $sale['id'] ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($sale['sale_date'])) ?></td>
                            <td>
                                <small><?= htmlspecialchars($sale['items_summary'] ?? 'N/A') ?></small>
                            </td>
                            <td class="text-center"><?= $sale['item_count'] ?> รายการ</td>
                            <td class="text-right price">฿<?= number_format($sale['total_amount'], 2) ?></td>
                            <td class="text-center">
                                <a href="sales.php?delete=<?= $sale['id'] ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('ต้องการลบรายการขาย #<?= $sale['id'] ?> หรือไม่?\nstock จะถูกคืนให้สินค้า')">
                                    🗑️ ลบ
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">🛒</div>
                    <p>ยังไม่มีรายการขาย — สร้างรายการขายใหม่เลย!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- New Sale Modal -->
<div class="modal-overlay" id="saleModal">
    <div class="modal" style="max-width:650px;">
        <div class="modal-header">
            <h3 class="modal-title">🛒 สร้างรายการขายใหม่</h3>
            <button class="modal-close" onclick="closeModal('saleModal')">&times;</button>
        </div>
        <form method="POST" id="saleForm">
            <input type="hidden" name="action" value="create_sale">
            <div class="modal-body">
                <div id="saleItems">
                    <div class="sale-item-entry" data-index="0">
                        <div style="display:grid;grid-template-columns:1fr 100px 40px;gap:0.75rem;align-items:end;margin-bottom:0.75rem;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">สินค้า</label>
                                <select name="product_id[]" class="form-control product-select" required onchange="updateItemPrice(this)">
                                    <option value="">-- เลือกสินค้า --</option>
                                    <?php foreach ($products as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>" data-stock="<?= $p['stock_quantity'] ?>">
                                        <?= htmlspecialchars($p['name']) ?> (฿<?= number_format($p['price'], 2) ?>) [<?= $p['stock_quantity'] ?> ชิ้น]
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">จำนวน</label>
                                <input type="number" name="quantity[]" class="form-control qty-input" min="1" value="1" required onchange="updateTotal()">
                            </div>
                            <button type="button" class="btn btn-danger btn-icon" onclick="removeItem(this)" title="ลบ" style="margin-bottom:0;">×</button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="addItem()" style="margin-bottom:1rem;">+ เพิ่มสินค้า</button>

                <div class="sale-total">
                    <span>💰 ยอดรวม</span>
                    <span id="totalDisplay">฿0.00</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('saleModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-success">💾 บันทึกการขาย</button>
            </div>
        </form>
    </div>
</div>

<button class="mobile-menu-btn" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>

<script>
const productsData = <?= json_encode($products) ?>;

function openSaleModal() {
    document.getElementById('saleModal').classList.add('active');
    updateTotal();
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

function addItem() {
    const container = document.getElementById('saleItems');
    const index = container.children.length;
    const options = productsData.map(p =>
        `<option value="${p.id}" data-price="${p.price}" data-stock="${p.stock_quantity}">${escapeHtml(p.name)} (฿${Number(p.price).toLocaleString('th-TH', {minimumFractionDigits: 2})}) [${p.stock_quantity} ชิ้น]</option>`
    ).join('');

    const div = document.createElement('div');
    div.className = 'sale-item-entry';
    div.dataset.index = index;
    div.innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 100px 40px;gap:0.75rem;align-items:end;margin-bottom:0.75rem;">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">สินค้า</label>
                <select name="product_id[]" class="form-control product-select" required onchange="updateItemPrice(this)">
                    <option value="">-- เลือกสินค้า --</option>
                    ${options}
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">จำนวน</label>
                <input type="number" name="quantity[]" class="form-control qty-input" min="1" value="1" required onchange="updateTotal()">
            </div>
            <button type="button" class="btn btn-danger btn-icon" onclick="removeItem(this)" title="ลบ" style="margin-bottom:0;">×</button>
        </div>
    `;
    container.appendChild(div);
}

function removeItem(btn) {
    const entries = document.querySelectorAll('.sale-item-entry');
    if (entries.length <= 1) return;
    btn.closest('.sale-item-entry').remove();
    updateTotal();
}

function updateItemPrice(select) {
    updateTotal();
}

function updateTotal() {
    let total = 0;
    document.querySelectorAll('.sale-item-entry').forEach(entry => {
        const select = entry.querySelector('.product-select');
        const qtyInput = entry.querySelector('.qty-input');
        if (select.value) {
            const option = select.options[select.selectedIndex];
            const price = parseFloat(option.dataset.price) || 0;
            const qty = parseInt(qtyInput.value) || 0;
            total += price * qty;
        }
    });
    document.getElementById('totalDisplay').textContent = '฿' + total.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    }
});
</script>
</body>
</html>
