# 🎮 Gaming Store Inventory System

ระบบจัดการสต็อกสินค้าร้านขายอุปกรณ์เกมมิ่ง พัฒนาด้วย **PHP 8.2 + MariaDB 10.6** รันผ่าน **Docker**

## 🛠️ เทคโนโลยี

- **PHP 8.2** — Backend + Server-side rendering
- **MariaDB 10.6** — ฐานข้อมูล
- **Docker Compose** — จัดการ containers
- **PDO** — เชื่อมต่อ DB (Prepared Statements)
- **phpMyAdmin** — จัดการฐานข้อมูลผ่าน GUI

## 🚀 วิธีรัน

```bash
# 1. Build และรัน Docker containers
docker-compose up -d --build

# 2. เปิดเว็บแอป
# http://localhost:8001

# 3. เปิด phpMyAdmin (ถ้าต้องการ)
# http://localhost:8080
# Login: root / rootpassword
```

> **หมายเหตุ:** `schema.sql` จะถูก import อัตโนมัติเมื่อสร้าง container ครั้งแรก

## 📄 หน้าเว็บ

| หน้า | URL | ฟีเจอร์ |
|---|---|---|
| Dashboard | `/index.php` | สถิติสินค้า, stock ต่ำ, ยอดขายรวม, ยอดขายล่าสุด |
| Products | `/products.php` | เพิ่ม/แก้ไข/ลบสินค้า, เตือน stock ต่ำ |
| Sales | `/sales.php` | สร้างรายการขาย, หักstock อัตโนมัติ, ประวัติการขาย |

## 🗄️ Database

- **categories** — หมวดหมู่สินค้า (Graphics Cards, Processors, RAM, Storage, Monitors, Peripherals)
- **products** — ข้อมูลสินค้า (ชื่อ, ราคา, stock, หมวดหมู่)
- **sales** — รายการขาย
- **sale_items** — รายละเอียดสินค้าในแต่ละรายการขาย

## 📁 โครงสร้างไฟล์

```
├── Dockerfile
├── docker-compose.yml
├── db.php
├── schema.sql
├── index.php
├── products.php
├── sales.php
├── assets/
│   └── css/
│       └── style.css
└── README.md
```
