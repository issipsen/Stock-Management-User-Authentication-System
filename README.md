#  Stock Management & User Authentication System

##  Project Overview

Stock Management & User Authentication System เป็นระบบเว็บแอปพลิเคชันที่พัฒนาด้วย PHP และ MySQL สำหรับจัดการข้อมูลผู้ใช้งาน พร้อมระบบเข้าสู่ระบบ (Authentication) และการควบคุมสิทธิ์การเข้าถึงหน้าเว็บ

ระบบถูกออกแบบเพื่อเป็นพื้นฐานสำหรับการพัฒนาระบบจัดการสต็อกสินค้า หรือระบบสารสนเทศภายในองค์กร โดยสามารถนำไปต่อยอดเพิ่มฟังก์ชันการจัดการสินค้า คลังสินค้า และรายงานต่าง ๆ ได้ในอนาคต

---

##  Objectives

* จัดการบัญชีผู้ใช้งานภายในระบบ
* รองรับการเข้าสู่ระบบและออกจากระบบ
* ควบคุมการเข้าถึงข้อมูลผ่านระบบ Authentication
* เชื่อมต่อฐานข้อมูล MySQL
* เป็นโครงสร้างพื้นฐานสำหรับระบบจัดการสต็อกสินค้า

---

##  Technologies Used

* PHP
* MySQL
* HTML5
* CSS3
* SQL

---

##  Project Structure

```text
stock2/
│
├── index.php        # หน้า Dashboard หลัก
├── login.php        # หน้าเข้าสู่ระบบ
├── logout.php       # ออกจากระบบ
├── auth.php         # ตรวจสอบสิทธิ์ผู้ใช้งาน
├── users.php        # จัดการข้อมูลผู้ใช้งาน
├── config.php       # ตั้งค่าฐานข้อมูล
├── setup.sql        # โครงสร้างฐานข้อมูล
├── style.css        # ไฟล์ตกแต่งเว็บไซต์
│
└── assets/          # ไฟล์รูปภาพหรือทรัพยากรเพิ่มเติม (ถ้ามี)
```

---

##  Features

###  Authentication System

* Login เข้าสู่ระบบ
* Logout ออกจากระบบ
* ตรวจสอบ Session
* จำกัดการเข้าถึงหน้าเว็บสำหรับผู้ที่ไม่ได้เข้าสู่ระบบ

###  User Management

* แสดงรายการผู้ใช้งาน
* จัดเก็บข้อมูลผู้ใช้ในฐานข้อมูล
* รองรับการเพิ่มและแก้ไขข้อมูลผู้ใช้ (สามารถต่อยอดได้)

###  Database Integration

* เชื่อมต่อ MySQL ผ่านไฟล์ `config.php`
* ใช้ไฟล์ `setup.sql` สำหรับสร้างฐานข้อมูลเริ่มต้น

---

##  System Workflow

1. ผู้ใช้งานเข้าสู่หน้า Login
2. ระบบตรวจสอบข้อมูลบัญชีผู้ใช้
3. หากถูกต้อง ระบบสร้าง Session
4. ผู้ใช้งานสามารถเข้าถึง Dashboard ได้
5. ระบบตรวจสอบสิทธิ์ผ่าน `auth.php`
6. ผู้ใช้งานสามารถออกจากระบบผ่าน `logout.php`

---

##  Installation

### 1. Clone Project

```bash
git clone https://github.com/your-username/stock-management-system.git
```

### 2. Import Database

* เปิด phpMyAdmin
* สร้างฐานข้อมูลใหม่
* Import ไฟล์ `setup.sql`

### 3. Configure Database

แก้ไขไฟล์ `config.php`

```php
$host = "localhost";
$username = "root";
$password = "";
$database = "stock_db";
```

### 4. Run Project

นำโปรเจกต์ไปไว้ใน

```text
xampp/htdocs/
```

จากนั้นเปิด

```text
http://localhost/stock2
```

---

##  Future Improvements

* ระบบจัดการสินค้า (Product Management)
* ระบบรับเข้า–เบิกจ่ายสินค้า
* ระบบแจ้งเตือนสินค้าใกล้หมด
* Dashboard และรายงานสถิติ
* Role-Based Access Control (Admin/User)
* Export รายงานเป็น PDF และ Excel
* Audit Log สำหรับติดตามการใช้งาน

---

##  Security Features

* Session Authentication
* Access Control
* Database Configuration Separation
* Protected Pages ผ่าน Auth Middleware

---

## ☁️ AWS Cloud Architecture

ระบบถูกออกแบบให้สามารถ Deploy บน AWS Cloud เพื่อรองรับการใช้งานจริง โดยใช้บริการหลักดังนี้

### Amazon EC2

ใช้สำหรับรัน Web Application ที่พัฒนาด้วย PHP

**หน้าที่**

* Host เว็บไซต์
* ประมวลผลคำขอจากผู้ใช้งาน
* เชื่อมต่อฐานข้อมูลและบริการอื่นบน AWS

### Amazon RDS (MySQL)

ใช้สำหรับจัดเก็บข้อมูลของระบบ

**ข้อมูลที่จัดเก็บ**

* ข้อมูลผู้ใช้งาน
* ข้อมูลสินค้า
* ข้อมูลการเข้าสู่ระบบ
* ข้อมูลธุรกรรมภายในระบบ

**ข้อดี**

* Backup อัตโนมัติ
* High Availability
* จัดการฐานข้อมูลได้ง่าย

### Amazon S3

ใช้สำหรับจัดเก็บไฟล์และรูปภาพ

**ตัวอย่างการใช้งาน**

* รูปภาพสินค้า
* เอกสารประกอบ
* ไฟล์รายงาน
* ไฟล์สำรองข้อมูล

### Amazon Cognito

ใช้สำหรับ Authentication และ Authorization

**ความสามารถ**

* Login
* Register
* Password Recovery
* Multi-Factor Authentication (MFA)
* JWT Token Authentication

ช่วยลดความเสี่ยงด้านความปลอดภัยและลดภาระในการพัฒนาระบบยืนยันตัวตนเอง

---

##  Security Implementation

ระบบถูกออกแบบตามแนวทางด้านความปลอดภัยของ AWS

### HTTPS Encryption

* ใช้ SSL/TLS Certificate
* เข้ารหัสข้อมูลระหว่าง Client และ Server

### IAM (Identity and Access Management)

กำหนดสิทธิ์การเข้าถึง AWS Resources

ตัวอย่างสิทธิ์

* Administrator
* Developer
* Viewer

### Data Encryption

* Encryption at Rest (RDS & S3)
* Encryption in Transit (HTTPS)

### Authentication & Authorization

* Amazon Cognito User Pool
* Session Management
* Role-Based Access Control (RBAC)

### Audit Logging

ใช้ AWS CloudWatch และ CloudTrail เพื่อตรวจสอบ

* การเข้าสู่ระบบ
* การแก้ไขข้อมูล
* การเข้าถึงทรัพยากรบน AWS

---

##  AWS System Architecture

```text
Users
   │
   ▼
Internet
   │
   ▼
Amazon EC2 (PHP Application)
   │
   ├────────────► Amazon Cognito
   │                (Authentication)
   │
   ├────────────► Amazon RDS MySQL
   │                (Database)
   │
   └────────────► Amazon S3
                    (File Storage)

Monitoring
   │
   ├────────► CloudWatch
   └────────► CloudTrail
```

---

##  Deployment Architecture

### Frontend

* HTML
* CSS
* JavaScript

### Backend

* PHP

### Cloud Infrastructure

* Amazon EC2
* Amazon RDS MySQL
* Amazon S3
* Amazon Cognito
* AWS IAM
* AWS CloudWatch
* AWS CloudTrail

---

##  Scalability

ระบบสามารถขยายตัวในอนาคตได้โดย

* เพิ่ม EC2 Instance
* ใช้ Application Load Balancer (ALB)
* เปิด Auto Scaling
* เพิ่ม Read Replica ของ RDS
* ใช้ CloudFront CDN ร่วมกับ S3

เพื่อรองรับจำนวนผู้ใช้งานที่เพิ่มขึ้น

