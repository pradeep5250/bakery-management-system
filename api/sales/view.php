<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_SALES_VIEW);
$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT s.*, u.name AS biller_name, c.name AS customer_name, c.phone AS customer_phone
     FROM sales s
     INNER JOIN users u ON u.id = s.biller_id
     LEFT JOIN customers c ON c.id = s.customer_id
     WHERE s.id = ?'
);
$stmt->execute([$id]);
$sale = $stmt->fetch();
if (!$sale) {
    json_response(false, 'Sale not found.', null, 404);
}
if (is_biller() && ((int) $sale['biller_id'] !== (int) $_SESSION['user_id'] || !biller_can_open_sale((int) $sale['id'], (int) $_SESSION['user_id']))) {
    json_response(false, 'You do not have permission.', null, 403);
}
$items = db()->prepare(
    'SELECT si.*, p.product_name, p.barcode FROM sale_items si INNER JOIN products p ON p.id = si.product_id WHERE si.sale_id = ?'
);
$items->execute([$id]);
$pay = db()->prepare(
    'SELECT p.*, pm.name AS method_name, pm.code AS method_code FROM payments p INNER JOIN payment_methods pm ON pm.id = p.payment_method_id WHERE p.sale_id = ?'
);
$pay->execute([$id]);
$inv = db()->prepare('SELECT * FROM invoices WHERE sale_id = ?');
$inv->execute([$id]);
json_response(true, 'OK', [
    'sale' => $sale,
    'items' => $items->fetchAll(),
    'payments' => $pay->fetchAll(),
    'invoice' => $inv->fetch() ?: null,
]);
