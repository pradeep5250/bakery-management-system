<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_ADMIN);
require_permission(PERM_PRINT_CONTROL);
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    redirect('biller/invoice.php?id=' . $id);
}
$page_title = 'Print';
require INCLUDES_PATH . '/header.php';
$rows = db()->query('SELECT id, invoice_number, total, created_at FROM sales ORDER BY id DESC LIMIT 50')->fetchAll();
?>
<main class="app-content">
    <div class="page-head"><div><h1>Print Center</h1><p>Open an invoice to print or reprint.</p></div></div>
    <div class="table-wrap"><table class="table table-app"><thead><tr><th>Invoice</th><th>Date</th><th>Total</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['invoice_number']) ?></td>
            <td><?= e(format_date($r['created_at'])) ?></td>
            <td><?= e(money_inr($r['total'])) ?></td>
            <td><a class="btn btn-sm btn-primary" href="<?= e(url('biller/invoice.php?id='.$r['id'])) ?>">Print</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
