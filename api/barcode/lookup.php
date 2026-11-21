<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_PRODUCT_VIEW);

$code = trim((string) ($_GET['barcode'] ?? $_GET['q'] ?? ''));
if ($code === '') {
    json_response(false, 'Barcode not found.', null, 404);
}
$stmt = db()->prepare(
    'SELECT p.* FROM products p
     LEFT JOIN product_barcodes pb ON pb.product_id = p.id
     WHERE p.barcode = ? OR pb.barcode = ?
     LIMIT 1'
);
$stmt->execute([$code, $code]);
$product = $stmt->fetch();
if (!$product) {
    json_response(false, 'Product not found.', null, 404);
}
if ($product['status'] !== 'active') {
    json_response(false, 'Product is unavailable.', null, 400);
}
if ((int) $product['stock_quantity'] <= 0) {
    json_response(false, 'Product is out of stock.', null, 400);
}
json_response(true, 'OK', $product);
