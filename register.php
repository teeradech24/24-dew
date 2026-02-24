<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif (strlen($password) < 4) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร';
    } elseif ($password !== $confirm) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } else {
        // Check existing
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้แล้ว';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'user')");
            $stmt->execute([$username, $email, $hash]);
            $success = 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก — GamePro</title>
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
            max-width: 460px;
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
        .alert { margin-bottom: 1.25rem; padding: 0.75rem 1rem; border-radius: var(--radius-sm); font-size: 0.9rem; }
        .alert-danger { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); }
        .alert-success { background: rgba(34,197,94,0.1); color: #22c55e; border: 1px solid rgba(34,197,94,0.2); }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <div class="login-logo">
            <div class="logo-icon">🎮</div>
            <span>GamePro</span>
        </div>
        <p class="login-title">สมัครสมาชิกใหม่</p>

        <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">ชื่อผู้ใช้</label>
                <input type="text" name="username" class="form-control" required placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">อีเมล</label>
                <input type="email" name="email" class="form-control" required placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">รหัสผ่าน</label>
                <input type="password" name="password" class="form-control" required placeholder="Password (อย่างน้อย 4 ตัว)">
            </div>
            <div class="form-group">
                <label class="form-label">ยืนยันรหัสผ่าน</label>
                <input type="password" name="confirm_password" class="form-control" required placeholder="Confirm Password">
            </div>
            <button type="submit" class="btn btn-primary login-btn">สมัครสมาชิก</button>
        </form>
        <div class="login-footer">
            มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a>
        </div>
    </div>
</div>
</body>
</html>
