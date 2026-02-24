<?php
session_start();
require_once 'db.php';

// ถ้า login อยู่แล้ว → redirect
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: index.php');
    } else {
        header('Location: showcase.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Try DB first
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: index.php');
            } else {
                header('Location: showcase.php');
            }
            exit;
        }
    } catch (Exception $e) {
        // Table might not exist yet
    }

    // Fallback: hardcoded admin
    if ($username === 'admin' && $password === '1234') {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'admin';
        header('Location: index.php');
        exit;
    }

    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — GamePro</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--bg-primary);
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 1.5rem;
        }
        .login-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
        }
        .login-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }
        .login-logo .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #1a1a1a, #444444);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: var(--shadow);
        }
        .login-logo span {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
        }
        .login-title {
            text-align: center;
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 1.75rem;
            font-weight: 400;
        }
        .login-btn {
            width: 100%;
            padding: 0.8rem;
            font-size: 1rem;
            margin-top: 0.5rem;
        }
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .login-footer a {
            color: var(--text-primary);
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <div class="login-logo">
            <div class="logo-icon">🎮</div>
            <span>GamePro</span>
        </div>
        <p class="login-title">เข้าสู่ระบบจัดการสินค้า</p>

        <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom:1.25rem;">
            ❌ <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">ชื่อผู้ใช้</label>
                <input type="text" name="username" class="form-control" required autofocus placeholder="Username">
            </div>
            <div class="form-group">
                <label class="form-label">รหัสผ่าน</label>
                <input type="password" name="password" class="form-control" required placeholder="Password">
            </div>
            <button type="submit" class="btn btn-primary login-btn">เข้าสู่ระบบ</button>
        </form>
        <div class="login-footer">
            ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a>
        </div>
    </div>
</div>
<a href="javascript:history.back()" class="btn-back-float" title="ย้อนกลับ">←</a>
</body>
</html>
