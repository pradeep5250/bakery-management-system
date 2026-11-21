<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_PRODUCT_VIEW);

$q = trim((string) ($_GET['q'] ?? ''));
$category = (int) ($_GET['category_id'] ?? 0);
$status = $_GET['status'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$per = 20;
$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(p.product_name LIKE ? OR p.barcode LIKE ? OR p.id = ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = ctype_digit($q) ? (int) $q : 0;
}
if ($category > 0) {
    $where[] = 'p.category_id = ?';
    $params[] = $category;
}
if (in_array($status, ['active', 'inactive'], true)) {
    $where[] = 'p.status = ?';
    $params[] = $status;
}
$sqlWhere = implode(' AND ', $where);
$count = db()->prepare("SELECT COUNT(*) FROM products p WHERE $sqlWhere");
$count->execute($params);
$pg = pagination((int) $count->fetchColumn(), $page, $per);
$stmt = db()->prepare(
    "SELECT p.*, c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE $sqlWhere
     ORDER BY p.product_name
     LIMIT {$pg['per_page']} OFFSET {$pg['offset']}"
);
$stmt->execute($params);
json_response(true, 'OK', ['items' => $stmt->fetchAll(), 'pagination' => $pg]);
