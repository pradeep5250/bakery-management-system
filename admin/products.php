<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_ADMIN);
require_permission(PERM_PRODUCT_VIEW);
$q = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (p.product_name LIKE ? OR p.barcode LIKE ? OR p.id=?)';
    $params = ['%'.$q.'%','%'.$q.'%', ctype_digit($q)?(int)$q:0];
}
$stmt = db()->prepare($sql.' ORDER BY p.product_name');
$stmt->execute($params);
$rows = $stmt->fetchAll();
$page_title = 'Products';
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head"><div><h1>Products</h1><p>View and search bakery products.</p></div></div>
    <form class="filter-bar" method="get"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Name, barcode or ID"><button class="btn btn-outline-dark">Search</button></form>
    <div class="table-wrap"><table class="table table-app"><thead><tr><th></th><th>Product</th><th>Barcode</th><th>Category</th><th>Price</th><th>Stock</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): $st=stock_status((int)$r['stock_quantity'],(int)$r['minimum_stock']); ?>
        <tr>
            <td><img class="product-thumb" src="<?= e(product_image_url($r['product_image'] ?? null)) ?>" alt=""></td>
            <td><?= e($r['product_name']) ?></td><td><?= e($r['barcode']) ?></td><td><?= e($r['category_name']) ?></td>
            <td><?= e(money_inr($r['selling_price'])) ?></td>
            <td><?= (int)$r['stock_quantity'] ?> <span class="badge <?= e(stock_status_class($st)) ?>"><?= e($st) ?></span></td>
            <td><a class="btn btn-sm btn-outline-dark" href="<?= e(url('admin/product-view.php?id='.$r['id'])) ?>">View</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
