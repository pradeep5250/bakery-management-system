# Bakery Management System with POC POS

Production-style bakery management and point-of-sale billing system for XAMPP.

PHP 8.1+ · MySQL 8 / MariaDB · Bootstrap 5 · Chart.js · JsBarcode · html5-qrcode

## Project overview

The first screen is a landing page with two modules:

1. **Bill Determination** — independent billing calculator, preview and print
2. **POC (Point of Control)** — role selection, login, dashboards and POS

Roles:

| Role | Access |
| --- | --- |
| Super Admin | Full control: products, users, permissions, settings, reports |
| Admin | Operational control: products, stock, barcode, sales, reports |
| Biller | Selling only: scan, cart, payment, bill, print |

## Features

- Role login with password hashing and session regeneration
- Permission-based access on pages and APIs
- Product, category and stock management with stock movements
- Barcode generate / print / USB scan / mobile camera scan
- Atomic POS billing (`START TRANSACTION` / `COMMIT` / `ROLLBACK`)
- Cash and GPay/UPI payments (UPI requires explicit paid confirmation)
- A4 invoice and 80mm thermal print CSS
- Sales, biller, payment, stock and cancelled-bill reports with CSV export
- Activity logs, login history, notifications, settings, PWA shell

## Requirements

- XAMPP with Apache and MySQL/MariaDB running
- PHP 8.1 or newer with PDO MySQL
- Browser with camera permission for mobile barcode scanning

## Installation

### 1. Place the project

This workspace folder is:

`C:\xampp\htdocs\BAKERY MANAGEMENT`

For the URL `http://localhost/bakery-pos/`, copy or rename the folder to:

`C:\xampp\htdocs\bakery-pos`

The app detects its own base path automatically.

### 2. Start XAMPP

Start **Apache** and **MySQL**.

### 3. Database credentials

Default XAMPP values in `config/database.php`:

- Host: `localhost`
- Database: `bakery_management`
- User: `root`
- Password: empty
- Port: `3306`

If MySQL root has a password, set it in `config/database.php`.

### 4. Install (recommended)

Open:

`http://localhost/BAKERY%20MANAGEMENT/install.php`

or

`http://localhost/bakery-pos/install.php`

Click **Create Database & Seed Data**. This creates tables, sample products and demo users.

### 5. Manual import (phpMyAdmin)

1. Open phpMyAdmin
2. Import `database/bakery.sql`
3. Open `install.php` so demo users are created with `password_hash()`

Users are **not** stored as plain passwords in SQL. `install.php` hashes them.

## Default logins

| Role | Username | Password |
| --- | --- | --- |
| Super Admin | `superadmin` | `admin123` |
| Admin | `admin` | `admin123` |
| Biller | `biller` | `admin123` |

## Application flow

```
Landing → Bill Determination
       → POC → Super Admin login → Dashboard
             → Admin login → Dashboard
             → Biller login → POS → Scan → Cart → Payment → Bill → Print
```

## Barcode scanning

- **Desktop USB scanner:** focus the barcode field on POS. Scanners send digits plus Enter.
- **Mobile camera:** tap **SCAN**, allow camera, then point at EAN-13, EAN-8, UPC-A, CODE128 or CODE39.

## Printing

- Invoice page supports **A4** and **80mm thermal**
- Browser print dialog: choose the printer, hide headers/footers if needed
- Barcode labels print from Super Admin / Admin barcode pages

## PWA

`manifest.json` and `service-worker.js` allow “Install app”. Completed sales always require a live PHP/MySQL server. Offline checkout is blocked.

## Backup

Command line (XAMPP):

```bat
C:\xampp\mysql\bin\mysqldump -u root bakery_management > bakery_backup.sql
```

phpMyAdmin: select `bakery_management` → Export → Go.

Restore:

```bat
C:\xampp\mysql\bin\mysql -u root bakery_management < bakery_backup.sql
```

## Folder structure

See `config/`, `middleware/`, `includes/`, `super-admin/`, `admin/`, `biller/`, `api/`, `assets/`, `database/`, `uploads/`, `logs/`.

## Security notes

- PDO prepared statements on user input
- CSRF tokens on state-changing POSTs
- Output escaping with `htmlspecialchars`
- Role and permission checks on PHP pages and APIs
- Product images validated by extension, MIME type and size
- `logs/`, `config/` and `database/` are blocked by `.htaccess`
- Login rate limiting after repeated failures

## Troubleshooting

| Problem | Check |
| --- | --- |
| Database connection unavailable | MySQL is running; password in `config/database.php` |
| 404 on CSS/JS | Folder name / `BASE_URL`; Apache `AllowOverride All` for `.htaccess` |
| Camera scanner fails | HTTPS or localhost, camera permission, HTTPS not required on localhost |
| Bill not created | Stock, payment confirmation, CSRF token, PHP error log in `logs/php-errors.log` |
| Cannot log in as Super Admin with a biller account | Role-specific login pages reject the wrong role |

## Deployment

Keep the app behind a local network or HTTPS in production. Change all default passwords, set a MySQL password, and restrict `install.php` after setup.
