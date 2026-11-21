<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_PRODUCT_CREATE);
api_require_csrf();

$name = trim((string) ($_POST['product_name'] ?? ''));
$barcode = trim((string) ($_POST['barcode'] ?? ''));
$categoryId = (int) ($_POST['category_id'] ?? 0);
$description = trim((string) ($_POST['description'] ?? ''));
$purchase = (float) ($_POST['purchase_price'] ?? 0);
$selling = (float) ($_POST['selling_price'] ?? 0);
$gst = (float) ($_POST['gst'] ?? 0);
$unit = trim((string) ($_POST['unit'] ?? 'pcs'));
$stock = max(0, (int) ($_POST['stock_quantity'] ?? 0));
$min = max(0, (int) ($_POST['minimum_stock'] ?? 0));
if ($name === '' || $selling <= 0 || $categoryId <= 0) {
    json_response(false, 'Product name, category and selling price are required.', null, 400);
}
if ($barcode !== '') {
    $chk = db()->prepare('SELECT id FROM products WHERE barcode = ?');
    $chk->execute([$barcode]);
    if ($chk->fetch()) {
        json_response(false, 'Barcode already exists.', null, 400);
    }
}
$image = null;
try {
    if (!empty($_FILES['product_image']) && is_array($_FILES['product_image'])) {
        $image = safe_upload_image($_FILES['product_image']);
    }
} catch (Throwable $e) {
    json_response(false, $e->getMessage(), null, 400);
}

$pdo = db();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        'INSERT INTO products (product_name, barcode, category_id, description, purchase_price, selling_price, gst, unit, stock_quantity, minimum_stock, product_image, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "active")'
    );
    $stmt->execute([$name, $barcode !== '' ? $barcode : null, $categoryId, $description, $purchase, $selling, $gst, $unit ?: 'pcs', $stock, $min, $image]);
    $id = (int) $pdo->lastInsertId();
    if ($barcode !== '') {
        $pdo->prepare('INSERT INTO product_barcodes (product_id, barcode, barcode_type, is_primary, created_by) VALUES (?, ?, "CODE128", 1, ?)')
            ->execute([$id, $barcode, (int) $_SESSION['user_id']]);
    }
    if ($stock > 0) {
        $pdo->prepare(
            'INSERT INTO stock_movements (product_id, movement_type, quantity, previous_stock, new_stock, reference_type, reference_id, reason, created_by)
             VALUES (?, "PURCHASE", ?, 0, ?, "product", ?, "Initial stock", ?)'
        )->execute([$id, $stock, $stock, $id, (int) $_SESSION['user_id']]);
    }
    $pdo->commit();
    log_activity('PRODUCT_CREATE', 'products', $id, 'Created product ' . $name);
    notify_role('super_admin', 'Product Added', $name . ' was added.', 'success');
    json_response(true, 'Product added successfully', ['id' => $id], 201);
} catch (Throwable $e) {
    $pdo->rollBack();
    app_log('Product create failed: ' . $e->getMessage());
    json_response(false, 'Unable to add product.', null, 500);
}
