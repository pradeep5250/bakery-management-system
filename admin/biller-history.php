<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_ADMIN);
require_permission(PERM_SALES_VIEW);
[$from,$to] = date_range_from_filter($_GET['filter'] ?? 'month', $_GET['from'] ?? null, $_GET['to'] ?? null);
$stmt = db()->prepare(
    "SELECT u.id, u.name, COUNT(s.id) bills, COALESCE(SUM(CASE WHEN s.status='COMPLETED' THEN s.total END),0) total,
            COALESCE(SUM(CASE WHEN s.status='CANCELLED' THEN 1 ELSE 0 END),0) cancelled
     FROM users u INNER JOIN roles r ON r.id=u.role_id
     LEFT JOIN sales s ON s.biller_id=u.id AND DATE(s.created_at) BETWEEN ? AND ?
     WHERE r.name='biller' GROUP BY u.id, u.name"
);
$stmt->execute([$from,$to]);
$rows = $stmt->fetchAll();
$cashStmt = db()->prepare(
    "SELECT s.biller_id, m.code, SUM(p.amount) amt FROM payments p
     INNER JOIN payment_methods m ON m.id=p.payment_method_id
     INNER JOIN sales s ON s.id=p.sale_id
     WHERE s.status='COMPLETED' AND DATE(s.created_at) BETWEEN ? AND ?
     GROUP BY s.biller_id, m.code"
);
$cashStmt->execute([$from,$to]);
$payMap = [];
foreach ($cashStmt->fetchAll() as $p) {
    $payMap[$p['biller_id']][$p['code']] = $p['amt'];
}
$page_title = 'Biller History';
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head"><div><h1>Biller History</h1></div></div>
    <form class="filter-bar" method="get">
        <select class="form-select" name="filter"><option value="today">Today</option><option value="month" selected>This Month</option><option value="custom">Custom</option></select>
        <input type="date" class="form-control" name="from" value="<?= e($from) ?>"><input type="date" class="form-control" name="to" value="<?= e($to) ?>">
        <button class="btn btn-outline-dark">Filter</button>
    </form>
    <div class="table-wrap"><table class="table table-app"><thead><tr><th>Biller</th><th>Bills</th><th>Total</th><th>Cash</th><th>GPay</th><th>Avg</th><th>Cancelled</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): $avg = $r['bills']? $r['total']/$r['bills']:0; ?>
        <tr>
            <td><?= e($r['name']) ?></td>
            <td><?= (int)$r['bills'] ?></td>
            <td><?= e(money_inr($r['total'])) ?></td>
            <td><?= e(money_inr($payMap[$r['id']]['CASH'] ?? 0)) ?></td>
            <td><?= e(money_inr($payMap[$r['id']]['GPAY'] ?? 0)) ?></td>
            <td><?= e(money_inr($avg)) ?></td>
            <td><?= (int)$r['cancelled'] ?></td>
            <td><a class="btn btn-sm btn-outline-dark" href="<?= e(url('admin/sales.php?q='.urlencode($r['name']))) ?>">View Details</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
