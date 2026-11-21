<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_SUPER_ADMIN, ROLE_ADMIN);
require_permission(PERM_REPORT_VIEW);
$filter = $_GET['filter'] ?? 'month';
$type = $_GET['type'] ?? 'daily';
[$from, $to] = date_range_from_filter($filter, $_GET['from'] ?? null, $_GET['to'] ?? null);
$export = $_GET['export'] ?? '';
$pdo = db();

$queries = [
    'daily' => ["SELECT DATE(created_at) period, COUNT(*) bills, SUM(total) total FROM sales WHERE status='COMPLETED' AND DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at)", ['Period','Bills','Total']],
    'product' => ["SELECT p.product_name period, SUM(si.quantity) bills, SUM(si.total) total FROM sale_items si INNER JOIN products p ON p.id=si.product_id INNER JOIN sales s ON s.id=si.sale_id WHERE s.status='COMPLETED' AND DATE(s.created_at) BETWEEN ? AND ? GROUP BY p.id, p.product_name ORDER BY bills DESC", ['Product','Qty','Total']],
    'biller' => ["SELECT u.name period, COUNT(s.id) bills, COALESCE(SUM(CASE WHEN s.status='COMPLETED' THEN s.total END),0) total FROM users u INNER JOIN roles r ON r.id=u.role_id LEFT JOIN sales s ON s.biller_id=u.id AND DATE(s.created_at) BETWEEN ? AND ? WHERE r.name='biller' GROUP BY u.id, u.name", ['Biller','Bills','Total']],
    'payment' => ["SELECT m.name period, COUNT(p.id) bills, COALESCE(SUM(p.amount),0) total FROM payment_methods m LEFT JOIN payments p ON p.payment_method_id=m.id AND p.status='PAID' LEFT JOIN sales s ON s.id=p.sale_id AND s.status='COMPLETED' AND DATE(s.created_at) BETWEEN ? AND ? GROUP BY m.id, m.name", ['Method','Bills','Total']],
    'cancelled' => ["SELECT invoice_number period, 1 bills, total FROM sales WHERE status='CANCELLED' AND DATE(created_at) BETWEEN ? AND ?", ['Invoice',' ','Total']],
    'stock' => ["SELECT product_name period, stock_quantity bills, minimum_stock total FROM products WHERE status='active' ORDER BY product_name", ['Product','Stock','Minimum']],
    'low' => ["SELECT product_name period, stock_quantity bills, minimum_stock total FROM products WHERE status='active' AND stock_quantity>0 AND stock_quantity<=minimum_stock", ['Product','Stock','Minimum']],
    'out' => ["SELECT product_name period, stock_quantity bills, 0 total FROM products WHERE status='active' AND stock_quantity=0", ['Product','Stock',' ']],
];
$key = array_key_exists($type, $queries) ? $type : 'daily';
[$sql, $headers] = $queries[$key];
if (str_contains($sql, '?')) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$from, $to]);
    $rows = $stmt->fetchAll();
} else {
    $rows = $pdo->query($sql)->fetchAll();
}
if ($export === 'csv' && has_permission(PERM_REPORT_EXPORT)) {
    $out = [];
    foreach ($rows as $r) {
        $out[] = [$r['period'], $r['bills'], $r['total']];
    }
    csv_download('report-' . $key . '.csv', $headers, $out);
}
$page_title = 'Reports';
$extra_js = [asset('js/reports.js')];
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head"><div><h1>Reports</h1></div>
        <?php if (has_permission(PERM_REPORT_EXPORT)): ?>
            <a class="btn btn-outline-dark" href="?type=<?= e($type) ?>&filter=<?= e($filter) ?>&from=<?= e($from) ?>&to=<?= e($to) ?>&export=csv">Export CSV</a>
            <button class="btn btn-primary" onclick="window.print()">Print</button>
        <?php endif; ?>
    </div>
    <form class="filter-bar" method="get">
        <select class="form-select" name="type">
            <?php foreach (['daily'=>'Daily Sales','product'=>'Product Sales','biller'=>'Biller Sales','payment'=>'Payment Report','cancelled'=>'Cancelled Bills','stock'=>'Stock Report','low'=>'Low Stock','out'=>'Out of Stock'] as $k=>$l): ?>
                <option value="<?= $k ?>" <?= $type===$k?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-select" name="filter">
            <?php foreach (['today'=>'Today','yesterday'=>'Yesterday','last7'=>'Last 7 Days','month'=>'This Month','last_month'=>'Last Month','custom'=>'Custom'] as $k=>$l): ?>
                <option value="<?= $k ?>" <?= $filter===$k?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" class="form-control" name="from" value="<?= e($from) ?>">
        <input type="date" class="form-control" name="to" value="<?= e($to) ?>">
        <button class="btn btn-outline-dark">Apply</button>
    </form>
    <div class="table-wrap print-a4"><table class="table table-app"><thead><tr><?php foreach ($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead><tbody>
    <?php $sum=0; foreach ($rows as $r): $sum += (float)$r['total']; ?>
        <tr><td><?= e((string)$r['period']) ?></td><td><?= e((string)$r['bills']) ?></td><td><?= is_numeric($r['total']) ? e(money_inr($r['total'])) : e((string)$r['total']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="3" class="text-center text-muted py-4">No data for this range.</td></tr><?php endif; ?>
    </tbody></table></div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
