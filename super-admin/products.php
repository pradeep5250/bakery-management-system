<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_SUPER_ADMIN);
require_permission(PERM_PRODUCT_VIEW);
$q = trim((string) ($_GET['q'] ?? ''));
$cat = (int) ($_GET['category_id'] ?? 0);
$status = $_GET['status'] ?? '';
$cats = db()->query('SELECT id, name FROM categories WHERE status="active" ORDER BY name')->fetchAll();
$sql = 'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (p.product_name LIKE ? OR p.barcode LIKE ? OR p.id = ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = ctype_digit($q) ? (int) $q : 0;
}
if ($cat) { $sql .= ' AND p.category_id = ?'; $params[] = $cat; }
if (in_array($status, ['active','inactive'], true)) { $sql .= ' AND p.status = ?'; $params[] = $status; }
$sql .= ' ORDER BY p.product_name';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$page_title = 'Products';
$extra_js = [asset('js/products.js')];
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head">
        <div><h1>Products</h1><p>Add, search, filter and manage bakery items.</p></div>
        <?php if (has_permission(PERM_PRODUCT_CREATE)): ?>
            <a class="btn btn-primary" href="<?= e(url('super-admin/add-product.php')) ?>">Add Product</a>
        <?php endif; ?>
    </div>
    <form class="filter-bar" method="get" id="productFilter">
        <input class="form-control" name="q" id="liveSearch" value="<?= e($q) ?>" placeholder="Name, barcode or ID">
        <select class="form-select" name="category_id">
            <option value="">All categories</option>
            <?php foreach ($cats as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $cat===$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-select" name="status">
            <option value="">All status</option>
            <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
            <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
        </select>
        <button class="btn btn-outline-dark">Filter</button>
    </form>
    <div class="table-wrap">
        <table class="table table-app" id="productTable">
            <thead><tr><th></th><th>Product</th><th>Barcode</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): $st = stock_status((int)$r['stock_quantity'], (int)$r['minimum_stock']); ?>
                <tr>
                    <td><img class="product-thumb" src="<?= e(product_image_url($r['product_image'] ?? null)) ?>" alt=""></td>
                    <td><?= e($r['product_name']) ?></td>
                    <td><?= e($r['barcode']) ?></td>
                    <td><?= e($r['category_name']) ?></td>
                    <td><?= e(money_inr($r['selling_price'])) ?></td>
                    <td><?= (int)$r['stock_quantity'] ?> <span class="badge <?= e(stock_status_class($st)) ?>"><?= e($st) ?></span></td>
                    <td><?= e($r['status']) ?></td>
                    <td class="text-nowrap">
                        <?php if (has_permission(PERM_PRODUCT_EDIT)): ?>
                            <a class="btn btn-sm btn-outline-dark" href="<?= e(url('super-admin/edit-product.php?id=' . $r['id'])) ?>">Edit</a>
                        <?php endif; ?>
                        <?php if (has_permission(PERM_PRODUCT_DELETE)): ?>
                            <button class="btn btn-sm btn-outline-danger" data-deactivate="<?= (int)$r['id'] ?>" data-name="<?= e($r['product_name']) ?>"><?= $r['status']==='active'?'Deactivate':'Activate' ?></button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">No products found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
