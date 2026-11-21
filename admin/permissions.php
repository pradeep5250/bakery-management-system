<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_ADMIN);
require_any_permission(PERM_USER_EDIT, PERM_SALES_VIEW);

$role = db()->query("SELECT * FROM roles WHERE name='biller' LIMIT 1")->fetch();
$perms = db()->query("SELECT * FROM permissions WHERE permission_name NOT IN ('SETTINGS_VIEW','SETTINGS_EDIT') ORDER BY module, permission_name")->fetchAll();
$rp = db()->prepare('SELECT permission_id FROM role_permissions WHERE role_id=?');
$rp->execute([(int) $role['id']]);
$map = [];
foreach ($rp->fetchAll(PDO::FETCH_COLUMN) as $pid) {
    $map[(int) $pid] = true;
}
$msg = '';
if (is_post() && verify_csrf_token()) {
    $ids = array_map('intval', $_POST['permission_ids'] ?? []);
    $pdo = db();
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM role_permissions WHERE role_id=?')->execute([(int) $role['id']]);
    $ins = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?,?)');
    foreach ($ids as $pid) {
        $ins->execute([(int) $role['id'], $pid]);
    }
    $pdo->commit();
    $limit = max(1, min(100, (int) ($_POST['biller_history_limit'] ?? 5)));
    save_app_setting('biller_history_limit', (string) $limit);
    log_activity('PERMISSION_UPDATE', 'permissions', (int) $role['id'], 'Updated biller permissions');
    $msg = 'Biller permissions updated.';
    $rp->execute([(int) $role['id']]);
    $map = [];
    foreach ($rp->fetchAll(PDO::FETCH_COLUMN) as $pid) {
        $map[(int) $pid] = true;
    }
}
$page_title = 'Permissions';
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head"><div><h1>Permissions</h1><p>Choose what the Biller can do, including billing history.</p></div></div>
    <?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
    <form class="card-block" method="post" style="max-width:760px">
        <?= csrf_field() ?>
        <h2 class="h5">Biller</h2>
        <?php render_permission_checkboxes($perms, $map, ['SETTINGS_VIEW', 'SETTINGS_EDIT']); ?>
        <div class="mt-3 pt-3 border-top">
            <label class="form-label" for="billerHistoryLimit">History invoice limit</label>
            <input class="form-control" id="billerHistoryLimit" type="number" name="biller_history_limit" min="1" max="100" value="<?= e((string) biller_history_limit()) ?>">
            <div class="form-text">Turn on “View billing history”, then set how many latest invoices the biller can see. Click Save Biller.</div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">Save Biller</button>
    </form>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
