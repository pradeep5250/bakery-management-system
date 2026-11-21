<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_REPORT_VIEW);
$filter = $_GET['filter'] ?? 'today';
[$from, $to] = date_range_from_filter($filter, $_GET['from'] ?? null, $_GET['to'] ?? null);
$type = $_GET['type'] ?? 'daily';
$pdo = db();

if ($type === 'payment') {
    $stmt = $pdo->prepare(
        "SELECT m.name, m.code, COUNT(p.id) bills, COALESCE(SUM(p.amount),0) total
         FROM payment_methods m
         LEFT JOIN payments p ON p.payment_method_id = m.id AND p.status='PAID'
         LEFT JOIN sales s ON s.id = p.sale_id AND s.status='COMPLETED' AND DATE(s.created_at) BETWEEN ? AND ?
         GROUP BY m.id, m.name, m.code"
    );
    $stmt->execute([$from, $to]);
    json_response(true, 'OK', ['from' => $from, 'to' => $to, 'rows' => $stmt->fetchAll()]);
}
if ($type === 'biller') {
    $stmt = $pdo->prepare(
        "SELECT u.name, COUNT(s.id) bills, COALESCE(SUM(s.total),0) total,
                COALESCE(SUM(CASE WHEN s.status='CANCELLED' THEN 1 ELSE 0 END),0) cancelled
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         LEFT JOIN sales s ON s.biller_id = u.id AND DATE(s.created_at) BETWEEN ? AND ?
         WHERE r.name = 'biller'
         GROUP BY u.id, u.name"
    );
    $stmt->execute([$from, $to]);
    json_response(true, 'OK', ['from' => $from, 'to' => $to, 'rows' => $stmt->fetchAll()]);
}
if ($type === 'product') {
    $stmt = $pdo->prepare(
        "SELECT p.product_name, SUM(si.quantity) qty, SUM(si.total) amount
         FROM sale_items si
         INNER JOIN products p ON p.id = si.product_id
         INNER JOIN sales s ON s.id = si.sale_id
         WHERE s.status='COMPLETED' AND DATE(s.created_at) BETWEEN ? AND ?
         GROUP BY p.id, p.product_name
         ORDER BY qty DESC"
    );
    $stmt->execute([$from, $to]);
    json_response(true, 'OK', ['from' => $from, 'to' => $to, 'rows' => $stmt->fetchAll()]);
}
$stmt = $pdo->prepare(
    "SELECT DATE(created_at) d, COUNT(*) bills, SUM(total) total
     FROM sales WHERE status='COMPLETED' AND DATE(created_at) BETWEEN ? AND ?
     GROUP BY DATE(created_at) ORDER BY d"
);
$stmt->execute([$from, $to]);
json_response(true, 'OK', ['from' => $from, 'to' => $to, 'rows' => $stmt->fetchAll()]);
