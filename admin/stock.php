<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_ADMIN);
require_permission(PERM_STOCK_VIEW);
$rows = db()->query('SELECT * FROM products ORDER BY product_name')->fetchAll();
$page_title = 'Stock';
$extra_js = [asset('js/stock.js')];
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head"><div><h1>Stock</h1></div><a class="btn btn-outline-dark" href="<?= e(url('admin/stock-history.php')) ?>">History</a></div>
    <div class="table-wrap"><table class="table table-app"><thead><tr><th>Product</th><th>Barcode</th><th>Current</th><th>Min</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): $st=stock_status((int)$r['stock_quantity'],(int)$r['minimum_stock']); ?>
        <tr>
            <td><?= e($r['product_name']) ?></td><td><?= e($r['barcode']) ?></td>
            <td><?= (int)$r['stock_quantity'] ?></td><td><?= (int)$r['minimum_stock'] ?></td>
            <td><span class="badge <?= e(stock_status_class($st)) ?>"><?= e($st) ?></span></td>
            <td><?php if (has_permission(PERM_STOCK_ADJUST)): ?><button class="btn btn-sm btn-primary" data-stock="<?= (int)$r['id'] ?>" data-name="<?= e($r['product_name']) ?>">Adjust</button><?php endif; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</main>
<div class="modal fade" id="stockModal"><div class="modal-dialog"><form class="modal-content" id="stockForm">
    <div class="modal-header"><h5>Adjust Stock</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><?= csrf_field() ?><input type="hidden" name="product_id" id="stockPid"><p id="stockPname"></p>
        <select class="form-select mb-2" name="movement_type"><option value="PURCHASE">Add</option><option value="ADJUSTMENT">Adjust</option><option value="DAMAGE">Damage</option></select>
        <input class="form-control mb-2" type="number" name="quantity" required>
        <input class="form-control" name="reason" placeholder="Reason">
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
</form></div></div>
<?php require INCLUDES_PATH . '/footer.php'; ?>
