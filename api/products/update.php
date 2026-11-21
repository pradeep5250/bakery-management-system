<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_PRODUCT_EDIT);
api_require_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    json_response(false, 'Product not found', null, 404);
}
$stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    json_response(false, 'Product not found', null, 404);
}

$name = trim((string) ($_POST['product_name'] ?? $product['product_name']));
$barcode = trim((string) ($_POST['barcode'] ?? (string) $product['barcode']));
$categoryId = (int) ($_POST['category_id'] ?? $product['category_id']);
$description = trim((string) ($_POST['description'] ?? (string) $product['description']));
$purchase = (float) ($_POST['purchase_price'] ?? $product['purchase_price']);
$selling = (float) ($_POST['selling_price'] ?? $product['selling_price']);
$gst = (float) ($_POST['gst'] ?? $product['gst']);
$unit = trim((string) ($_POST['unit'] ?? $product['unit']));
$min = max(0, (int) ($_POST['minimum_stock'] ?? $product['minimum_stock']));
$status = in_array($_POST['status'] ?? $product['status'], ['active', 'inactive'], true) ? $_POST['status'] : $product['status'];

if ($barcode !== '') {
    $chk = db()->prepare('SELECT id FROM products WHERE barcode = ? AND id <> ?');
    $chk->execute([$barcode, $id]);
    if ($chk->fetch()) {
        json_response(false, 'Barcode already exists.', null, 400);
    }
}
$image = $product['product_image'];
try {
    if (!empty($_FILES['product_image']) && is_array($_FILES['product_image']) && ($_FILES['product_image']['error'] ?? 4) !== 4) {
        $image = safe_upload_image($_FILES['product_image']);
    }
} catch (Throwable $e) {
    json_response(false, $e->getMessage(), null, 400);
}

db()->prepare(
    'UPDATE products SET product_name=?, barcode=?, category_id=?, description=?, purchase_price=?, selling_price=?, gst=?, unit=?, minimum_stock=?, product_image=?, status=? WHERE id=?'
)->execute([$name, $barcode !== '' ? $barcode : null, $categoryId, $description, $purchase, $selling, $gst, $unit, $min, $image, $status, $id]);

log_activity('PRODUCT_UPDATE', 'products', $id, 'Updated product ' . $name);
json_response(true, 'Product updated successfully');
