<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_post() || !verify_csrf_token($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) {
    json_response(false, 'Invalid security token. Please refresh and try again.', null, 403);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '[]', true);
if (!is_array($data) || empty($data)) {
    $data = $_POST;
    if (!empty($data['items']) && is_string($data['items'])) {
        $data['items'] = json_decode($data['items'], true);
    }
}
$items = $data['items'] ?? [];
if (!is_array($items) || !$items) {
    json_response(false, 'Add at least one item.', null, 400);
}

$pdo = db();
$pdo->beginTransaction();
try {
    $subtotal = 0; $gst = 0; $discount = 0;
    $clean = [];
    foreach ($items as $item) {
        $name = trim((string) ($item['product_name'] ?? ''));
        $qty = (int) ($item['quantity'] ?? 0);
        $price = (float) ($item['unit_price'] ?? 0);
        $gstP = (float) ($item['gst'] ?? 0);
        $disc = (float) ($item['discount'] ?? 0);
        if ($name === '' || $qty <= 0 || $price < 0) {
            throw new RuntimeException('Invalid item.');
        }
        $calc = calc_item_totals($price, $qty, $gstP, $disc);
        $subtotal += $calc['item_total'];
        $gst += $calc['gst'];
        $discount += $calc['discount'];
        $clean[] = [$name, $qty, $price, $calc['gst'], $calc['discount'], $calc['total']];
    }
    $total = round($subtotal - $discount + $gst, 2);
    $ref = generate_bd_number($pdo);
    $pdo->prepare(
        'INSERT INTO bill_determinations (reference_number, customer_name, subtotal, discount, gst, total, payment_method, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $ref,
        trim((string) ($data['customer_name'] ?? '')),
        $subtotal,
        $discount,
        $gst,
        $total,
        trim((string) ($data['payment_method'] ?? '')),
        trim((string) ($data['notes'] ?? '')),
    ]);
    $id = (int) $pdo->lastInsertId();
    $ins = $pdo->prepare('INSERT INTO bill_determination_items (bill_id, product_name, quantity, unit_price, gst, discount, total) VALUES (?,?,?,?,?,?,?)');
    foreach ($clean as $row) {
        $ins->execute(array_merge([$id], $row));
    }
    $pdo->commit();
    json_response(true, 'Bill determination saved.', ['id' => $id, 'reference_number' => $ref, 'total' => $total], 201);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(false, $e->getMessage(), null, 400);
}
