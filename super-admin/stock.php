<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_SUPER_ADMIN);
require_permission(PERM_STOCK_VIEW);
$q = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT * FROM products WHERE 1=1';
$params = [];
if ($q !== '') { $sql .= ' AND (product_name LIKE ? OR barcode LIKE ?)'; $params = ['%'.$q.'%','%'.$q.'%']; }
$sql .= ' ORDER BY product_name';
$stmt = db()->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();
$page_title = 'Stock';
$extra_js = [asset('js/stock.js')];
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head"><div><h1>Stock</h1><p>Add, reduce or adjust inventory. Every change is recorded.</p></div>
        <a class="btn btn-outline-dark" href="<?= e(url('super-admin/stock-history.php')) ?>">Stock History</a>
    </div>
    <form class="filter-bar" method="get"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Product or barcode"><button class="btn btn-outline-dark">Search</button></form>
    <div class="table-wrap"><table class="table table-app"><thead><tr><th>Product</th><th>Barcode</th><th>Current</th><th>Minimum</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): $st = stock_status((int)$r['stock_quantity'], (int)$r['minimum_stock']); ?>
        <tr>
            <td><?= e($r['product_name']) ?></td>
            <td><?= e($r['barcode']) ?></td>
            <td><?= (int)$r['stock_quantity'] ?></td>
            <td><?= (int)$r['minimum_stock'] ?></td>
            <td><span class="badge <?= e(stock_status_class($st)) ?>"><?= e($st) ?></span></td>
            <td>
                <?php if (has_permission(PERM_STOCK_ADJUST)): ?>
                <button class="btn btn-sm btn-primary" data-stock="<?= (int)$r['id'] ?>" data-name="<?= e($r['product_name']) ?>">Adjust</button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</main>
<div class="modal fade" id="stockModal"><div class="modal-dialog"><form class="modal-content" id="stockForm">
    <div class="modal-header"><h5 class="modal-title">Adjust Stock</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" id="stockPid">
        <p id="stockPname" class="fw-semibold"></p>
        <label class="form-label">Type</label>
        <select class="form-select mb-2" name="movement_type">
            <option value="PURCHASE">Add Stock (Purchase)</option>
            <option value="ADJUSTMENT">Adjust</option>
            <option value="DAMAGE">Damage</option>
            <option value="EXPIRED">Expired</option>
            <option value="RETURN">Return</option>
        </select>
        <label class="form-label">Quantity</label>
        <input class="form-control mb-2" type="number" name="quantity" required>
        <label class="form-label">Reason</label>
        <input class="form-control" name="reason">
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
</form></div></div>
<?php require INCLUDES_PATH . '/footer.php'; ?>
