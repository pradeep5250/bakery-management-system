<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_role(ROLE_SUPER_ADMIN);

$today = date('Y-m-d');
$pdo = db();

$todaySales = (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM sales WHERE status='COMPLETED' AND DATE(created_at)='$today'")->fetchColumn();
$todayBills = (int) $pdo->query("SELECT COUNT(*) FROM sales WHERE status='COMPLETED' AND DATE(created_at)='$today'")->fetchColumn();
$totalProducts = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status='active'")->fetchColumn();
$totalStock = (int) $pdo->query("SELECT COALESCE(SUM(stock_quantity),0) FROM products WHERE status='active'")->fetchColumn();
$lowStock = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status='active' AND stock_quantity > 0 AND stock_quantity <= minimum_stock")->fetchColumn();
$outStock = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status='active' AND stock_quantity = 0")->fetchColumn();
$activeAdmins = (int) $pdo->query("SELECT COUNT(*) FROM users u INNER JOIN roles r ON r.id=u.role_id WHERE r.name='admin' AND u.status='active'")->fetchColumn();
$activeBillers = (int) $pdo->query("SELECT COUNT(*) FROM users u INNER JOIN roles r ON r.id=u.role_id WHERE r.name='biller' AND u.status='active'")->fetchColumn();

$cash = (float) $pdo->query("SELECT COALESCE(SUM(p.amount),0) FROM payments p INNER JOIN payment_methods m ON m.id=p.payment_method_id INNER JOIN sales s ON s.id=p.sale_id WHERE s.status='COMPLETED' AND p.status='PAID' AND m.code='CASH' AND DATE(s.created_at)='$today'")->fetchColumn();
$gpay = (float) $pdo->query("SELECT COALESCE(SUM(p.amount),0) FROM payments p INNER JOIN payment_methods m ON m.id=p.payment_method_id INNER JOIN sales s ON s.id=p.sale_id WHERE s.status='COMPLETED' AND p.status='PAID' AND m.code='GPAY' AND DATE(s.created_at)='$today'")->fetchColumn();

$recentBills = $pdo->query("SELECT s.invoice_number, s.total, s.created_at, u.name AS biller_name FROM sales s INNER JOIN users u ON u.id=s.biller_id ORDER BY s.id DESC LIMIT 8")->fetchAll();
$recentAct = $pdo->query("SELECT action, description, created_at FROM activity_logs ORDER BY id DESC LIMIT 8")->fetchAll();
$top = $pdo->query("SELECT p.product_name, SUM(si.quantity) qty, SUM(si.total) amount FROM sale_items si INNER JOIN products p ON p.id=si.product_id INNER JOIN sales s ON s.id=si.sale_id WHERE s.status='COMPLETED' GROUP BY p.id, p.product_name ORDER BY qty DESC LIMIT 5")->fetchAll();
$low = $pdo->query("SELECT product_name, stock_quantity, minimum_stock FROM products WHERE status='active' AND stock_quantity > 0 AND stock_quantity <= minimum_stock ORDER BY stock_quantity ASC LIMIT 8")->fetchAll();
$out = $pdo->query("SELECT product_name, barcode FROM products WHERE status='active' AND stock_quantity = 0 ORDER BY product_name LIMIT 8")->fetchAll();
$chart = $pdo->query("SELECT DATE(created_at) d, SUM(total) t FROM sales WHERE status='COMPLETED' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at) ORDER BY d")->fetchAll();
$payChart = $pdo->query("SELECT m.name, SUM(p.amount) t FROM payments p INNER JOIN payment_methods m ON m.id=p.payment_method_id INNER JOIN sales s ON s.id=p.sale_id WHERE s.status='COMPLETED' AND p.status='PAID' GROUP BY m.id, m.name")->fetchAll();

json_response(true, 'OK', [
    'cards' => [
        'today_sales' => $todaySales,
        'today_bills' => $todayBills,
        'total_products' => $totalProducts,
        'total_stock' => $totalStock,
        'low_stock' => $lowStock,
        'out_of_stock' => $outStock,
        'active_admins' => $activeAdmins,
        'active_billers' => $activeBillers,
        'cash_sales' => $cash,
        'gpay_sales' => $gpay,
    ],
    'recent_bills' => $recentBills,
    'recent_activities' => $recentAct,
    'top_products' => $top,
    'low_stock_products' => $low,
    'out_of_stock_products' => $out,
    'sales_chart' => $chart,
    'payment_chart' => $payChart,
]);
