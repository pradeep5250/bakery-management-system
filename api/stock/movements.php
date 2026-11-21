<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_STOCK_VIEW);
$productId = (int) ($_GET['product_id'] ?? 0);
$sql = 'SELECT sm.*, p.product_name, u.name AS user_name
        FROM stock_movements sm
        INNER JOIN products p ON p.id = sm.product_id
        LEFT JOIN users u ON u.id = sm.created_by
        WHERE 1=1';
$params = [];
if ($productId > 0) {
    $sql .= ' AND sm.product_id = ?';
    $params[] = $productId;
}
$sql .= ' ORDER BY sm.id DESC LIMIT 200';
$stmt = db()->prepare($sql);
$stmt->execute($params);
json_response(true, 'OK', $stmt->fetchAll());
