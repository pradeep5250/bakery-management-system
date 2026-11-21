<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
header('Content-Type: application/json; charset=utf-8');

$q = trim((string) ($_GET['q'] ?? ''));
if ($q === '') {
    json_response(true, 'OK', []);
}
$stmt = db()->prepare(
    'SELECT id, product_name, selling_price, gst, unit, stock_quantity, status
     FROM products
     WHERE status = "active" AND (product_name LIKE ? OR barcode LIKE ?)
     ORDER BY product_name LIMIT 15'
);
$like = '%' . $q . '%';
$stmt->execute([$like, $like]);
json_response(true, 'OK', $stmt->fetchAll());
