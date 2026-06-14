<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <div class="sidebar-logo">📦 <span>Stock</span>Pro</div>
  <div class="sidebar-user">
    <div class="user-avatar"><?= mb_substr($_SESSION['full_name'], 0, 1) ?></div>
    <div>
      <div class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
      <div><?= role_badge($_SESSION['role']) ?></div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <a href="index.php" class="nav-item <?= $currentPage==='index.php' ? 'active' : '' ?>">
      <span class="icon">🏠</span><span>รายการสินค้า</span>
    </a>
    <?php if (can('add')): ?>
    <a href="index.php?action=add" class="nav-item">
      <span class="icon">➕</span><span>เพิ่มสินค้า</span>
    </a>
    <?php endif; ?>
    <?php if (can('report')): ?>
    <a href="index.php?action=log" class="nav-item <?= isset($_GET['action']) && $_GET['action']==='log' ? 'active' : '' ?>">
      <span class="icon">📋</span><span>ประวัติสต็อก</span>
    </a>
    <?php endif; ?>
    <?php if (can('manage_users')): ?>
    <a href="users.php" class="nav-item <?= $currentPage==='users.php' ? 'active' : '' ?>">
      <span class="icon">👥</span><span>จัดการผู้ใช้</span>
    </a>
    <?php endif; ?>
  </nav>
  <div class="sidebar-footer">
    <a href="logout.php" class="nav-item" style="color:#f87171">
      <span class="icon">🚪</span><span>ออกจากระบบ</span>
    </a>
  </div>
</aside>
