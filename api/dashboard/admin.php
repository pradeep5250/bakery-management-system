<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_role(ROLE_ADMIN);
$today = date('Y-m-d');
$pdo = db();
json_response(true, 'OK', [
    'today_sales' => (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM sales WHERE status='COMPLETED' AND DATE(created_at)='$today'")->fetchColumn(),
    'today_bills' => (int) $pdo->query("SELECT COUNT(*) FROM sales WHERE status='COMPLETED' AND DATE(created_at)='$today'")->fetchColumn(),
    'total_products' => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status='active'")->fetchColumn(),
    'low_stock' => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status='active' AND stock_quantity > 0 AND stock_quantity <= minimum_stock")->fetchColumn(),
    'out_of_stock' => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status='active' AND stock_quantity = 0")->fetchColumn(),
    'low_stock_products' => $pdo->query("SELECT product_name, stock_quantity, minimum_stock FROM products WHERE status='active' AND stock_quantity > 0 AND stock_quantity <= minimum_stock ORDER BY stock_quantity LIMIT 10")->fetchAll(),
    'out_of_stock_products' => $pdo->query("SELECT product_name FROM products WHERE status='active' AND stock_quantity = 0 ORDER BY product_name LIMIT 10")->fetchAll(),
    'recent_bills' => $pdo->query("SELECT invoice_number, total, created_at FROM sales ORDER BY id DESC LIMIT 8")->fetchAll(),
]);
