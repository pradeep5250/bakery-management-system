<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_ADMIN);
require_permission(PERM_STOCK_VIEW);
$rows = db()->query('SELECT sm.*, p.product_name, u.name AS user_name FROM stock_movements sm INNER JOIN products p ON p.id=sm.product_id LEFT JOIN users u ON u.id=sm.created_by ORDER BY sm.id DESC LIMIT 200')->fetchAll();
$page_title = 'Stock History';
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head"><div><h1>Stock History</h1></div></div>
    <div class="table-wrap"><table class="table table-app"><thead><tr><th>Time</th><th>Product</th><th>Type</th><th>Qty</th><th>Prev</th><th>New</th><th>User</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
        <tr><td><?= e(format_date($r['created_at'])) ?></td><td><?= e($r['product_name']) ?></td><td><?= e($r['movement_type']) ?></td><td><?= (int)$r['quantity'] ?></td><td><?= (int)$r['previous_stock'] ?></td><td><?= (int)$r['new_stock'] ?></td><td><?= e($r['user_name']) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
