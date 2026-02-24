<?php
session_start();
require_once 'db.php';

// Check if cart has items
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// Get user info if logged in
$user = null;
$userPoints = 0;
$userTier = 'bronze';
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $userPoints = (int)($user['loyalty_points'] ?? 0);
        $userTier = $user['loyalty_tier'] ?? 'bronze';
    }
}

// Get cart items
$cartItems = [];
$cartTotal = 0;
foreach ($_SESSION['cart'] as $pid => $q) {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stmt->execute([$pid]);
    $p = $stmt->fetch();
    if ($p) {
        $subtotal = $p['price'] * $q;
        $cartTotal += $subtotal;
        $cartItems[] = [
            'id' => $p['id'],
            'name' => $p['name'],
            'price' => (float)$p['price'],
            'qty' => $q,
            'subtotal' => $subtotal,
            'image_url' => $p['image_url'] ?? '',
            'category' => $p['category_name'],
        ];
    }
}
$cartCount = array_sum($_SESSION['cart']);

// Tier config
$tierLabels = [
    'bronze' => '🥉 Bronze', 'silver' => '🥈 Silver',
    'gold' => '🥇 Gold', 'diamond' => '💎 Diamond'
];
$tierRates = [
    'bronze' => 1.0, 'silver' => 1.5, 'gold' => 2.0, 'diamond' => 3.0
];
$earnRate = $tierRates[$userTier] ?? 1.0;
$estimatedPoints = (int)floor($cartTotal / 100 * $earnRate);
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>💳 ชำระเงิน — GamePro</title>
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

        .checkout-container { max-width: 1100px; margin: 0 auto; padding: 2rem; }
        .checkout-title { font-size: 1.5rem; font-weight: 800; color: var(--text-primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .checkout-grid { display: grid; grid-template-columns: 1fr 400px; gap: 1.5rem; align-items: start; }

        /* Left - Form */
        .checkout-form { display: flex; flex-direction: column; gap: 1.25rem; }
        .form-section { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; }
        .form-section h3 { font-size: 1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.4rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem; }
        .form-row.full { grid-template-columns: 1fr; }
        .form-group { display: flex; flex-direction: column; gap: 0.3rem; }
        .form-group label { font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); }
        .form-group input, .form-group textarea { padding: 0.65rem 0.85rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-primary); color: var(--text-primary); font-size: 0.88rem; outline: none; transition: var(--transition); font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.1); }
        .form-group textarea { resize: vertical; min-height: 80px; }

        /* Payment Methods */
        .payment-options { display: flex; flex-direction: column; gap: 0.5rem; }
        .payment-option { display: flex; align-items: center; gap: 0.75rem; padding: 0.85rem 1rem; border: 2px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; transition: var(--transition); }
        .payment-option:hover { border-color: #999; }
        .payment-option.active { border-color: #7c3aed; background: rgba(124,58,237,0.05); }
        .payment-option input[type="radio"] { display: none; }
        .payment-radio { width: 20px; height: 20px; border: 2px solid var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: var(--transition); }
        .payment-option.active .payment-radio { border-color: #7c3aed; }
        .payment-option.active .payment-radio::after { content: ''; width: 10px; height: 10px; background: #7c3aed; border-radius: 50%; }
        .payment-icon { font-size: 1.5rem; }
        .payment-info { flex: 1; }
        .payment-info .payment-name { font-weight: 700; font-size: 0.9rem; color: var(--text-primary); }
        .payment-info .payment-desc { font-size: 0.75rem; color: var(--text-muted); }

        /* Coupon */
        .coupon-row { display: flex; gap: 0.5rem; }
        .coupon-input { flex: 1; padding: 0.6rem 0.8rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-primary); color: var(--text-primary); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        .coupon-input::placeholder { text-transform: none; letter-spacing: normal; font-weight: 400; }
        .btn-coupon { padding: 0.6rem 1rem; background: #7c3aed; color: #fff; border: none; border-radius: var(--radius-sm); font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: var(--transition); white-space: nowrap; }
        .btn-coupon:hover { background: #6d28d9; }
        .coupon-msg { font-size: 0.78rem; margin-top: 0.4rem; font-weight: 600; }
        .coupon-msg.ok { color: #16a34a; }
        .coupon-msg.err { color: #dc2626; }

        /* Loyalty Points */
        .points-box { background: linear-gradient(135deg, rgba(124,58,237,0.08), rgba(59,130,246,0.08)); border: 1px solid rgba(124,58,237,0.2); border-radius: var(--radius-sm); padding: 1rem; }
        .points-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem; }
        .points-header .tier-badge { font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 100px; }
        .points-available { font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 0.5rem; }
        .points-available strong { color: var(--text-primary); font-size: 1.1rem; }
        .points-input-row { display: flex; gap: 0.5rem; align-items: center; }
        .points-input { width: 100px; padding: 0.5rem 0.7rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-primary); color: var(--text-primary); font-size: 0.88rem; font-weight: 700; text-align: center; }
        .points-input-row span { font-size: 0.8rem; color: var(--text-muted); }
        .btn-use-points { padding: 0.5rem 0.8rem; background: #f59e0b; color: #fff; border: none; border-radius: var(--radius-sm); font-size: 0.8rem; font-weight: 700; cursor: pointer; }
        .btn-use-points:hover { background: #d97706; }
        .btn-use-all { padding: 0.5rem 0.6rem; background: var(--bg-tertiary); color: var(--text-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.72rem; font-weight: 600; cursor: pointer; }
        .points-msg { font-size: 0.78rem; margin-top: 0.4rem; font-weight: 600; color: #16a34a; }

        /* Right - Summary */
        .checkout-summary { position: sticky; top: 80px; }
        .summary-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem; }
        .summary-card h3 { font-size: 1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; }
        .summary-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0; border-bottom: 1px solid var(--border); }
        .summary-item:last-of-type { border-bottom: none; }
        .summary-item-img { width: 50px; height: 50px; background: #fff; border-radius: 6px; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
        [data-theme='dark'] .summary-item-img { background: #1a1a1a; }
        .summary-item-img img { max-width: 100%; max-height: 100%; object-fit: contain; padding: 0.15rem; }
        .summary-item-info { flex: 1; min-width: 0; }
        .summary-item-name { font-size: 0.8rem; font-weight: 600; color: var(--text-primary); line-height: 1.3; }
        .summary-item-qty { font-size: 0.7rem; color: var(--text-muted); }
        .summary-item-price { font-size: 0.85rem; font-weight: 800; color: var(--text-primary); white-space: nowrap; }

        .summary-divider { border: none; border-top: 1px solid var(--border); margin: 0.75rem 0; }
        .summary-row { display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.4rem; }
        .summary-row.discount { color: #16a34a; font-weight: 700; }
        .summary-row.earn { color: #7c3aed; font-weight: 600; font-size: 0.78rem; }
        .summary-total { display: flex; justify-content: space-between; font-size: 1.3rem; font-weight: 800; color: var(--text-primary); padding-top: 0.75rem; border-top: 2px solid var(--border); margin-top: 0.5rem; }

        .btn-place-order { width: 100%; padding: 0.9rem; background: linear-gradient(135deg, #16a34a, #15803d); color: #fff; border: none; border-radius: var(--radius-sm); font-size: 1rem; font-weight: 700; cursor: pointer; transition: var(--transition); margin-top: 1rem; letter-spacing: 0.02em; }
        .btn-place-order:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(22,163,74,0.35); }
        .btn-place-order:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }
        .btn-back-cart { display: block; text-align: center; margin-top: 0.75rem; color: var(--text-muted); font-size: 0.82rem; font-weight: 500; text-decoration: none; }
        .btn-back-cart:hover { color: var(--text-primary); }

        /* Success overlay */
        .success-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .success-overlay.show { display: flex; }
        .success-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 2.5rem; max-width: 480px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: popIn 0.4s ease; }
        @keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .success-card .icon { font-size: 4rem; margin-bottom: 1rem; }
        .success-card h2 { font-size: 1.3rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.5rem; }
        .success-card .order-num { font-size: 1.1rem; font-weight: 700; color: #7c3aed; margin-bottom: 0.3rem; }
        .success-card .details { font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem; }
        .success-card .points-earned { display: inline-flex; align-items: center; gap: 0.3rem; background: rgba(124,58,237,0.1); color: #7c3aed; padding: 0.4rem 1rem; border-radius: 100px; font-size: 0.85rem; font-weight: 700; margin-bottom: 1.5rem; }
        .success-links { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
        .success-links a { padding: 0.7rem 1.5rem; border-radius: var(--radius-sm); font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: var(--transition); }
        .success-links .primary { background: #1a1a1a; color: #fff; }
        [data-theme='dark'] .success-links .primary { background: #f0f0f0; color: #1a1a1a; }
        .success-links .secondary { background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--border); }

        .toast { position: fixed; top: 80px; right: 1rem; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0.8rem 1.2rem; box-shadow: var(--shadow-lg); z-index: 999; font-size: 0.85rem; font-weight: 600; color: var(--text-primary); transform: translateX(120%); transition: transform 0.3s ease; }
        .toast.show { transform: translateX(0); }
        .toast.success { border-left: 3px solid #16a34a; }
        .toast.error { border-left: 3px solid #dc2626; }

        @media (max-width: 768px) {
            .checkout-container { padding: 1rem; }
            .checkout-grid { grid-template-columns: 1fr; }
            .checkout-summary { position: static; }
            .form-row { grid-template-columns: 1fr; }
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
        <a href="cart.php">🛒 ตะกร้า</a>
        <?php if ($user): ?>
            <a href="profile.php">👤 <?= htmlspecialchars($user['username']) ?></a>
        <?php else: ?>
            <a href="login.php">🔐 เข้าสู่ระบบ</a>
        <?php endif; ?>
        <button class="theme-toggle" onclick="toggleTheme()" title="สลับธีม">🌙</button>
    </div>
</nav>

<div class="checkout-container">
    <h1 class="checkout-title">💳 ชำระเงิน</h1>
    
    <div class="checkout-grid">
        <!-- Left: Form -->
        <div class="checkout-form">
            <!-- Shipping Info -->
            <div class="form-section">
                <h3>📦 ข้อมูลจัดส่ง</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>ชื่อ-นามสกุล *</label>
                        <input type="text" id="shipName" required placeholder="ชื่อผู้รับสินค้า" value="<?= htmlspecialchars($user['username'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>เบอร์โทรศัพท์ *</label>
                        <input type="tel" id="shipPhone" required placeholder="0xx-xxx-xxxx" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>ที่อยู่จัดส่ง *</label>
                        <textarea id="shipAddress" required placeholder="บ้านเลขที่, ถนน, แขวง/ตำบล, เขต/อำเภอ, จังหวัด, รหัสไปรษณีย์"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="form-section">
                <h3>💰 วิธีชำระเงิน</h3>
                <div class="payment-options">
                    <div class="payment-option active" onclick="selectPayment('transfer', this)">
                        <input type="radio" name="payment" value="transfer" checked>
                        <div class="payment-radio"></div>
                        <div class="payment-icon">🏦</div>
                        <div class="payment-info">
                            <div class="payment-name">โอนเงินผ่านธนาคาร</div>
                            <div class="payment-desc">ธนาคารกสิกรไทย, ไทยพาณิชย์, กรุงเทพ</div>
                        </div>
                    </div>
                    <div class="payment-option" onclick="selectPayment('promptpay', this)">
                        <input type="radio" name="payment" value="promptpay">
                        <div class="payment-radio"></div>
                        <div class="payment-icon">📱</div>
                        <div class="payment-info">
                            <div class="payment-name">PromptPay QR Code</div>
                            <div class="payment-desc">สแกนจ่ายผ่าน Mobile Banking ทุกธนาคาร</div>
                        </div>
                    </div>
                    <div class="payment-option" onclick="selectPayment('cod', this)">
                        <input type="radio" name="payment" value="cod">
                        <div class="payment-radio"></div>
                        <div class="payment-icon">💵</div>
                        <div class="payment-info">
                            <div class="payment-name">เก็บเงินปลายทาง (COD)</div>
                            <div class="payment-desc">ชำระเงินเมื่อได้รับสินค้า (+฿50)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coupon -->
            <div class="form-section">
                <h3>🎟️ คูปองส่วนลด</h3>
                <div class="coupon-row">
                    <input type="text" id="couponInput" class="coupon-input" placeholder="ใส่โค้ดคูปอง" maxlength="20">
                    <button class="btn-coupon" onclick="applyCoupon()">ใช้คูปอง</button>
                </div>
                <div id="couponMsg"></div>
            </div>

            <!-- Loyalty Points -->
            <?php if ($user && $userPoints > 0): ?>
            <div class="form-section">
                <h3>🎮 ใช้แต้มสะสม</h3>
                <div class="points-box">
                    <div class="points-header">
                        <span style="font-weight:700;color:var(--text-primary)">แต้มสะสม</span>
                        <span class="tier-badge" style="background:<?php
                            $tierColors = ['bronze'=>'rgba(205,127,50,0.15);color:#cd7f32','silver'=>'rgba(192,192,192,0.2);color:#888','gold'=>'rgba(255,215,0,0.15);color:#b8860b','diamond'=>'rgba(185,242,255,0.2);color:#0ea5e9'];
                            echo $tierColors[$userTier] ?? $tierColors['bronze'];
                        ?>"><?= $tierLabels[$userTier] ?></span>
                    </div>
                    <div class="points-available">คุณมี <strong><?= number_format($userPoints) ?></strong> แต้ม (= ฿<?= number_format($userPoints) ?>)</div>
                    <div class="points-input-row">
                        <input type="number" id="pointsInput" class="points-input" min="0" max="<?= $userPoints ?>" value="0" placeholder="0">
                        <span>แต้ม</span>
                        <button class="btn-use-all" onclick="document.getElementById('pointsInput').value=<?= $userPoints ?>;updatePointsDiscount()">ใช้ทั้งหมด</button>
                        <button class="btn-use-points" onclick="updatePointsDiscount()">คำนวณ</button>
                    </div>
                    <div id="pointsMsg"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right: Summary -->
        <div class="checkout-summary">
            <div class="summary-card">
                <h3>📋 สรุปคำสั่งซื้อ</h3>
                <?php foreach ($cartItems as $item): ?>
                <div class="summary-item">
                    <div class="summary-item-img">
                        <?php if ($item['image_url']): ?>
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                        <?php else: ?>
                            <span style="font-size:1.5rem">📦</span>
                        <?php endif; ?>
                    </div>
                    <div class="summary-item-info">
                        <div class="summary-item-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="summary-item-qty"><?= $item['qty'] ?> × ฿<?= number_format($item['price'], 2) ?></div>
                    </div>
                    <div class="summary-item-price">฿<?= number_format($item['subtotal'], 2) ?></div>
                </div>
                <?php endforeach; ?>

                <hr class="summary-divider">
                <div class="summary-row">
                    <span>สินค้า <?= count($cartItems) ?> รายการ (<?= $cartCount ?> ชิ้น)</span>
                    <span>฿<?= number_format($cartTotal, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>ค่าจัดส่ง</span>
                    <span id="shippingCost" style="color:#16a34a;font-weight:600">ฟรี</span>
                </div>
                <div id="codFeeRow" style="display:none" class="summary-row">
                    <span>ค่าธรรมเนียม COD</span>
                    <span>+฿50.00</span>
                </div>
                <div id="couponDiscountRow" style="display:none" class="summary-row discount">
                    <span id="couponDiscountLabel">ส่วนลดคูปอง</span>
                    <span id="couponDiscountAmount"></span>
                </div>
                <div id="pointsDiscountRow" style="display:none" class="summary-row discount">
                    <span>ส่วนลดแต้มสะสม</span>
                    <span id="pointsDiscountAmount"></span>
                </div>

                <div class="summary-total">
                    <span>รวมทั้งหมด</span>
                    <span id="grandTotal">฿<?= number_format($cartTotal, 2) ?></span>
                </div>

                <?php if ($user): ?>
                <div class="summary-row earn" style="margin-top:0.5rem">
                    <span>⭐ จะได้รับ</span>
                    <span id="earnPoints">+<?= $estimatedPoints ?> แต้ม</span>
                </div>
                <?php endif; ?>

                <button class="btn-place-order" id="btnPlaceOrder" onclick="placeOrder()">✅ ยืนยันสั่งซื้อ</button>
                <a href="cart.php" class="btn-back-cart">← กลับไปแก้ไขตะกร้า</a>
            </div>
        </div>
    </div>
</div>

<!-- Success Overlay -->
<div class="success-overlay" id="successOverlay">
    <div class="success-card">
        <div class="icon">🎉</div>
        <h2>สั่งซื้อสำเร็จ!</h2>
        <div class="order-num" id="successOrderNum"></div>
        <div class="details" id="successDetails"></div>
        <div class="points-earned" id="successPoints" style="display:none">⭐ <span id="successPointsText"></span></div>
        <div class="success-links">
            <a href="profile.php" class="primary">👤 ดูโปรไฟล์</a>
            <a href="showcase.php" class="secondary">🏠 กลับหน้าแรก</a>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
const cartTotal = <?= $cartTotal ?>;
let selectedPayment = 'transfer';
let couponDiscount = 0;
let couponCode = '';
let pointsDiscount = 0;
let codFee = 0;

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

function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = `toast show ${type}`;
    setTimeout(() => t.className = 'toast', 2500);
}

function selectPayment(method, el) {
    document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('active'));
    el.classList.add('active');
    el.querySelector('input').checked = true;
    selectedPayment = method;
    codFee = method === 'cod' ? 50 : 0;
    document.getElementById('codFeeRow').style.display = method === 'cod' ? 'flex' : 'none';
    updateTotal();
}

function updateTotal() {
    const total = cartTotal + codFee - couponDiscount - pointsDiscount;
    document.getElementById('grandTotal').textContent = '฿' + Math.max(0, total).toLocaleString('th-TH', {minimumFractionDigits: 2});
}

async function applyCoupon() {
    const code = document.getElementById('couponInput').value.trim();
    if (!code) return;
    const form = new FormData();
    form.append('action', 'validate_coupon');
    form.append('code', code);
    const res = await fetch('cart_api.php', { method: 'POST', body: form });
    const data = await res.json();
    const msgEl = document.getElementById('couponMsg');
    if (data.ok) {
        couponCode = data.code;
        couponDiscount = data.discount;
        msgEl.innerHTML = `<div class="coupon-msg ok">✅ ใช้คูปอง ${data.code} สำเร็จ! ลด ${data.label}</div>`;
        document.getElementById('couponDiscountRow').style.display = 'flex';
        document.getElementById('couponDiscountLabel').textContent = '🎟️ คูปอง (' + data.label + ')';
        document.getElementById('couponDiscountAmount').textContent = '-฿' + data.discount.toLocaleString('th-TH', {minimumFractionDigits:2});
        showToast('🎟️ ใช้คูปองสำเร็จ!');
        updateTotal();
    } else {
        couponCode = '';
        couponDiscount = 0;
        msgEl.innerHTML = `<div class="coupon-msg err">❌ ${data.msg}</div>`;
        document.getElementById('couponDiscountRow').style.display = 'none';
        updateTotal();
    }
}

function updatePointsDiscount() {
    const input = document.getElementById('pointsInput');
    const points = parseInt(input.value) || 0;
    const maxPoints = <?= $userPoints ?>;
    const msgEl = document.getElementById('pointsMsg');
    
    if (points <= 0) {
        pointsDiscount = 0;
        document.getElementById('pointsDiscountRow').style.display = 'none';
        msgEl.innerHTML = '';
        updateTotal();
        return;
    }
    if (points > maxPoints) {
        input.value = maxPoints;
        msgEl.innerHTML = '<div style="color:#dc2626;font-size:0.78rem;font-weight:600">แต้มไม่เพียงพอ</div>';
        return;
    }
    // Max discount = remaining total
    const maxDiscount = cartTotal + codFee - couponDiscount;
    const actualPoints = Math.min(points, maxDiscount);
    pointsDiscount = actualPoints;
    input.value = actualPoints;
    
    document.getElementById('pointsDiscountRow').style.display = 'flex';
    document.getElementById('pointsDiscountAmount').textContent = '-฿' + actualPoints.toLocaleString('th-TH', {minimumFractionDigits:2});
    msgEl.innerHTML = `<div class="points-msg">🎮 ใช้ ${actualPoints} แต้ม ลด ฿${actualPoints.toLocaleString()}</div>`;
    updateTotal();
}

async function placeOrder() {
    const name = document.getElementById('shipName').value.trim();
    const phone = document.getElementById('shipPhone').value.trim();
    const address = document.getElementById('shipAddress').value.trim();
    
    if (!name || !phone || !address) {
        showToast('กรุณากรอกข้อมูลจัดส่งให้ครบ', 'error');
        return;
    }
    
    const btn = document.getElementById('btnPlaceOrder');
    btn.disabled = true;
    btn.textContent = '⏳ กำลังดำเนินการ...';
    
    const form = new FormData();
    form.append('action', 'full_checkout');
    form.append('shipping_name', name);
    form.append('shipping_phone', phone);
    form.append('shipping_address', address);
    form.append('payment_method', selectedPayment);
    if (couponCode) form.append('coupon_code', couponCode);
    form.append('points_used', pointsDiscount);
    form.append('cod_fee', codFee);
    
    try {
        const res = await fetch('cart_api.php', { method: 'POST', body: form });
        const data = await res.json();
        if (data.ok) {
            document.getElementById('successOrderNum').textContent = 'หมายเลขคำสั่งซื้อ: ' + data.order_number;
            document.getElementById('successDetails').textContent = 'ยอดรวม: ฿' + Number(data.total).toLocaleString('th-TH', {minimumFractionDigits:2});
            if (data.points_earned > 0) {
                document.getElementById('successPoints').style.display = 'inline-flex';
                document.getElementById('successPointsText').textContent = 'ได้รับ ' + data.points_earned + ' แต้มสะสม!';
            }
            document.getElementById('successOverlay').classList.add('show');
        } else {
            showToast(data.msg || 'เกิดข้อผิดพลาด', 'error');
            btn.disabled = false;
            btn.textContent = '✅ ยืนยันสั่งซื้อ';
        }
    } catch (e) {
        showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        btn.disabled = false;
        btn.textContent = '✅ ยืนยันสั่งซื้อ';
    }
}
</script>

<a href="cart.php" class="btn-back-float" title="กลับตะกร้า">←</a>
</body>
</html>
