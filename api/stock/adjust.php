<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_any_permission(PERM_STOCK_ADJUST, PERM_STOCK_CREATE, PERM_STOCK_EDIT);
api_require_csrf();
$data = api_post_data();
$productId = (int) ($data['product_id'] ?? 0);
$type = strtoupper((string) ($data['movement_type'] ?? 'ADJUSTMENT'));
$qty = (int) ($data['quantity'] ?? 0);
$reason = trim((string) ($data['reason'] ?? ''));
$allowed = ['PURCHASE', 'ADJUSTMENT', 'RETURN', 'DAMAGE', 'EXPIRED'];
if (!in_array($type, $allowed, true) || $productId <= 0 || $qty === 0) {
    json_response(false, 'Invalid stock adjustment.', null, 400);
}
if (in_array($type, ['DAMAGE', 'EXPIRED'], true) && $qty > 0) {
    $qty = -abs($qty);
}
if ($type === 'PURCHASE' || $type === 'RETURN') {
    $qty = abs($qty);
}

$pdo = db();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? FOR UPDATE');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) {
        throw new RuntimeException('Product not found');
    }
    $prev = (int) $product['stock_quantity'];
    $new = $prev + $qty;
    if ($new < 0) {
        throw new RuntimeException('Stock cannot be negative.');
    }
    $pdo->prepare('UPDATE products SET stock_quantity = ? WHERE id = ?')->execute([$new, $productId]);
    $pdo->prepare(
        'INSERT INTO stock_movements (product_id, movement_type, quantity, previous_stock, new_stock, reference_type, reason, created_by)
         VALUES (?, ?, ?, ?, ?, "manual", ?, ?)'
    )->execute([$productId, $type, $qty, $prev, $new, $reason, (int) $_SESSION['user_id']]);
    $pdo->commit();
    $action = $qty > 0 ? 'STOCK_ADD' : ($type === 'ADJUSTMENT' ? 'STOCK_ADJUST' : 'STOCK_REDUCE');
    log_activity($action, 'stock', $productId, $product['product_name'] . " $prev → $new");
    notify_role('super_admin', 'Stock Updated', $product['product_name'] . ' stock is now ' . $new, 'info');
    if ($new === 0) {
        notify_role('super_admin', 'Out of Stock', $product['product_name'] . ' is out of stock.', 'warning');
        notify_role('admin', 'Out of Stock', $product['product_name'] . ' is out of stock.', 'warning');
    } elseif ($new <= (int) $product['minimum_stock']) {
        notify_role('super_admin', 'Low Stock', $product['product_name'] . ' is low on stock.', 'warning');
        notify_role('admin', 'Low Stock', $product['product_name'] . ' is low on stock.', 'warning');
    }
    json_response(true, 'Stock updated.', ['previous' => $prev, 'new' => $new]);
} catch (Throwable $e) {
    $pdo->rollBack();
    json_response(false, $e->getMessage(), null, 400);
}
