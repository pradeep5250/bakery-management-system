<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_login();
if (is_biller()) {
    require_any_permission(PERM_BILL_PRINT, PERM_BILL_CREATE, PERM_BILL_HISTORY_VIEW, PERM_SALES_VIEW);
} else {
    require_permission(PERM_SALES_VIEW);
}
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0 && is_biller()) {
    $latest = biller_recent_sale_ids((int) $_SESSION['user_id'], has_permission(PERM_BILL_HISTORY_VIEW) ? biller_history_limit() : 1);
    if ($latest) {
        redirect('biller/invoice.php?id=' . $latest[0]);
    }
    redirect('biller/pos.php');
}
$stmt = db()->prepare(
    'SELECT s.*, u.name AS biller_name, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address
     FROM sales s INNER JOIN users u ON u.id=s.biller_id LEFT JOIN customers c ON c.id=s.customer_id WHERE s.id=?'
);
$stmt->execute([$id]);
$sale = $stmt->fetch();
if (!$sale) {
    http_response_code(404);
    echo 'Invoice not found.';
    exit;
}
if (is_biller()) {
    if ((int) $sale['biller_id'] !== (int) $_SESSION['user_id'] || !biller_can_open_sale((int) $sale['id'], (int) $_SESSION['user_id'])) {
        http_response_code(403);
        exit('You do not have permission to view this invoice.');
    }
}
$items = db()->prepare('SELECT si.*, p.product_name, p.barcode FROM sale_items si INNER JOIN products p ON p.id=si.product_id WHERE si.sale_id=?');
$items->execute([$id]);
$items = $items->fetchAll();
$pay = db()->prepare('SELECT p.*, m.name method_name FROM payments p INNER JOIN payment_methods m ON m.id=p.payment_method_id WHERE p.sale_id=?');
$pay->execute([$id]);
$pay = $pay->fetch();
$inv = db()->prepare('SELECT * FROM invoices WHERE sale_id=?');
$inv->execute([$id]);
$inv = $inv->fetch();
$format = $_GET['format'] ?? get_setting('printer_type', 'a4');
$page_title = 'Invoice ' . $sale['invoice_number'];
$hide_chrome = isset($_GET['print']);
require INCLUDES_PATH . '/header.php';
$logo = get_setting('logo');
?>
<main class="app-content">
    <div class="no-print page-head">
        <div><h1>Invoice <?= e($sale['invoice_number']) ?></h1></div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-dark" href="?id=<?= $id ?>&format=a4">A4</a>
            <a class="btn btn-outline-dark" href="?id=<?= $id ?>&format=thermal">80mm</a>
            <?php if (has_permission(PERM_BILL_PRINT) || has_permission(PERM_BILL_REPRINT)): ?>
                <button class="btn btn-primary" id="printInv">Print</button>
            <?php endif; ?>
            <?php if (has_permission(PERM_BILL_CANCEL) && $sale['status']==='COMPLETED'): ?>
                <button class="btn btn-outline-danger" id="cancelInv">Cancel Bill</button>
            <?php endif; ?>
        </div>
    </div>
    <article class="<?= $format === 'thermal' ? 'print-thermal card-block' : 'print-a4 card-block' ?>" id="invoiceSheet">
        <header class="text-center mb-3">
            <?php if ($logo): ?><img src="<?= e(UPLOADS_URL.$logo) ?>" alt="" style="max-height:64px"><?php endif; ?>
            <h2><?= e(bakery_name()) ?></h2>
            <p class="mb-0"><?= e(get_setting('address')) ?></p>
            <p class="mb-0"><?= e(get_setting('phone')) ?> · <?= e(get_setting('email')) ?></p>
            <?php if (get_setting('gst_number')): ?><p>GSTIN: <?= e(get_setting('gst_number')) ?></p><?php endif; ?>
        </header>
        <p><strong>Invoice:</strong> <?= e($sale['invoice_number']) ?><br>
        <strong>Date:</strong> <?= e(format_date($sale['created_at'], 'date')) ?>
        <strong>Time:</strong> <?= e(format_date($sale['created_at'], 'time')) ?><br>
        <strong>Biller:</strong> <?= e($sale['biller_name']) ?><br>
        <strong>Customer:</strong> <?= e($sale['customer_name'] ?: 'Walk-in Customer') ?></p>
        <table class="table table-sm">
            <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>GST</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= e($it['product_name']) ?></td>
                    <td><?= (int)$it['quantity'] ?></td>
                    <td><?= e(money_inr($it['unit_price'])) ?></td>
                    <td><?= e(money_inr($it['gst'])) ?></td>
                    <td><?= e(money_inr($it['total'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p>Subtotal: <?= e(money_inr($sale['subtotal'])) ?><br>
        Discount: <?= e(money_inr($sale['discount'])) ?><br>
        GST: <?= e(money_inr($sale['gst'])) ?><br>
        <strong>GRAND TOTAL: <?= e(money_inr($sale['total'])) ?></strong></p>
        <p>Payment: <?= e($pay['method_name'] ?? '—') ?> · <?= e($sale['payment_status']) ?>
        <?php if ($sale['cash_received'] !== null): ?><br>Cash received: <?= e(money_inr($sale['cash_received'])) ?> · Change: <?= e(money_inr($sale['change_amount'])) ?><?php endif; ?></p>
        <?php if ($sale['status']==='CANCELLED'): ?><p class="text-danger">CANCELLED: <?= e($sale['cancel_reason']) ?></p><?php endif; ?>
        <p class="text-center mt-4"><?= e(get_setting('thank_you_message', 'Thank You')) ?><br><?= e(get_setting('receipt_footer', 'Visit Again')) ?></p>
    </article>
</main>
<script>
document.getElementById('printInv')?.addEventListener('click', async () => {
    await fetch(APP.baseUrl + 'api/sales/reprint.php', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':APP.csrf},
        body: JSON.stringify({id: <?= $id ?>, csrf_token: APP.csrf})
    });
    window.print();
});
document.getElementById('cancelInv')?.addEventListener('click', async () => {
    const reason = prompt('Cancel bill reason?');
    if (!reason) return;
    const res = await fetch(APP.baseUrl + 'api/sales/cancel.php', {
        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':APP.csrf},
        body: JSON.stringify({id: <?= $id ?>, reason, csrf_token: APP.csrf})
    });
    const json = await res.json();
    alert(json.message);
    if (json.success) location.reload();
});
</script>
<?php require INCLUDES_PATH . '/footer.php'; ?>
