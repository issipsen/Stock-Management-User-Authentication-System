-- ==========================================
-- setup.sql — สร้างฐานข้อมูลพร้อมระบบ Login
-- รันไฟล์นี้ครั้งเดียวตอนติดตั้งครั้งแรก
-- ==========================================

CREATE DATABASE IF NOT EXISTS stock_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stock_db;

-- ตารางผู้ใช้
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    full_name  VARCHAR(100) NOT NULL,
    password   VARCHAR(255) NOT NULL,  -- bcrypt hash
    role       ENUM('admin','manager','staff') NOT NULL DEFAULT 'staff',
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at DATETIME     DEFAULT NOW()
);

-- ตารางสินค้า
CREATE TABLE IF NOT EXISTS products (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(200)  NOT NULL,
    category   VARCHAR(100)  DEFAULT '',
    quantity   INT           NOT NULL DEFAULT 0,
    price      DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit       VARCHAR(50)   DEFAULT 'ชิ้น',
    created_at DATETIME      DEFAULT NOW(),
    updated_at DATETIME      DEFAULT NOW()
);

-- ตารางประวัติสต็อก (เพิ่ม user_id)
CREATE TABLE IF NOT EXISTS stock_log (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT  NOT NULL,
    user_id    INT  DEFAULT NULL,
    type       ENUM('in','out') NOT NULL,
    quantity   INT  NOT NULL,
    note       VARCHAR(255) DEFAULT '',
    created_at DATETIME DEFAULT NOW(),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ตาราง Login Log
CREATE TABLE IF NOT EXISTS login_log (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    ip_address VARCHAR(45) DEFAULT '',
    created_at DATETIME DEFAULT NOW(),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================
-- บัญชีเริ่มต้น (รหัสผ่าน bcrypt)
-- admin:admin123 / manager:mgr123 / staff:staff123
-- ⚠️ เปลี่ยนรหัสผ่านทันทีหลังติดตั้ง!
-- ==========================================
INSERT INTO users (username, full_name, password, role) VALUES
('admin',   'ผู้ดูแลระบบ',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('manager', 'ผู้จัดการ',    '$2y$10$oD2RkCCFh.sBHoq38tGR3OxVQ6BkBOJD8sXeSj3Dy0GlkCRRPmmgy', 'manager'),
('staff',   'พนักงาน',      '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p6VdxFCBuTvL3vFvWQgRJS', 'staff');

-- ข้อมูลสินค้าตัวอย่าง
INSERT INTO products (name, category, quantity, price, unit) VALUES
('น้ำดื่มขวด 600ml',   'เครื่องดื่ม',          200,  8.00, 'ขวด'),
('กาแฟ 3in1 กล่อง',    'เครื่องดื่ม',           30, 85.00, 'กล่อง'),
('ปากกาลูกลื่น',       'อุปกรณ์สำนักงาน',       150,  5.00, 'แท่ง'),
('กระดาษ A4 รีม',      'อุปกรณ์สำนักงาน',        20,120.00, 'รีม'),
('ถุงมือยาง',          'อุปกรณ์ทำความสะอาด',       5, 45.00, 'ถุง');
