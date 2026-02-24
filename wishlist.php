<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>❤️ Wishlist — GamePro</title>
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
        .container { max-width: 1100px; margin: 0 auto; padding: 2rem; }
        .page-title { font-size: 1.5rem; font-weight: 800; color: var(--text-primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .wl-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
        .wl-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; transition: var(--transition); position: relative; }
        .wl-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
        .wl-img { height: 180px; background: #fff; display: flex; align-items: center; justify-content: center; overflow: hidden; border-bottom: 1px solid var(--border); }
        [data-theme='dark'] .wl-img { background: #1a1a1a; }
        .wl-img img { max-width: 100%; max-height: 100%; object-fit: contain; padding: 0.5rem; }
        .wl-info { padding: 0.85rem; }
        .wl-name { font-weight: 700; font-size: 0.9rem; color: var(--text-primary); margin-bottom: 0.3rem; line-height: 1.3; }
        .wl-name a { color: var(--text-primary); }
        .wl-price { font-weight: 800; font-size: 1.05rem; color: var(--text-primary); margin-bottom: 0.5rem; }
        .wl-actions { display: flex; gap: 0.5rem; }
        .wl-actions button { flex: 1; padding: 0.5rem; border-radius: var(--radius-sm); font-size: 0.78rem; font-weight: 600; cursor: pointer; transition: var(--transition); border: none; }
        .btn-wl-cart { background: #1a1a1a; color: #fff; }
        [data-theme='dark'] .btn-wl-cart { background: #f0f0f0; color: #1a1a1a; }
        .btn-wl-cart:hover { transform: translateY(-1px); }
        .btn-wl-remove { background: var(--danger-bg); color: var(--danger); }
        .btn-wl-remove:hover { background: var(--danger); color: #fff; }
        .empty-state { text-align: center; padding: 4rem 2rem; }
        .empty-state .icon { font-size: 4rem; margin-bottom: 1rem; }
        .empty-state p { color: var(--text-muted); margin-bottom: 1rem; }
        .empty-state a { display: inline-block; padding: 0.7rem 1.5rem; background: #1a1a1a; color: #fff; border-radius: var(--radius-sm); font-weight: 600; }
        [data-theme='dark'] .empty-state a { background: #f0f0f0; color: #1a1a1a; }
        .toast { position: fixed; top: 80px; right: 1rem; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0.8rem 1.2rem; box-shadow: var(--shadow-lg); z-index: 999; font-size: 0.85rem; font-weight: 600; color: var(--text-primary); transform: translateX(120%); transition: transform 0.3s ease; }
        .toast.show { transform: translateX(0); }
        .toast.success { border-left: 3px solid #16a34a; }
    </style>
</head>
<body class="showcase-body">
<nav class="top-nav">
    <a href="showcase.php" class="top-nav-logo"><div class="logo-icon">🎮</div><span>GamePro</span></a>
    <div class="top-nav-links">
        <a href="showcase.php">🏠 หน้าแรก</a>
        <a href="cart.php">🛒 ตะกร้า <span class="cart-badge" id="cartBadge">0</span></a>
        <a href="wishlist.php" style="color:var(--text-primary);font-weight:600">❤️ Wishlist</a>
        <a href="login.php">🔐 เข้าสู่ระบบ</a>
        <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
    </div>
</nav>

<div class="container">
    <h1 class="page-title">❤️ รายการที่ชอบ</h1>
    <div id="wlContent"><div class="empty-state"><div class="icon">❤️</div><p>กำลังโหลด...</p></div></div>
</div>

<div class="toast" id="toast"></div>

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

function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show success';
    setTimeout(() => t.className = 'toast', 2500);
}

function loadWishlist() {
    const wl = JSON.parse(localStorage.getItem('wishlist') || '[]');
    const container = document.getElementById('wlContent');
    if (wl.length === 0) {
        container.innerHTML = `<div class="empty-state"><div class="icon">💔</div><p>ยังไม่มีสินค้าในรายการที่ชอบ</p><a href="showcase.php">🏠 เลือกซื้อสินค้า</a></div>`;
        return;
    }
    container.innerHTML = `<div class="wl-grid">${wl.map(p => `
        <div class="wl-card" id="wl-${p.id}">
            <a href="product_detail.php?id=${p.id}">
                <div class="wl-img">${p.img ? `<img src="${p.img}" alt="${p.name}">` : `<span style="font-size:3rem">${p.icon||'📦'}</span>`}</div>
            </a>
            <div class="wl-info">
                <div class="wl-name"><a href="product_detail.php?id=${p.id}">${p.name}</a></div>
                <div class="wl-price">฿${Number(p.price).toLocaleString('th-TH',{minimumFractionDigits:2})}</div>
                <div class="wl-actions">
                    <button class="btn-wl-cart" onclick="addToCart(${p.id})">🛒 เพิ่มลงตะกร้า</button>
                    <button class="btn-wl-remove" onclick="removeFromWl(${p.id})">🗑</button>
                </div>
            </div>
        </div>
    `).join('')}</div>`;
}

function removeFromWl(id) {
    let wl = JSON.parse(localStorage.getItem('wishlist') || '[]');
    wl = wl.filter(w => w.id !== id);
    localStorage.setItem('wishlist', JSON.stringify(wl));
    showToast('💔 ลบออกจาก Wishlist แล้ว');
    loadWishlist();
}

async function addToCart(id) {
    const form = new FormData();
    form.append('action', 'add');
    form.append('product_id', id);
    form.append('qty', 1);
    const res = await fetch('cart_api.php', { method: 'POST', body: form });
    const data = await res.json();
    if (data.ok) {
        showToast('🛒 เพิ่มลงตะกร้าแล้ว!');
        document.getElementById('cartBadge').textContent = data.count;
    } else {
        showToast(data.msg || 'เกิดข้อผิดพลาด');
    }
}

(async function() {
    try { const r = await fetch('cart_api.php?action=count'); const d = await r.json(); document.getElementById('cartBadge').textContent = d.count||0; } catch(e){}
})();

loadWishlist();
</script>
<a href="javascript:history.back()" class="btn-back-float" title="ย้อนกลับ">←</a>
</body>
</html>
