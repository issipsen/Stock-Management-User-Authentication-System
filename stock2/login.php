<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // บันทึก session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            // บันทึก log การ login
            $pdo->prepare("INSERT INTO login_log (user_id, ip_address) VALUES (?, ?)")
                ->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);

            header('Location: index.php');
            exit;
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบ';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>เข้าสู่ระบบ — StockPro</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Sarabun', sans-serif;
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 60%, #1d4ed8 100%);
  }
  .login-wrap {
    width: 100%; max-width: 420px; padding: 16px;
  }
  .logo {
    text-align: center; margin-bottom: 32px; color: #fff;
  }
  .logo-icon { font-size: 48px; display: block; margin-bottom: 10px; }
  .logo h1 { font-size: 26px; font-weight: 700; letter-spacing: 1px; }
  .logo p { font-size: 14px; opacity: 0.8; margin-top: 4px; }

  .card {
    background: #fff; border-radius: 16px; padding: 36px 32px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
  }
  .card h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 24px; }

  .form-group { margin-bottom: 18px; }
  .form-group label { display: block; font-size: 14px; font-weight: 600; color: #64748b; margin-bottom: 6px; }
  .form-group input {
    width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0;
    border-radius: 10px; font-family: 'Sarabun', sans-serif; font-size: 15px;
    color: #1e293b; outline: none; transition: border 0.2s;
  }
  .form-group input:focus { border-color: #2563eb; }

  .input-icon { position: relative; }
  .input-icon span {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    font-size: 18px; pointer-events: none;
  }
  .input-icon input { padding-left: 42px; }

  .alert-error {
    background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;
    border-radius: 8px; padding: 12px 16px; margin-bottom: 18px; font-size: 14px;
  }

  .btn-login {
    width: 100%; padding: 14px; background: #2563eb; color: #fff;
    border: none; border-radius: 10px; font-family: 'Sarabun', sans-serif;
    font-size: 16px; font-weight: 700; cursor: pointer; transition: background 0.2s;
    margin-top: 4px;
  }
  .btn-login:hover { background: #1d4ed8; }

  .demo-box {
    margin-top: 24px; background: #f8fafc; border-radius: 10px;
    padding: 16px; border: 1px solid #e2e8f0;
  }
  .demo-box p { font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
  .demo-item {
    display: flex; justify-content: space-between; align-items: center;
    padding: 7px 0; border-bottom: 1px solid #e2e8f0;
  }
  .demo-item:last-child { border-bottom: none; }
  .demo-item .role-badge {
    font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 20px;
  }
  .role-admin { background: #ede9fe; color: #6d28d9; }
  .role-manager { background: #dbeafe; color: #1d4ed8; }
  .role-staff { background: #dcfce7; color: #166534; }
  .demo-cred { font-size: 13px; color: #64748b; font-family: monospace; }
  .demo-btn {
    font-size: 12px; color: #2563eb; cursor: pointer; background: none; border: none;
    font-family: 'Sarabun', sans-serif; text-decoration: underline; padding: 0;
  }
</style>
</head>
<body>
<div class="login-wrap">
  <div class="logo">
    <span class="logo-icon">📦</span>
    <h1>StockPro</h1>
    <p>ระบบจัดการสต็อกสินค้า</p>
  </div>

  <div class="card">
    <h2>เข้าสู่ระบบ</h2>

    <?php if ($error): ?>
      <div class="alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="loginForm">
      <div class="form-group">
        <label>ชื่อผู้ใช้</label>
        <div class="input-icon">
          <span>👤</span>
          <input type="text" name="username" id="username" placeholder="กรอกชื่อผู้ใช้" required autocomplete="username">
        </div>
      </div>
      <div class="form-group">
        <label>รหัสผ่าน</label>
        <div class="input-icon">
          <span>🔒</span>
          <input type="password" name="password" id="password" placeholder="กรอกรหัสผ่าน" required autocomplete="current-password">
        </div>
      </div>
      <button type="submit" class="btn-login">เข้าสู่ระบบ →</button>
    </form>

    <!-- Demo accounts -->
    <div class="demo-box">
      <p>บัญชีทดสอบ</p>
      <div class="demo-item">
        <span class="role-badge role-admin">Admin</span>
        <span class="demo-cred">admin / admin123</span>
        <button class="demo-btn" onclick="fill('admin','admin123')">ใช้</button>
      </div>
      <div class="demo-item">
        <span class="role-badge role-manager">Manager</span>
        <span class="demo-cred">manager / mgr123</span>
        <button class="demo-btn" onclick="fill('manager','mgr123')">ใช้</button>
      </div>
      <div class="demo-item">
        <span class="role-badge role-staff">Staff</span>
        <span class="demo-cred">staff / staff123</span>
        <button class="demo-btn" onclick="fill('staff','staff123')">ใช้</button>
      </div>
    </div>
  </div>
</div>
<script>
function fill(u, p) {
  document.getElementById('username').value = u;
  document.getElementById('password').value = p;
}
</script>
</body>
</html>
