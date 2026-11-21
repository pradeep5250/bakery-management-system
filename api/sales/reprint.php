<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_BILL_REPRINT);
api_require_csrf();
$data = api_post_data();
$id = (int) ($data['id'] ?? 0);
$stmt = db()->prepare('SELECT s.*, i.id AS invoice_id FROM sales s INNER JOIN invoices i ON i.sale_id = s.id WHERE s.id = ?');
$stmt->execute([$id]);
$sale = $stmt->fetch();
if (!$sale) {
    json_response(false, 'Invoice not found.', null, 404);
}
if (is_biller() && (int) $sale['biller_id'] !== (int) $_SESSION['user_id'] && !has_permission(PERM_BILL_REPRINT)) {
    json_response(false, 'You do not have permission.', null, 403);
}
db()->prepare('UPDATE invoices SET printed_count = printed_count + 1, last_printed_at = NOW() WHERE sale_id = ?')->execute([$id]);
log_activity('SALE_REPRINT', 'sales', $id, 'Reprinted ' . $sale['invoice_number']);
json_response(true, 'Reprint logged.', ['invoice_number' => $sale['invoice_number']]);
