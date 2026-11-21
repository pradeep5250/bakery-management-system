<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_SUPER_ADMIN);
require_permission(PERM_PAYMENT_VIEW);
[$from, $to] = date_range_from_filter($_GET['filter'] ?? 'month', $_GET['from'] ?? null, $_GET['to'] ?? null);
$stmt = db()->prepare(
    "SELECT m.id, m.name, m.code, m.status, COUNT(p.id) bills, COALESCE(SUM(CASE WHEN s.status='COMPLETED' THEN p.amount END),0) total
     FROM payment_methods m
     LEFT JOIN payments p ON p.payment_method_id = m.id AND p.status='PAID'
     LEFT JOIN sales s ON s.id = p.sale_id AND DATE(s.created_at) BETWEEN ? AND ?
     GROUP BY m.id, m.name, m.code, m.status"
);
$stmt->execute([$from, $to]);
$rows = $stmt->fetchAll();
if (is_post() && verify_csrf_token() && has_permission(PERM_PAYMENT_CONFIG)) {
    db()->prepare('UPDATE payment_methods SET status=? WHERE id=?')->execute([
        $_POST['status'] === 'active' ? 'active' : 'inactive',
        (int) $_POST['id'],
    ]);
    log_activity('PAYMENT_CONFIG', 'payments', (int)$_POST['id'], 'Updated payment method');
    redirect('super-admin/payments.php');
}
$page_title = 'Payments';
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head"><div><h1>Payments</h1><p><?= e(format_date($from,'date')) ?> – <?= e(format_date($to,'date')) ?></p></div></div>
    <div class="row g-3 mb-4">
        <?php foreach ($rows as $r): ?>
            <div class="col-md-4"><div class="stat-card">
                <span><?= e($r['name']) ?></span>
                <strong><?= e(money_inr($r['total'])) ?></strong>
                <small><?= (int)$r['bills'] ?> bills · <?= e($r['status']) ?></small>
                <?php if (has_permission(PERM_PAYMENT_CONFIG) && !in_array($r['code'], ['CASH'], true)): ?>
                <form method="post" class="mt-2"><?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <input type="hidden" name="status" value="<?= $r['status']==='active'?'inactive':'active' ?>">
                    <button class="btn btn-sm btn-outline-dark"><?= $r['status']==='active'?'Disable':'Enable' ?></button>
                </form>
                <?php endif; ?>
            </div></div>
        <?php endforeach; ?>
    </div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
