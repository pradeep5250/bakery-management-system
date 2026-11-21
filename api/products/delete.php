<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_PRODUCT_DELETE);
api_require_csrf();
$data = api_post_data();
$id = (int) ($data['id'] ?? 0);
$activate = !empty($data['activate']);
$stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    json_response(false, 'Product not found', null, 404);
}
$used = db()->prepare('SELECT COUNT(*) FROM sale_items WHERE product_id = ?');
$used->execute([$id]);
if (!$activate && (int) $used->fetchColumn() > 0) {
    db()->prepare('UPDATE products SET status = "inactive" WHERE id = ?')->execute([$id]);
    log_activity('PRODUCT_DEACTIVATE', 'products', $id, 'Deactivated product ' . $product['product_name']);
    json_response(true, 'Product deactivated. Sales history was preserved.');
}
if ($activate) {
    db()->prepare('UPDATE products SET status = "active" WHERE id = ?')->execute([$id]);
    log_activity('PRODUCT_ACTIVATE', 'products', $id, 'Activated product ' . $product['product_name']);
    json_response(true, 'Product activated.');
}
db()->prepare('UPDATE products SET status = "inactive" WHERE id = ?')->execute([$id]);
log_activity('PRODUCT_DEACTIVATE', 'products', $id, 'Deactivated product ' . $product['product_name']);
json_response(true, 'Product deactivated.');
