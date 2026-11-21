-- Bakery Management System
-- Single SQL file: schema + seed + sample data
-- Database: bakery_management

-- Bakery Management System
-- Database: bakery_management
-- MySQL 8+ / MariaDB 10.4+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';
SET time_zone = '+05:30';

CREATE DATABASE IF NOT EXISTS bakery_management
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE bakery_management;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS login_history;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS bill_determination_items;
DROP TABLE IF EXISTS bill_determinations;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS product_barcodes;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS user_permissions;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS payment_methods;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;

CREATE TABLE roles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  display_name VARCHAR(100) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  permission_name VARCHAR(80) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  module VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_permissions_name (permission_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_role_permission (role_id, permission_id),
  CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(100) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role_id INT UNSIGNED NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  force_password_change TINYINT(1) NOT NULL DEFAULT 0,
  last_login DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  KEY idx_users_role_id (role_id),
  KEY idx_users_status (status),
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_permissions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  granted TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_permission (user_id, permission_id),
  CONSTRAINT fk_up_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_up_permission FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_name VARCHAR(150) NOT NULL,
  barcode VARCHAR(50) DEFAULT NULL,
  category_id INT UNSIGNED DEFAULT NULL,
  description TEXT DEFAULT NULL,
  purchase_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  gst DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  unit VARCHAR(30) NOT NULL DEFAULT 'pcs',
  stock_quantity INT NOT NULL DEFAULT 0,
  minimum_stock INT NOT NULL DEFAULT 0,
  product_image VARCHAR(255) DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_products_barcode (barcode),
  KEY idx_products_name (product_name),
  KEY idx_products_category_id (category_id),
  KEY idx_products_status (status),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_barcodes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id INT UNSIGNED NOT NULL,
  barcode VARCHAR(50) NOT NULL,
  barcode_type VARCHAR(20) NOT NULL DEFAULT 'CODE128',
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_product_barcodes_barcode (barcode),
  KEY idx_pb_product_id (product_id),
  CONSTRAINT fk_pb_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pb_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_movements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id INT UNSIGNED NOT NULL,
  movement_type ENUM('PURCHASE','SALE','ADJUSTMENT','RETURN','DAMAGE','EXPIRED') NOT NULL,
  quantity INT NOT NULL,
  previous_stock INT NOT NULL,
  new_stock INT NOT NULL,
  reference_type VARCHAR(40) DEFAULT NULL,
  reference_id INT UNSIGNED DEFAULT NULL,
  reason VARCHAR(255) DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sm_product_id (product_id),
  KEY idx_sm_created_at (created_at),
  CONSTRAINT fk_sm_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_sm_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  email VARCHAR(150) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  customer_type ENUM('walk_in','registered') NOT NULL DEFAULT 'registered',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_customers_phone (phone),
  KEY idx_customers_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_methods (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80) NOT NULL,
  code VARCHAR(40) NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payment_methods_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sales (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  invoice_number VARCHAR(40) NOT NULL,
  customer_id INT UNSIGNED DEFAULT NULL,
  biller_id INT UNSIGNED NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  gst DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  cash_received DECIMAL(12,2) DEFAULT NULL,
  change_amount DECIMAL(12,2) DEFAULT NULL,
  payment_status ENUM('PENDING','PAID','FAILED','REFUNDED') NOT NULL DEFAULT 'PENDING',
  status ENUM('COMPLETED','CANCELLED') NOT NULL DEFAULT 'COMPLETED',
  cancelled_by INT UNSIGNED DEFAULT NULL,
  cancelled_at DATETIME DEFAULT NULL,
  cancel_reason VARCHAR(255) DEFAULT NULL,
  notes VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sales_invoice (invoice_number),
  KEY idx_sales_biller_id (biller_id),
  KEY idx_sales_created_at (created_at),
  KEY idx_sales_status (status),
  KEY idx_sales_customer_id (customer_id),
  CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_sales_biller FOREIGN KEY (biller_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_sales_cancelled_by FOREIGN KEY (cancelled_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sale_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sale_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  gst DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_si_sale_id (sale_id),
  KEY idx_si_product_id (product_id),
  CONSTRAINT fk_si_sale FOREIGN KEY (sale_id) REFERENCES sales (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_si_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sale_id INT UNSIGNED NOT NULL,
  payment_method_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  status ENUM('PENDING','PAID','FAILED') NOT NULL DEFAULT 'PENDING',
  transaction_reference VARCHAR(100) DEFAULT NULL,
  paid_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_payments_sale_id (sale_id),
  KEY idx_payments_method (payment_method_id),
  CONSTRAINT fk_payments_sale FOREIGN KEY (sale_id) REFERENCES sales (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_payments_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoices (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sale_id INT UNSIGNED NOT NULL,
  invoice_number VARCHAR(40) NOT NULL,
  printed_count INT UNSIGNED NOT NULL DEFAULT 0,
  last_printed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_invoices_sale (sale_id),
  UNIQUE KEY uq_invoices_number (invoice_number),
  CONSTRAINT fk_invoices_sale FOREIGN KEY (sale_id) REFERENCES sales (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bill_determinations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  reference_number VARCHAR(40) NOT NULL,
  customer_name VARCHAR(120) DEFAULT NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  gst DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  payment_method VARCHAR(40) DEFAULT NULL,
  notes VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_bd_reference (reference_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bill_determination_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  bill_id INT UNSIGNED NOT NULL,
  product_name VARCHAR(150) NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  gst DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_bdi_bill (bill_id),
  CONSTRAINT fk_bdi_bill FOREIGN KEY (bill_id) REFERENCES bill_determinations (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  role VARCHAR(50) DEFAULT NULL,
  action VARCHAR(80) NOT NULL,
  module VARCHAR(50) DEFAULT NULL,
  reference_id INT UNSIGNED DEFAULT NULL,
  description VARCHAR(500) DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_al_user_id (user_id),
  KEY idx_al_created_at (created_at),
  KEY idx_al_action (action),
  CONSTRAINT fk_al_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_history (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  login_time DATETIME NOT NULL,
  logout_time DATETIME DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  device VARCHAR(80) DEFAULT NULL,
  status ENUM('success','failed','logout') NOT NULL DEFAULT 'success',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_lh_user_id (user_id),
  KEY idx_lh_login_time (login_time),
  CONSTRAINT fk_lh_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_key VARCHAR(80) NOT NULL,
  setting_value TEXT DEFAULT NULL,
  updated_by INT UNSIGNED DEFAULT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_settings_key (setting_key),
  CONSTRAINT fk_settings_user FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  title VARCHAR(150) NOT NULL,
  message VARCHAR(500) NOT NULL,
  type VARCHAR(40) NOT NULL DEFAULT 'info',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_n_user_id (user_id),
  KEY idx_n_is_read (is_read),
  CONSTRAINT fk_n_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
-- Seed: roles, permissions, payment methods, categories, settings
-- Users are created by install.php with password_hash()

USE bakery_management;

INSERT INTO roles (id, name, display_name, description) VALUES
(1, 'super_admin', 'Super Admin', 'Full system control'),
(2, 'admin', 'Admin', 'Product, stock and operational control'),
(3, 'biller', 'Biller', 'Sales, billing and payment');

INSERT INTO permissions (id, permission_name, description, module) VALUES
(1,  'PRODUCT_VIEW',        'View products', 'products'),
(2,  'PRODUCT_CREATE',      'Add products', 'products'),
(3,  'PRODUCT_EDIT',        'Edit products', 'products'),
(4,  'PRODUCT_DELETE',      'Remove products', 'products'),
(5,  'CATEGORY_VIEW',       'View categories', 'categories'),
(6,  'CATEGORY_CREATE',     'Add categories', 'categories'),
(7,  'CATEGORY_EDIT',       'Edit categories', 'categories'),
(8,  'CATEGORY_DELETE',     'Delete categories', 'categories'),
(9,  'STOCK_VIEW',          'View stock', 'stock'),
(10, 'STOCK_CREATE',        'Add stock', 'stock'),
(11, 'STOCK_EDIT',          'Edit stock', 'stock'),
(12, 'STOCK_ADJUST',        'Adjust stock', 'stock'),
(13, 'BARCODE_VIEW',        'View barcodes', 'barcode'),
(14, 'BARCODE_GENERATE',    'Generate barcodes', 'barcode'),
(15, 'BARCODE_PRINT',       'Print barcodes', 'barcode'),
(16, 'BILL_CREATE',         'Create bills', 'billing'),
(17, 'BILL_PRINT',          'Print bills', 'billing'),
(18, 'BILL_REPRINT',        'Reprint bills', 'billing'),
(19, 'BILL_CANCEL',         'Cancel bills', 'billing'),
(20, 'SALES_VIEW',          'View sales', 'sales'),
(21, 'SALES_EXPORT',        'Export sales', 'sales'),
(22, 'PAYMENT_VIEW',        'View payments', 'payments'),
(23, 'PAYMENT_CONFIG',      'Set up payment methods', 'payments'),
(24, 'CUSTOMER_VIEW',       'View customers', 'customers'),
(25, 'CUSTOMER_CREATE',     'Add customers', 'customers'),
(26, 'CUSTOMER_EDIT',       'Edit customers', 'customers'),
(27, 'REPORT_VIEW',         'View reports', 'reports'),
(28, 'REPORT_EXPORT',       'Export reports', 'reports'),
(29, 'USER_VIEW',           'View users', 'users'),
(30, 'USER_CREATE',         'Add users', 'users'),
(31, 'USER_EDIT',           'Edit users', 'users'),
(32, 'USER_DELETE',         'Deactivate users', 'users'),
(33, 'HISTORY_VIEW',        'View activity logs', 'history'),
(34, 'LOGIN_HISTORY_VIEW',  'View login history', 'history'),
(35, 'PRINT_CONTROL',       'Control printing', 'print'),
(36, 'SETTINGS_VIEW',       'View settings', 'settings'),
(37, 'SETTINGS_EDIT',       'Edit settings', 'settings'),
(38, 'BILL_HISTORY_VIEW',   'View billing history', 'billing');

-- Super Admin: all permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- Admin defaults: operational, no user/settings/payment config
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE permission_name IN (
  'PRODUCT_VIEW','PRODUCT_EDIT','CATEGORY_VIEW',
  'STOCK_VIEW','STOCK_CREATE','STOCK_EDIT','STOCK_ADJUST',
  'BARCODE_VIEW','BARCODE_GENERATE','BARCODE_PRINT',
  'BILL_PRINT','BILL_REPRINT','SALES_VIEW','SALES_EXPORT',
  'PAYMENT_VIEW','CUSTOMER_VIEW','CUSTOMER_CREATE','CUSTOMER_EDIT',
  'REPORT_VIEW','REPORT_EXPORT','HISTORY_VIEW','PRINT_CONTROL'
);

-- Biller defaults: selling and billing only
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE permission_name IN (
  'PRODUCT_VIEW','BILL_CREATE','BILL_PRINT','SALES_VIEW',
  'PAYMENT_VIEW','CUSTOMER_VIEW','CUSTOMER_CREATE'
);

INSERT INTO payment_methods (id, name, code, status) VALUES
(1, 'Cash', 'CASH', 'active'),
(2, 'GPay / UPI', 'GPAY', 'active'),
(3, 'Card', 'CARD', 'inactive'),
(4, 'PhonePe', 'PHONEPE', 'inactive'),
(5, 'Paytm', 'PAYTM', 'inactive');

INSERT INTO categories (name, description, status) VALUES
('Cakes', 'Cakes and celebration items', 'active'),
('Bread', 'Fresh breads and loaves', 'active'),
('Cookies', 'Cookies and biscuits', 'active'),
('Puffs', 'Veg and non-veg puffs', 'active'),
('Pastries', 'Pastries and slices', 'active'),
('Snacks', 'Bakery snacks', 'active'),
('Beverages', 'Tea, coffee and drinks', 'active'),
('Sweets', 'Indian and bakery sweets', 'active'),
('Other', 'Other bakery items', 'active');

INSERT INTO customers (name, phone, customer_type) VALUES
('Walk-in Customer', '', 'walk_in');

INSERT INTO settings (setting_key, setting_value) VALUES
('bakery_name', 'Golden Crust Bakery'),
('address', '12, Baker Street, Chennai - 600001'),
('phone', '+91 98765 43210'),
('email', 'hello@goldencrust.local'),
('gst_number', '33AAAAA0000A1Z5'),
('logo', ''),
('invoice_prefix', 'INV'),
('currency', 'INR'),
('currency_symbol', '₹'),
('timezone', 'Asia/Kolkata'),
('date_format', 'd-m-Y'),
('time_format', 'h:i A'),
('tax_inclusive', '0'),
('default_gst', '0'),
('allow_discount', '1'),
('max_discount_percent', '10'),
('receipt_footer', 'Thank You. Visit Again.'),
('thank_you_message', 'Thank You. Visit Again.'),
('business_hours', '07:00 - 21:00'),
('printer_type', 'a4'),
('thermal_width', '80'),
('session_timeout', '3600'),
('biller_history_limit', '5');
USE bakery_management;

INSERT INTO products
(product_name, barcode, category_id, description, purchase_price, selling_price, gst, unit, stock_quantity, minimum_stock, product_image, status)
VALUES
('Chocolate Cake',      '8901234567890', 1, 'Rich chocolate cake',           300.00, 450.00, 0.00, 'pcs', 20, 5, 'products/chocolate-cake.svg', 'active'),
('Vanilla Cake',        '8901234567891', 1, 'Classic vanilla sponge cake',   280.00, 420.00, 0.00, 'pcs', 18, 5, 'products/vanilla-cake.svg', 'active'),
('Black Forest Cake',   '8901234567892', 1, 'Black forest with cherries',    350.00, 520.00, 0.00, 'pcs', 12, 4, 'products/black-forest-cake.svg', 'active'),
('Bread',               '8901234567893', 2, 'Fresh white bread loaf',         25.00,  40.00, 0.00, 'pcs', 40, 10,'products/bread.svg', 'active'),
('Veg Puff',            '8901234567894', 4, 'Crispy vegetable puff',          18.00,  40.00, 0.00, 'pcs', 50, 15,'products/veg-puff.svg', 'active'),
('Egg Puff',            '8901234567895', 4, 'Egg stuffed puff',               22.00,  45.00, 0.00, 'pcs', 35, 10,'products/egg-puff.svg', 'active'),
('Chocolate Cookie',    '8901234567896', 3, 'Chocolate chip cookie',          12.00,  25.00, 0.00, 'pcs', 80, 20,'products/chocolate-cookie.svg', 'active'),
('Butter Cookie',       '8901234567897', 3, 'Butter cookie pack',             10.00,  20.00, 0.00, 'pcs', 70, 20,'products/butter-cookie.svg', 'active'),
('Donut',               '8901234567898', 6, 'Glazed donut',                   15.00,  35.00, 0.00, 'pcs', 30, 8, 'products/donut.svg', 'active'),
('Croissant',           '8901234567899', 5, 'Butter croissant',               28.00,  55.00, 0.00, 'pcs', 22, 6, 'products/croissant.svg', 'active'),
('Tea',                 '8901234567900', 7, 'Hot tea',                         8.00,  15.00, 0.00, 'cup', 100,20,'products/tea.svg', 'active'),
('Coffee',              '8901234567901', 7, 'Hot filter coffee',              12.00,  25.00, 0.00, 'cup', 90, 20,'products/coffee.svg', 'active');

INSERT INTO product_barcodes (product_id, barcode, barcode_type, is_primary)
SELECT id, barcode, 'EAN-13', 1 FROM products WHERE barcode IS NOT NULL;

INSERT INTO stock_movements (product_id, movement_type, quantity, previous_stock, new_stock, reference_type, reason)
SELECT id, 'PURCHASE', stock_quantity, 0, stock_quantity, 'seed', 'Initial stock'
FROM products;
