<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_ADMIN);
require_permission(PERM_PRODUCT_VIEW);
$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=?');
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { http_response_code(404); exit('Not found'); }
$page_title = 'Product';
require INCLUDES_PATH . '/header.php';
$st = stock_status((int)$p['stock_quantity'], (int)$p['minimum_stock']);
?>
<main class="app-content">
    <div class="page-head"><div><h1><?= e($p['product_name']) ?></h1></div><a class="btn btn-outline-dark" href="<?= e(url('admin/products.php')) ?>">Back</a></div>
    <div class="card-block" style="max-width:640px">
        <img class="product-photo mb-3" src="<?= e(product_image_url($p['product_image'] ?? null)) ?>" alt="<?= e($p['product_name']) ?>">
        <p>Barcode: <?= e($p['barcode']) ?></p>
        <p>Category: <?= e($p['category_name']) ?></p>
        <p>Selling price: <?= e(money_inr($p['selling_price'])) ?></p>
        <p>GST: <?= e($p['gst']) ?>%</p>
        <p>Stock: <?= (int)$p['stock_quantity'] ?> <span class="badge <?= e(stock_status_class($st)) ?>"><?= e($st) ?></span></p>
        <p><?= e($p['description']) ?></p>
    </div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
