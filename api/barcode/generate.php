<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_BARCODE_GENERATE);
api_require_csrf();
$data = api_post_data();
$productId = (int) ($data['product_id'] ?? 0);
$type = strtoupper((string) ($data['barcode_type'] ?? 'CODE128'));
$allowed = ['EAN-13', 'EAN-8', 'UPC-A', 'CODE128', 'CODE39'];
if (!in_array($type, $allowed, true)) {
    json_response(false, 'Unsupported barcode type.', null, 400);
}
$stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$productId]);
$product = $stmt->fetch();
if (!$product) {
    json_response(false, 'Product not found', null, 404);
}
$code = trim((string) ($data['barcode'] ?? ''));
if ($code === '') {
    $code = $product['barcode'] ?: ('BC' . str_pad((string) $productId, 10, '0', STR_PAD_LEFT) . random_int(10, 99));
}
$chk = db()->prepare('SELECT id FROM product_barcodes WHERE barcode = ?');
$chk->execute([$code]);
if ($chk->fetch()) {
    json_response(false, 'Barcode already exists.', null, 400);
}
$primary = !empty($data['is_primary']) ? 1 : 0;
if ($primary) {
    db()->prepare('UPDATE product_barcodes SET is_primary = 0 WHERE product_id = ?')->execute([$productId]);
    db()->prepare('UPDATE products SET barcode = ? WHERE id = ?')->execute([$code, $productId]);
}
db()->prepare(
    'INSERT INTO product_barcodes (product_id, barcode, barcode_type, is_primary, created_by) VALUES (?, ?, ?, ?, ?)'
)->execute([$productId, $code, $type, $primary, (int) $_SESSION['user_id']]);
log_activity('BARCODE_GENERATE', 'barcode', $productId, 'Generated ' . $type . ' ' . $code);
json_response(true, 'Barcode generated.', ['barcode' => $code, 'barcode_type' => $type], 201);
