<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_STOCK_VIEW);
$q = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (p.product_name LIKE ? OR p.barcode LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
$sql .= ' ORDER BY p.product_name';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();
foreach ($items as &$item) {
    $item['stock_status'] = stock_status((int) $item['stock_quantity'], (int) $item['minimum_stock']);
}
json_response(true, 'OK', $items);
