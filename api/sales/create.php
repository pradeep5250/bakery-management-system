<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_BILL_CREATE);
api_require_csrf();

$data = api_post_data();
$items = $data['items'] ?? [];
$customerId = (int) ($data['customer_id'] ?? 0);
$discountBill = max(0, (float) ($data['discount'] ?? 0));
$paymentCode = strtoupper((string) ($data['payment_method'] ?? 'CASH'));
$cashReceived = isset($data['cash_received']) ? (float) $data['cash_received'] : null;
$txnRef = trim((string) ($data['transaction_reference'] ?? ''));
$upiStatus = strtoupper((string) ($data['upi_status'] ?? 'PENDING'));

if (!is_array($items) || count($items) === 0) {
    json_response(false, 'Cart is empty.', null, 400);
}

$pm = db()->prepare('SELECT * FROM payment_methods WHERE code = ? AND status = "active"');
$pm->execute([$paymentCode]);
$method = $pm->fetch();
if (!$method) {
    json_response(false, 'Selected payment method is not available.', null, 400);
}

$pdo = db();
$pdo->beginTransaction();
try {
    $subtotal = 0.0;
    $gstTotal = 0.0;
    $itemDiscountTotal = 0.0;
    $prepared = [];

    foreach ($items as $row) {
        $pid = (int) ($row['product_id'] ?? 0);
        $qty = (int) ($row['quantity'] ?? 0);
        $lineDiscount = max(0, (float) ($row['discount'] ?? 0));
        if ($pid <= 0 || $qty <= 0) {
            throw new RuntimeException('Invalid cart item.');
        }
        $st = $pdo->prepare('SELECT * FROM products WHERE id = ? FOR UPDATE');
        $st->execute([$pid]);
        $product = $st->fetch();
        if (!$product) {
            throw new RuntimeException('Product not found.');
        }
        if ($product['status'] !== 'active') {
            throw new RuntimeException($product['product_name'] . ' is unavailable.');
        }
        if ((int) $product['stock_quantity'] < $qty) {
            throw new RuntimeException('Only ' . $product['stock_quantity'] . ' units available for ' . $product['product_name'] . '.');
        }
        $calc = calc_item_totals((float) $product['selling_price'], $qty, (float) $product['gst'], $lineDiscount);
        $subtotal += $calc['item_total'];
        $gstTotal += $calc['gst'];
        $itemDiscountTotal += $calc['discount'];
        $prepared[] = [
            'product' => $product,
            'qty' => $qty,
            'calc' => $calc,
        ];
    }

    $discount = round($itemDiscountTotal + $discountBill, 2);
    if ($discount > $subtotal) {
        throw new RuntimeException('Discount cannot exceed subtotal.');
    }
    $grand = round($subtotal - $discount + $gstTotal, 2);
    if ($grand < 0) {
        throw new RuntimeException('Invalid totals.');
    }

    $payStatus = PAY_PAID;
    $change = null;
    $paidAt = date('Y-m-d H:i:s');

    if ($paymentCode === 'CASH') {
        if ($cashReceived === null || $cashReceived < $grand) {
            throw new RuntimeException('Insufficient payment amount.');
        }
        $change = round($cashReceived - $grand, 2);
    } else {
        if ($upiStatus !== 'PAID') {
            throw new RuntimeException('Payment is not confirmed as paid.');
        }
        if ($txnRef === '') {
            throw new RuntimeException('Transaction reference is required for UPI/GPay.');
        }
        $cashReceived = $grand;
        $change = 0;
    }

    $cust = null;
    if ($customerId > 0) {
        $c = $pdo->prepare('SELECT id FROM customers WHERE id = ?');
        $c->execute([$customerId]);
        $cust = $c->fetchColumn() ?: null;
    }

    $invoice = generate_invoice_number($pdo);
    $pdo->prepare(
        'INSERT INTO sales (invoice_number, customer_id, biller_id, subtotal, discount, gst, total, cash_received, change_amount, payment_status, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "COMPLETED")'
    )->execute([
        $invoice,
        $cust,
        (int) $_SESSION['user_id'],
        $subtotal,
        $discount,
        $gstTotal,
        $grand,
        $cashReceived,
        $change,
        $payStatus,
    ]);
    $saleId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare(
        'INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, gst, discount, total) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stockStmt = $pdo->prepare('UPDATE products SET stock_quantity = ? WHERE id = ?');
    $moveStmt = $pdo->prepare(
        'INSERT INTO stock_movements (product_id, movement_type, quantity, previous_stock, new_stock, reference_type, reference_id, reason, created_by)
         VALUES (?, "SALE", ?, ?, ?, "sale", ?, "POS sale", ?)'
    );

    foreach ($prepared as $row) {
        $p = $row['product'];
        $calc = $row['calc'];
        $itemStmt->execute([
            $saleId,
            (int) $p['id'],
            $row['qty'],
            $p['selling_price'],
            $calc['gst'],
            $calc['discount'],
            $calc['total'],
        ]);
        $prev = (int) $p['stock_quantity'];
        $new = $prev - $row['qty'];
        $stockStmt->execute([$new, (int) $p['id']]);
        $moveStmt->execute([(int) $p['id'], -$row['qty'], $prev, $new, $saleId, (int) $_SESSION['user_id']]);
    }

    $pdo->prepare(
        'INSERT INTO payments (sale_id, payment_method_id, amount, status, transaction_reference, paid_at)
         VALUES (?, ?, ?, "PAID", ?, ?)'
    )->execute([$saleId, (int) $method['id'], $grand, $txnRef !== '' ? $txnRef : null, $paidAt]);

    $pdo->prepare('INSERT INTO invoices (sale_id, invoice_number, printed_count) VALUES (?, ?, 0)')
        ->execute([$saleId, $invoice]);

    $pdo->prepare(
        'INSERT INTO activity_logs (user_id, role, action, module, reference_id, description, ip_address, user_agent)
         VALUES (?, ?, "SALE_CREATE", "sales", ?, ?, ?, ?)'
    )->execute([
        (int) $_SESSION['user_id'],
        $_SESSION['role'],
        $saleId,
        'Created invoice ' . $invoice,
        client_ip(),
        user_agent(),
    ]);

    $pdo->commit();
    notify((int) $_SESSION['user_id'], 'Bill Generated', 'Invoice ' . $invoice . ' for ' . money_inr($grand), 'success');
    json_response(true, 'Bill generated.', [
        'sale_id' => $saleId,
        'invoice_number' => $invoice,
        'total' => $grand,
        'payment_status' => $payStatus,
        'change' => $change,
    ], 201);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    app_log('Sale create failed: ' . $e->getMessage());
    json_response(false, $e->getMessage(), null, 400);
}
