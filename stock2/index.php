<?php
session_start();
require_once 'config.php';
require_once 'auth.php'; 

$action = $_GET['action'] ?? 'list';
$message = $_SESSION['flash_error'] ?? '';
$messageType = $message ? 'error' : '';
unset($_SESSION['flash_error']);

// ==========================================
// POST Actions — ตรวจสิทธิ์ก่อนทำทุกอย่าง
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['add_product'])) {
        require_permission('add');
        $name     = trim($_POST['name']);
        $category = trim($_POST['category']);
        $quantity = (int)$_POST['quantity'];
        $price    = (float)$_POST['price'];
        $unit     = trim($_POST['unit']);

        if ($name && $quantity >= 0 && $price >= 0) {
            $pdo->prepare("INSERT INTO products (name,category,quantity,price,unit) VALUES (?,?,?,?,?)")
                ->execute([$name,$category,$quantity,$price,$unit]);
            $message = "✅ เพิ่มสินค้า \"$name\" สำเร็จ!";
            $messageType = 'success';
        } else {
            $message = "❌ กรุณากรอกข้อมูลให้ครบ"; $messageType = 'error';
        }
        $action = 'list';
    }

    if (isset($_POST['edit_product'])) {
        require_permission('edit');
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE products SET name=?,category=?,quantity=?,price=?,unit=?,updated_at=NOW() WHERE id=?")
            ->execute([trim($_POST['name']),trim($_POST['category']),(int)$_POST['quantity'],(float)$_POST['price'],trim($_POST['unit']),$id]);
        $message = "✅ แก้ไขสินค้าสำเร็จ!"; $messageType = 'success';
        $action = 'list';
    }

    if (isset($_POST['adjust_stock'])) {
        require_permission('adjust');
        $id   = (int)$_POST['id'];
        $type = $_POST['type'];
        $qty  = (int)$_POST['adjust_qty'];
        $note = trim($_POST['note']);

        $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if ($product) {
            $newQty = $type === 'in' ? $product['quantity'] + $qty : $product['quantity'] - $qty;
            if ($newQty < 0) {
                $message = "❌ สต็อกไม่เพียงพอ! มีอยู่ " . $product['quantity'] . " " . $product['unit'];
                $messageType = 'error';
            } else {
                $pdo->prepare("UPDATE products SET quantity=?,updated_at=NOW() WHERE id=?")->execute([$newQty,$id]);
                $pdo->prepare("INSERT INTO stock_log (product_id,type,quantity,note,user_id) VALUES (?,?,?,?,?)")
                    ->execute([$id,$type,$qty,$note,$_SESSION['user_id']]);
                $typeText = $type === 'in' ? 'รับเข้า' : 'จ่ายออก';
                $message = "✅ บันทึก{$typeText} {$qty} " . $product['unit'] . " สำเร็จ!";
                $messageType = 'success';
            }
        }
        $action = 'list';
    }
}

// ลบสินค้า
if ($action === 'delete' && isset($_GET['id'])) {
    require_permission('delete');
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM stock_log WHERE product_id=?")->execute([$id]);
    $message = "🗑️ ลบสินค้าสำเร็จ"; $messageType = 'success';
    $action = 'list';
}

// ==========================================
// ดึงข้อมูล
// ==========================================
$search    = $_GET['search'] ?? '';
$filterCat = $_GET['cat'] ?? '';
$sql = "SELECT * FROM products WHERE 1=1"; $params = [];
if ($search)    { $sql .= " AND (name LIKE ? OR category LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($filterCat) { $sql .= " AND category=?"; $params[] = $filterCat; }
$sql .= " ORDER BY name ASC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$products = $stmt->fetchAll();

$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalValue    = $pdo->query("SELECT COALESCE(SUM(quantity*price),0) FROM products")->fetchColumn();
$lowStock      = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity<=10")->fetchColumn();
$categories    = $pdo->query("SELECT DISTINCT category FROM products WHERE category!='' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$editProduct = null;
if ($action === 'edit' && isset($_GET['id'])) {
    require_permission('edit');
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $editProduct = $stmt->fetch();
}

$logs = [];
if ($action === 'log') {
    require_permission('report');
    $logs = $pdo->query("
        SELECT l.*, p.name AS product_name, p.unit, u.full_name AS user_name
        FROM stock_log l
        JOIN products p ON l.product_id=p.id
        LEFT JOIN users u ON l.user_id=u.id
        ORDER BY l.created_at DESC LIMIT 100
    ")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>รายการสินค้า — StockPro</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<main class="main">

  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- แจ้งเตือนสิทธิ์ Staff -->
  <?php if ($_SESSION['role'] === 'staff'): ?>
  <div class="perm-notice">
    👷 คุณเข้าสู่ระบบในฐานะ <strong>Staff</strong> — สามารถ<strong>ปรับสต็อก</strong>ได้เท่านั้น (รับเข้า/จ่ายออก)
  </div>
  <?php endif; ?>

  <!-- LIST -->
  <?php if ($action === 'list'): ?>
  <div class="page-title">📦 ภาพรวมสินค้า</div>

  <div class="stats">
    <div class="stat-card"><div class="stat-icon">📦</div><div><div class="stat-label">สินค้าทั้งหมด</div><div class="stat-value"><?= number_format($totalProducts) ?> รายการ</div></div></div>
    <div class="stat-card"><div class="stat-icon">💰</div><div><div class="stat-label">มูลค่าสินค้ารวม</div><div class="stat-value">฿<?= number_format($totalValue,2) ?></div></div></div>
    <div class="stat-card"><div class="stat-icon">⚠️</div><div><div class="stat-label">สต็อกใกล้หมด</div><div class="stat-value" style="color:var(--warning)"><?= number_format($lowStock) ?> รายการ</div></div></div>
  </div>

  <div class="card">
    <div class="card-header">
      <h2>รายการสินค้า</h2>
      <div class="toolbar">
        <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
          <input type="text" name="search" placeholder="🔍 ค้นหา..." value="<?= htmlspecialchars($search) ?>" style="width:180px">
          <select name="cat">
            <option value="">ทุกหมวด</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat) ?>" <?= $filterCat===$cat?'selected':'' ?>><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-outline" type="submit">ค้นหา</button>
        </form>
        <?php if (can('add')): ?>
          <a href="index.php?action=add" class="btn btn-primary">➕ เพิ่มสินค้า</a>
        <?php endif; ?>
      </div>
    </div>
    <table>
      <thead>
        <tr>
          <th>#</th><th>ชื่อสินค้า</th><th>หมวด</th><th>คงเหลือ</th>
          <th>ราคา/หน่วย</th><th>มูลค่ารวม</th><th>สถานะ</th><th>จัดการ</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:40px">ยังไม่มีสินค้า</td></tr>
        <?php else: ?>
        <?php foreach ($products as $i => $p): ?>
        <tr>
          <td style="color:var(--muted)"><?= $i+1 ?></td>
          <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
          <td><?= htmlspecialchars($p['category']?:'-') ?></td>
          <td><strong><?= number_format($p['quantity']) ?></strong> <?= htmlspecialchars($p['unit']) ?></td>
          <td>฿<?= number_format($p['price'],2) ?></td>
          <td>฿<?= number_format($p['quantity']*$p['price'],2) ?></td>
          <td>
            <?php if ($p['quantity']==0): ?><span class="badge badge-out">หมด</span>
            <?php elseif ($p['quantity']<=10): ?><span class="badge badge-low">ใกล้หมด</span>
            <?php else: ?><span class="badge badge-ok">ปกติ</span>
            <?php endif; ?>
          </td>
          <td style="white-space:nowrap">
            <?php if (can('adjust')): ?>
              <button class="btn btn-success btn-sm" onclick="openAdjust(<?= $p['id'] ?>,'<?= htmlspecialchars($p['name'],ENT_QUOTES) ?>',<?= $p['quantity'] ?>,'<?= htmlspecialchars($p['unit'],ENT_QUOTES) ?>')">📥 ปรับ</button>
            <?php endif; ?>
            <?php if (can('edit')): ?>
              <a href="index.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-warning btn-sm">✏️</a>
            <?php endif; ?>
            <?php if (can('delete')): ?>
              <a href="index.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('ยืนยันลบ?')">🗑️</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Modal ปรับสต็อก -->
  <div class="modal-bg" id="adjustModal">
    <div class="modal">
      <h3>📥 ปรับสต็อก: <span id="modalProductName"></span></h3>
      <form method="POST">
        <input type="hidden" name="adjust_stock" value="1">
        <input type="hidden" name="id" id="modalId">
        <p style="margin-bottom:14px;color:var(--muted);font-size:14px">คงเหลือ: <strong id="modalQty"></strong></p>
        <div class="type-btns">
          <button type="button" class="type-btn in active" id="btnIn"  onclick="setType('in')">📥 รับเข้า</button>
          <button type="button" class="type-btn out"        id="btnOut" onclick="setType('out')">📤 จ่ายออก</button>
        </div>
        <input type="hidden" name="type" id="adjustType" value="in">
        <div class="form-group" style="margin-bottom:12px">
          <label>จำนวน</label>
          <input type="number" name="adjust_qty" min="1" value="1" required style="width:100%">
        </div>
        <div class="form-group">
          <label>หมายเหตุ</label>
          <input type="text" name="note" placeholder="เช่น รับจากซัพพลายเออร์..." style="width:100%">
        </div>
        <div class="modal-actions">
          <button type="submit" class="btn btn-primary">💾 บันทึก</button>
          <button type="button" class="btn btn-outline" onclick="closeAdjust()">ยกเลิก</button>
        </div>
      </form>
    </div>
  </div>

  <?php elseif ($action === 'add' || ($action === 'edit' && $editProduct)): ?>
  <div class="page-title"><?= $action==='add'?'➕ เพิ่มสินค้าใหม่':'✏️ แก้ไขสินค้า' ?></div>
  <div class="card" style="max-width:640px">
    <div class="card-header"><h2>กรอกข้อมูลสินค้า</h2></div>
    <div class="form-wrap">
      <form method="POST">
        <?php if ($action==='edit'): ?>
          <input type="hidden" name="edit_product" value="1">
          <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
        <?php else: ?>
          <input type="hidden" name="add_product" value="1">
        <?php endif; ?>
        <div class="form-grid">
          <div class="form-group form-full">
            <label>ชื่อสินค้า *</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($editProduct['name']??'') ?>">
          </div>
          <div class="form-group">
            <label>หมวดหมู่</label>
            <input type="text" name="category" value="<?= htmlspecialchars($editProduct['category']??'') ?>" list="cat-list">
            <datalist id="cat-list"><?php foreach($categories as $c): ?><option value="<?= htmlspecialchars($c) ?>"><?php endforeach; ?></datalist>
          </div>
          <div class="form-group">
            <label>หน่วย</label>
            <input type="text" name="unit" value="<?= htmlspecialchars($editProduct['unit']??'ชิ้น') ?>">
          </div>
          <div class="form-group">
            <label>จำนวน *</label>
            <input type="number" name="quantity" min="0" required value="<?= htmlspecialchars($editProduct['quantity']??'0') ?>">
          </div>
          <div class="form-group">
            <label>ราคาต่อหน่วย (฿) *</label>
            <input type="number" name="price" step="0.01" min="0" required value="<?= htmlspecialchars($editProduct['price']??'0') ?>">
          </div>
        </div>
        <div class="form-actions" style="margin-top:20px">
          <button type="submit" class="btn btn-primary">💾 บันทึก</button>
          <a href="index.php" class="btn btn-outline">ยกเลิก</a>
        </div>
      </form>
    </div>
  </div>

  <?php elseif ($action === 'log'): ?>
  <div class="page-title">📋 ประวัติการเคลื่อนไหวสต็อก</div>
  <div class="card">
    <div class="card-header"><h2>100 รายการล่าสุด</h2></div>
    <table>
      <thead>
        <tr><th>วันที่/เวลา</th><th>สินค้า</th><th>ประเภท</th><th>จำนวน</th><th>ผู้บันทึก</th><th>หมายเหตุ</th></tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:40px">ยังไม่มีประวัติ</td></tr>
        <?php else: ?>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td style="color:var(--muted);font-size:13px"><?= $log['created_at'] ?></td>
          <td><?= htmlspecialchars($log['product_name']) ?></td>
          <td style="color:<?= $log['type']==='in'?'#16a34a':'#dc2626' ?>;font-weight:600">
            <?= $log['type']==='in'?'📥 รับเข้า':'📤 จ่ายออก' ?>
          </td>
          <td><strong><?= number_format($log['quantity']) ?></strong> <?= htmlspecialchars($log['unit']) ?></td>
          <td style="font-size:13px"><?= htmlspecialchars($log['user_name']??'-') ?></td>
          <td style="color:var(--muted)"><?= htmlspecialchars($log['note']?:'-') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

</main>

<script>
function openAdjust(id, name, qty, unit) {
  document.getElementById('modalId').value = id;
  document.getElementById('modalProductName').textContent = name;
  document.getElementById('modalQty').textContent = qty + ' ' + unit;
  document.getElementById('adjustModal').classList.add('open');
}
function closeAdjust() { document.getElementById('adjustModal').classList.remove('open'); }
function setType(t) {
  document.getElementById('adjustType').value = t;
  document.getElementById('btnIn').className  = 'type-btn in'  + (t==='in' ?' active':'');
  document.getElementById('btnOut').className = 'type-btn out' + (t==='out'?' active':'');
}
document.getElementById('adjustModal').addEventListener('click', e => { if (e.target === document.getElementById('adjustModal')) closeAdjust(); });
</script>
</body>
</html>
