<?php session_start(); ?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🛒 ตะกร้าสินค้า — GamePro</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .showcase-body { min-height: 100vh; background: var(--bg-primary); }
        .top-nav { background: var(--bg-secondary); border-bottom: 1px solid var(--border); padding: 0 2rem; display: flex; align-items: center; justify-content: space-between; height: 64px; position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow); }
        .top-nav-logo { display: flex; align-items: center; gap: 0.6rem; font-weight: 800; font-size: 1.2rem; color: var(--text-primary); text-decoration: none; }
        .top-nav-logo .logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, #1a1a1a, #444); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
        .top-nav-links { display: flex; align-items: center; gap: 0.5rem; }
        .top-nav-links a { color: var(--text-secondary); font-size: 0.85rem; font-weight: 500; padding: 0.4rem 0.85rem; border-radius: var(--radius-sm); transition: var(--transition); }
        .top-nav-links a:hover { color: var(--text-primary); background: var(--bg-tertiary); }
        .theme-toggle { width: 36px; height: 36px; background: var(--bg-tertiary); border: 1px solid var(--border); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 1rem; transition: var(--transition); }
        .theme-toggle:hover { background: var(--bg-primary); transform: scale(1.1); }
        [data-theme='dark'] .top-nav-logo .logo-icon { background: linear-gradient(135deg, #333, #555); }
        .cart-badge { background: #dc2626; color: #fff; font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 100px; margin-left: -0.3rem; }

        .cart-container { max-width: 900px; margin: 0 auto; padding: 2rem; }
        .cart-title { font-size: 1.5rem; font-weight: 800; color: var(--text-primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }

        .cart-empty { text-align: center; padding: 4rem 2rem; }
        .cart-empty .empty-icon { font-size: 4rem; margin-bottom: 1rem; }
        .cart-empty p { color: var(--text-muted); font-size: 1rem; margin-bottom: 1.5rem; }
        .cart-empty a { display: inline-block; padding: 0.7rem 1.5rem; background: #1a1a1a; color: #fff; border-radius: var(--radius-sm); font-weight: 600; font-size: 0.9rem; transition: var(--transition); }
        [data-theme='dark'] .cart-empty a { background: #f0f0f0; color: #1a1a1a; }
        .cart-empty a:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }

        .cart-item { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 0.75rem; transition: var(--transition); }
        .cart-item:hover { box-shadow: var(--shadow); }
        .cart-item-img { width: 80px; height: 80px; background: #fff; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
        [data-theme='dark'] .cart-item-img { background: #1a1a1a; }
        .cart-item-img img { max-width: 100%; max-height: 100%; object-fit: contain; padding: 0.25rem; }
        .cart-item-info { flex: 1; min-width: 0; }
        .cart-item-name { font-weight: 700; font-size: 0.9rem; color: var(--text-primary); margin-bottom: 0.15rem; }
        .cart-item-name a { color: var(--text-primary); }
        .cart-item-cat { font-size: 0.72rem; color: var(--text-muted); }
        .cart-item-price { font-size: 1rem; font-weight: 800; color: var(--text-primary); white-space: nowrap; }
        .cart-item-actions { display: flex; align-items: center; gap: 0.5rem; }
        .qty-btn { width: 28px; height: 28px; border: 1px solid var(--border); background: var(--bg-tertiary); color: var(--text-primary); border-radius: 4px; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: var(--transition); }
        .qty-btn:hover { background: var(--bg-primary); }
        .qty-value { font-size: 0.9rem; font-weight: 700; min-width: 24px; text-align: center; color: var(--text-primary); }
        .remove-btn { width: 28px; height: 28px; border: none; background: var(--danger-bg); color: var(--danger); border-radius: 4px; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: var(--transition); margin-left: 0.5rem; }
        .remove-btn:hover { background: var(--danger); color: #fff; }

        .cart-summary { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem; margin-top: 1rem; }
        .cart-summary-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-secondary); }
        .cart-summary-total { display: flex; justify-content: space-between; align-items: center; padding-top: 0.75rem; border-top: 2px solid var(--border); font-size: 1.3rem; font-weight: 800; color: var(--text-primary); }
        .cart-actions { display: flex; gap: 0.75rem; margin-top: 1rem; }
        .btn-checkout { flex: 1; padding: 0.8rem; background: #16a34a; color: #fff; border: none; border-radius: var(--radius-sm); font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: var(--transition); }
        .btn-checkout:hover { background: #15803d; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(22,163,74,0.3); }
        .btn-clear { padding: 0.8rem 1.2rem; background: var(--bg-tertiary); color: var(--text-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: var(--transition); }
        .btn-clear:hover { background: var(--danger-bg); color: var(--danger); border-color: var(--danger); }
        .btn-continue { display: inline-block; margin-top: 0.75rem; color: var(--text-muted); font-size: 0.82rem; font-weight: 500; }

        .toast { position: fixed; top: 80px; right: 1rem; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0.8rem 1.2rem; box-shadow: var(--shadow-lg); z-index: 999; font-size: 0.85rem; font-weight: 600; color: var(--text-primary); transform: translateX(120%); transition: transform 0.3s ease; }
        .toast.show { transform: translateX(0); }
        .toast.success { border-left: 3px solid #16a34a; }

        /* Coupon */
        .coupon-row { display: flex; gap: 0.5rem; margin-bottom: 0.75rem; }
        .coupon-input { flex: 1; padding: 0.55rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-primary); color: var(--text-primary); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        .coupon-input::placeholder { text-transform: none; letter-spacing: normal; font-weight: 400; }
        .btn-coupon { padding: 0.55rem 1rem; background: #7c3aed; color: #fff; border: none; border-radius: var(--radius-sm); font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: var(--transition); white-space: nowrap; }
        .btn-coupon:hover { background: #6d28d9; }
        .coupon-msg { font-size: 0.78rem; margin-bottom: 0.5rem; font-weight: 600; }
        .coupon-msg.ok { color: #16a34a; }
        .coupon-msg.err { color: #dc2626; }
        .discount-row { color: #16a34a; font-weight: 700; }

        @media (max-width: 768px) {
            .cart-container { padding: 1rem; }
            .cart-item { flex-wrap: wrap; }
            .cart-item-img { width: 60px; height: 60px; }
            .cart-actions { flex-direction: column; }
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
        <a href="login.php">🔐 เข้าสู่ระบบ</a>
        <button class="theme-toggle" onclick="toggleTheme()" title="สลับธีม">🌙</button>
    </div>
</nav>

<div class="cart-container">
    <h1 class="cart-title">🛒 ตะกร้าสินค้า</h1>
    <div id="cartContent">
        <div class="cart-empty">
            <div class="empty-icon">🛒</div>
            <p>กำลังโหลด...</p>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
// Dark Mode
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

async function cartAction(action, productId = 0, qty = 1) {
    const form = new FormData();
    form.append('action', action);
    form.append('product_id', productId);
    form.append('qty', qty);
    const res = await fetch('cart_api.php', { method: 'POST', body: form });
    return await res.json();
}

async function loadCart() {
    const data = await cartAction('get');
    document.getElementById('cartBadge').textContent = data.count || 0;
    
    if (!data.items || data.items.length === 0) {
        document.getElementById('cartContent').innerHTML = `
            <div class="cart-empty">
                <div class="empty-icon">🛒</div>
                <p>ตะกร้าว่างเปล่า</p>
                <a href="showcase.php">🏠 เลือกซื้อสินค้า</a>
            </div>`;
        return;
    }

    let html = '';
    data.items.forEach(item => {
        html += `
        <div class="cart-item" id="item-${item.id}">
            <div class="cart-item-img">
                ${item.image_url ? `<img src="${item.image_url}" alt="${item.name}">` : '<span style="font-size:2rem">📦</span>'}
            </div>
            <div class="cart-item-info">
                <div class="cart-item-name"><a href="product_detail.php?id=${item.id}">${item.name}</a></div>
                <div class="cart-item-cat">${item.category}</div>
            </div>
            <div class="cart-item-actions">
                <button class="qty-btn" onclick="updateQty(${item.id}, ${item.qty - 1})">−</button>
                <span class="qty-value">${item.qty}</span>
                <button class="qty-btn" onclick="updateQty(${item.id}, ${item.qty + 1})">+</button>
                <button class="remove-btn" onclick="removeItem(${item.id})" title="ลบ">🗑</button>
            </div>
            <div class="cart-item-price">฿${item.subtotal.toLocaleString('th-TH', {minimumFractionDigits:2})}</div>
        </div>`;
    });

    html += `
    <div class="cart-summary">
        <div class="coupon-row">
            <input type="text" id="couponInput" class="coupon-input" placeholder="🎟️ ใส่โค้ดคูปอง" maxlength="20">
            <button class="btn-coupon" onclick="applyCoupon()">ใช้คูปอง</button>
        </div>
        <div id="couponMsg"></div>
        <div class="cart-summary-row">
            <span>สินค้า ${data.items.length} รายการ (${data.count} ชิ้น)</span>
            <span>฿${data.total.toLocaleString('th-TH', {minimumFractionDigits:2})}</span>
        </div>
        <div id="discountRow"></div>
        <div class="cart-summary-total">
            <span>รวมทั้งหมด</span>
            <span id="finalTotal">฿${data.total.toLocaleString('th-TH', {minimumFractionDigits:2})}</span>
        </div>
        <div class="cart-actions">
            <button class="btn-checkout" onclick="checkout()">✅ สั่งซื้อสินค้า</button>
            <button class="btn-clear" onclick="clearCart()">🗑 ล้างตะกร้า</button>
        </div>
        <a href="showcase.php" class="btn-continue">← เลือกซื้อสินค้าเพิ่ม</a>
    </div>`;

    document.getElementById('cartContent').innerHTML = html;
}

async function updateQty(id, qty) {
    if (qty <= 0) return removeItem(id);
    await cartAction('update', id, qty);
    loadCart();
}

async function removeItem(id) {
    await cartAction('remove', id);
    showToast('ลบสินค้าออกแล้ว');
    loadCart();
}

async function clearCart() {
    if (!confirm('ต้องการล้างตะกร้าทั้งหมด?')) return;
    await cartAction('clear');
    showToast('ล้างตะกร้าแล้ว');
    loadCart();
}

let activeCoupon = null;

async function applyCoupon() {
    const code = document.getElementById('couponInput').value.trim();
    if (!code) return;
    const form = new FormData();
    form.append('action', 'validate_coupon');
    form.append('code', code);
    const res = await fetch('cart_api.php', { method: 'POST', body: form });
    const data = await res.json();
    const msgEl = document.getElementById('couponMsg');
    const discountEl = document.getElementById('discountRow');
    const totalEl = document.getElementById('finalTotal');
    if (data.ok) {
        activeCoupon = data.code;
        msgEl.innerHTML = `<div class="coupon-msg ok">✅ ใช้คูปอง ${data.code} สำเร็จ! ลด ${data.label}</div>`;
        discountEl.innerHTML = `<div class="cart-summary-row discount-row"><span>🎟️ ส่วนลดคูปอง (${data.label})</span><span>-฿${data.discount.toLocaleString('th-TH',{minimumFractionDigits:2})}</span></div>`;
        totalEl.textContent = `฿${data.new_total.toLocaleString('th-TH',{minimumFractionDigits:2})}`;
        showToast('🎟️ ใช้คูปองสำเร็จ!');
    } else {
        activeCoupon = null;
        msgEl.innerHTML = `<div class="coupon-msg err">❌ ${data.msg}</div>`;
        discountEl.innerHTML = '';
    }
}

async function checkout() {
    const form = new FormData();
    form.append('action', 'checkout');
    if (activeCoupon) form.append('coupon_code', activeCoupon);
    const res = await fetch('cart_api.php', { method: 'POST', body: form });
    const data = await res.json();
    if (data.ok) {
        let discountLine = '';
        if (data.discount > 0) {
            discountLine = `<p style="color:#16a34a;margin-bottom:0.3rem">ส่วนลด: -฿${Number(data.discount).toLocaleString('th-TH',{minimumFractionDigits:2})}</p>`;
        }
        document.getElementById('cartContent').innerHTML = `
            <div class="cart-empty" style="padding:3rem">
                <div class="empty-icon">✅</div>
                <p style="font-size:1.2rem;font-weight:700;color:var(--text-primary);margin-bottom:0.5rem">สั่งซื้อสำเร็จ!</p>
                <p style="color:var(--text-secondary);margin-bottom:0.3rem">หมายเลขคำสั่งซื้อ: <strong>${data.order_number}</strong></p>
                ${discountLine}
                <p style="color:var(--text-muted);margin-bottom:1.5rem">ยอดรวม: ฿${Number(data.total).toLocaleString('th-TH',{minimumFractionDigits:2})}</p>
                <div style="display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap">
                    <a href="orders.php" style="padding:0.7rem 1.5rem;background:#1a1a1a;color:#fff;border-radius:8px;font-weight:600;font-size:0.9rem">📋 ดูประวัติคำสั่งซื้อ</a>
                    <a href="showcase.php" style="padding:0.7rem 1.5rem;background:var(--bg-tertiary);color:var(--text-primary);border-radius:8px;font-weight:600;font-size:0.9rem;border:1px solid var(--border)">🏠 กลับหน้าแรก</a>
                </div>
            </div>`;
        document.getElementById('cartBadge').textContent = '0';
    } else {
        showToast(data.msg || 'เกิดข้อผิดพลาด');
    }
}

loadCart();
</script>

</body>
</html>
