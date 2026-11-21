<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_BILL_CANCEL);
api_require_csrf();
$data = api_post_data();
$id = (int) ($data['id'] ?? 0);
$reason = trim((string) ($data['reason'] ?? ''));
if ($reason === '') {
    json_response(false, 'Cancellation reason is required.', null, 400);
}
$pdo = db();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT * FROM sales WHERE id = ? FOR UPDATE');
    $stmt->execute([$id]);
    $sale = $stmt->fetch();
    if (!$sale) {
        throw new RuntimeException('Sale not found.');
    }
    if ($sale['status'] === 'CANCELLED') {
        throw new RuntimeException('This bill is already cancelled.');
    }
    $items = $pdo->prepare('SELECT * FROM sale_items WHERE sale_id = ?');
    $items->execute([$id]);
    foreach ($items->fetchAll() as $item) {
        $p = $pdo->prepare('SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE');
        $p->execute([(int) $item['product_id']]);
        $prev = (int) $p->fetchColumn();
        $new = $prev + (int) $item['quantity'];
        $pdo->prepare('UPDATE products SET stock_quantity = ? WHERE id = ?')->execute([$new, (int) $item['product_id']]);
        $pdo->prepare(
            'INSERT INTO stock_movements (product_id, movement_type, quantity, previous_stock, new_stock, reference_type, reference_id, reason, created_by)
             VALUES (?, "RETURN", ?, ?, ?, "sale_cancel", ?, ?, ?)'
        )->execute([
            (int) $item['product_id'],
            (int) $item['quantity'],
            $prev,
            $new,
            $id,
            $reason,
            (int) $_SESSION['user_id'],
        ]);
    }
    $pdo->prepare(
        'UPDATE sales SET status = "CANCELLED", payment_status = "REFUNDED", cancelled_by = ?, cancelled_at = NOW(), cancel_reason = ? WHERE id = ?'
    )->execute([(int) $_SESSION['user_id'], $reason, $id]);
    $pdo->prepare(
        'INSERT INTO activity_logs (user_id, role, action, module, reference_id, description, ip_address, user_agent)
         VALUES (?, ?, "SALE_CANCEL", "sales", ?, ?, ?, ?)'
    )->execute([(int) $_SESSION['user_id'], $_SESSION['role'], $id, 'Cancelled ' . $sale['invoice_number'] . ': ' . $reason, client_ip(), user_agent()]);
    $pdo->commit();
    json_response(true, 'Bill cancelled and stock restored.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(false, $e->getMessage(), null, 400);
}
