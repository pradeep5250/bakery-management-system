<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_ADMIN);
$page_title = 'Dashboard';
$today = date('Y-m-d');
$pdo = db();
$stats = [
    'Today\'s Sales' => money_inr($pdo->query("SELECT COALESCE(SUM(total),0) FROM sales WHERE status='COMPLETED' AND DATE(created_at)='$today'")->fetchColumn()),
    'Today\'s Bills' => $pdo->query("SELECT COUNT(*) FROM sales WHERE status='COMPLETED' AND DATE(created_at)='$today'")->fetchColumn(),
    'Products' => $pdo->query("SELECT COUNT(*) FROM products WHERE status='active'")->fetchColumn(),
    'Low Stock' => $pdo->query("SELECT COUNT(*) FROM products WHERE status='active' AND stock_quantity>0 AND stock_quantity<=minimum_stock")->fetchColumn(),
    'Out of Stock' => $pdo->query("SELECT COUNT(*) FROM products WHERE status='active' AND stock_quantity=0")->fetchColumn(),
];
$low = $pdo->query("SELECT product_name, stock_quantity FROM products WHERE status='active' AND stock_quantity>0 AND stock_quantity<=minimum_stock ORDER BY stock_quantity LIMIT 8")->fetchAll();
$bills = $pdo->query("SELECT invoice_number, total, created_at FROM sales ORDER BY id DESC LIMIT 8")->fetchAll();
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head"><div><h1>Admin Dashboard</h1><p>Products, stock, barcode, sales and reports.</p></div></div>
    <div class="stat-grid">
        <?php foreach ($stats as $l=>$v): ?><div class="stat-card"><span><?= e($l) ?></span><strong><?= e((string)$v) ?></strong></div><?php endforeach; ?>
    </div>
    <div class="row g-4">
        <div class="col-lg-6"><div class="card-block"><h2 class="h5">Low Stock</h2><ul class="list-compact"><?php foreach ($low as $r): ?><li><span><?= e($r['product_name']) ?></span><strong><?= (int)$r['stock_quantity'] ?></strong></li><?php endforeach; if(!$low): ?><li>All good.</li><?php endif; ?></ul></div></div>
        <div class="col-lg-6"><div class="card-block"><h2 class="h5">Recent Bills</h2><ul class="list-compact"><?php foreach ($bills as $r): ?><li><span><?= e($r['invoice_number']) ?></span><strong><?= e(money_inr($r['total'])) ?></strong></li><?php endforeach; ?></ul></div></div>
    </div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
