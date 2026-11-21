<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_PRODUCT_VIEW);

$q = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT p.id, p.product_name, p.barcode, p.selling_price, p.gst, p.unit, p.stock_quantity, p.minimum_stock, p.status, p.product_image, c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.status = "active"';
$params = [];
if ($q !== '') {
    $sql .= ' AND (p.product_name LIKE ? OR p.barcode LIKE ? OR p.id = ? OR c.name LIKE ?)';
    $like = '%' . $q . '%';
    $params = [$like, $like, ctype_digit($q) ? (int) $q : 0, $like];
}
$sql .= ' ORDER BY p.product_name LIMIT 40';
$stmt = db()->prepare($sql);
$stmt->execute($params);
json_response(true, 'OK', $stmt->fetchAll());
