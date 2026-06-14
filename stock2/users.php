<?php
session_start();
require_once 'config.php';
require_once 'auth.php';
require_permission('manage_users');

$message = '';
$messageType = '';

// ---- เพิ่มผู้ใช้ ----
if (isset($_POST['add_user'])) {
    $username  = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $password  = $_POST['password'];
    $role      = $_POST['role'];

    // ตรวจ username ซ้ำ
    $check = $pdo->prepare("SELECT id FROM users WHERE username=?");
    $check->execute([$username]);
    if ($check->fetch()) {
        $message = "❌ ชื่อผู้ใช้ \"$username\" มีอยู่แล้ว";
        $messageType = 'error';
    } elseif (strlen($password) < 6) {
        $message = "❌ รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร";
        $messageType = 'error';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, full_name, password, role) VALUES (?,?,?,?)")
            ->execute([$username, $full_name, $hash, $role]);
        $message = "✅ เพิ่มผู้ใช้ \"$full_name\" สำเร็จ";
        $messageType = 'success';
    }
}

// ---- แก้ไขผู้ใช้ ----
if (isset($_POST['edit_user'])) {
    $id        = (int)$_POST['id'];
    $full_name = trim($_POST['full_name']);
    $role      = $_POST['role'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password  = $_POST['password'];

    if ($password) {
        if (strlen($password) < 6) {
            $message = "❌ รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร";
            $messageType = 'error';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET full_name=?, role=?, is_active=?, password=? WHERE id=?")
                ->execute([$full_name, $role, $is_active, $hash, $id]);
        }
    } else {
        $pdo->prepare("UPDATE users SET full_name=?, role=?, is_active=? WHERE id=?")
            ->execute([$full_name, $role, $is_active, $id]);
    }
    if (!$message) {
        $message = "✅ แก้ไขผู้ใช้สำเร็จ";
        $messageType = 'success';
    }
}

// ---- ลบผู้ใช้ ----
if (isset($_GET['delete']) && (int)$_GET['delete'] !== $_SESSION['user_id']) {
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([(int)$_GET['delete']]);
    $message = "🗑️ ลบผู้ใช้สำเร็จ";
    $messageType = 'success';
}

// ดึงข้อมูลผู้ใช้
$users = $pdo->query("SELECT * FROM users ORDER BY role, username")->fetchAll();

// ดึงผู้ใช้สำหรับแก้ไข
$editUser = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editUser = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>จัดการผู้ใช้ — StockPro</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<main class="main">
  <div class="page-title">👥 จัดการผู้ใช้งาน</div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start">

    <!-- ตารางผู้ใช้ -->
    <div class="card">
      <div class="card-header"><h2>รายชื่อผู้ใช้ทั้งหมด (<?= count($users) ?> คน)</h2></div>
      <table>
        <thead>
          <tr>
            <th>ชื่อ-นามสกุล</th>
            <th>Username</th>
            <th>Role</th>
            <th>สถานะ</th>
            <th>วันที่สร้าง</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
            <td style="font-family:monospace;font-size:13px"><?= htmlspecialchars($u['username']) ?></td>
            <td><?= role_badge($u['role']) ?></td>
            <td>
              <?php if ($u['is_active']): ?>
                <span style="color:#16a34a;font-weight:600">● เปิดใช้งาน</span>
              <?php else: ?>
                <span style="color:#dc2626;font-weight:600">● ปิดใช้งาน</span>
              <?php endif; ?>
            </td>
            <td style="font-size:13px;color:#64748b"><?= date('d/m/y', strtotime($u['created_at'])) ?></td>
            <td>
              <a href="users.php?edit=<?= $u['id'] ?>" class="btn btn-warning btn-sm">✏️</a>
              <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                <a href="users.php?delete=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
                   onclick="return confirm('ลบ <?= htmlspecialchars($u['full_name']) ?>?')">🗑️</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Form เพิ่ม/แก้ไข -->
    <div class="card">
      <div class="card-header">
        <h2><?= $editUser ? '✏️ แก้ไขผู้ใช้' : '➕ เพิ่มผู้ใช้ใหม่' ?></h2>
        <?php if ($editUser): ?><a href="users.php" class="btn btn-outline btn-sm">+ ใหม่</a><?php endif; ?>
      </div>
      <div class="form-wrap">
        <form method="POST">
          <?php if ($editUser): ?>
            <input type="hidden" name="edit_user" value="1">
            <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
          <?php else: ?>
            <input type="hidden" name="add_user" value="1">
          <?php endif; ?>

          <div class="form-group" style="margin-bottom:14px">
            <label>ชื่อ-นามสกุล *</label>
            <input type="text" name="full_name" required placeholder="เช่น สมชาย ใจดี" value="<?= htmlspecialchars($editUser['full_name'] ?? '') ?>">
          </div>
          <div class="form-group" style="margin-bottom:14px">
            <label>Username *</label>
            <input type="text" name="username" <?= $editUser ? 'value="'.htmlspecialchars($editUser['username']).'" readonly style="background:#f8fafc"' : 'required placeholder="ตัวอักษรและตัวเลขเท่านั้น"' ?>>
          </div>
          <div class="form-group" style="margin-bottom:14px">
            <label><?= $editUser ? 'รหัสผ่านใหม่ (เว้นว่างถ้าไม่เปลี่ยน)' : 'รหัสผ่าน *' ?></label>
            <input type="password" name="password" <?= $editUser ? '' : 'required' ?> placeholder="อย่างน้อย 6 ตัวอักษร">
          </div>
          <div class="form-group" style="margin-bottom:14px">
            <label>Role / สิทธิ์ *</label>
            <select name="role" required>
              <option value="staff"   <?= ($editUser['role'] ?? '') === 'staff'   ? 'selected' : '' ?>>👷 Staff — ปรับสต็อกได้เท่านั้น</option>
              <option value="manager" <?= ($editUser['role'] ?? '') === 'manager' ? 'selected' : '' ?>>👔 Manager — เพิ่ม/แก้ไข/ลบสินค้า + รายงาน</option>
              <option value="admin"   <?= ($editUser['role'] ?? '') === 'admin'   ? 'selected' : '' ?>>🔑 Admin — ทุกอย่าง รวมจัดการผู้ใช้</option>
            </select>
          </div>
          <?php if ($editUser): ?>
          <div class="form-group" style="margin-bottom:14px">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
              <input type="checkbox" name="is_active" value="1" <?= $editUser['is_active'] ? 'checked' : '' ?> style="width:auto">
              เปิดใช้งานบัญชีนี้
            </label>
          </div>
          <?php endif; ?>

          <!-- ตารางสิทธิ์ -->
          <div style="background:#f8fafc;border-radius:8px;padding:14px;margin-bottom:16px;font-size:13px">
            <p style="font-weight:700;color:#64748b;margin-bottom:10px">สรุปสิทธิ์แต่ละ Role</p>
            <table style="width:100%;border-collapse:collapse">
              <tr style="color:#94a3b8;font-size:12px">
                <th style="text-align:left;padding:4px 0">การกระทำ</th>
                <th style="text-align:center">Staff</th>
                <th style="text-align:center">Manager</th>
                <th style="text-align:center">Admin</th>
              </tr>
              <?php
              $perms = [
                'ดูรายการสินค้า'   => [true, true,  true],
                'ปรับสต็อก'        => [true, true,  true],
                'เพิ่ม/แก้ไขสินค้า' => [false,true, true],
                'ลบสินค้า'         => [false,true,  true],
                'ดูรายงาน'         => [false,true,  true],
                'จัดการผู้ใช้'      => [false,false, true],
              ];
              foreach ($perms as $label => $cols):
              ?>
              <tr>
                <td style="padding:5px 0;color:#475569"><?= $label ?></td>
                <?php foreach ($cols as $v): ?>
                <td style="text-align:center"><?= $v ? '✅' : '❌' ?></td>
                <?php endforeach; ?>
              </tr>
              <?php endforeach; ?>
            </table>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 บันทึก</button>
            <a href="users.php" class="btn btn-outline">ยกเลิก</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>
</body>
</html>
