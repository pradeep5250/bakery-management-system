<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_SUPER_ADMIN);
$roles = db()->query('SELECT * FROM roles WHERE id IN (2,3)')->fetchAll();
$perms = db()->query('SELECT * FROM permissions ORDER BY module, permission_name')->fetchAll();
$rp = db()->query('SELECT role_id, permission_id FROM role_permissions')->fetchAll();
$map = [];
foreach ($rp as $row) {
    $map[$row['role_id']][(int) $row['permission_id']] = true;
}
$msg = '';
if (is_post() && verify_csrf_token()) {
    $roleId = (int) $_POST['role_id'];
    if (in_array($roleId, [2, 3], true)) {
        $ids = array_map('intval', $_POST['permission_ids'] ?? []);
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM role_permissions WHERE role_id=?')->execute([$roleId]);
        $ins = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?,?)');
        foreach ($ids as $pid) {
            $ins->execute([$roleId, $pid]);
        }
        $pdo->commit();
        if ($roleId === 3 && isset($_POST['biller_history_limit'])) {
            $limit = max(1, min(100, (int) $_POST['biller_history_limit']));
            save_app_setting('biller_history_limit', (string) $limit);
        }
        log_activity('PERMISSION_UPDATE', 'permissions', $roleId, 'Updated role permissions');
        $msg = 'Permissions updated.';
        $rp = db()->query('SELECT role_id, permission_id FROM role_permissions')->fetchAll();
        $map = [];
        foreach ($rp as $row) {
            $map[$row['role_id']][(int) $row['permission_id']] = true;
        }
    }
}
$page_title = 'Permissions';
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head"><div><h1>Permissions</h1><p>Super Admin always has full access. Assign operational rights to Admin and Biller.</p></div></div>
    <?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
    <div class="row g-4">
        <?php foreach ($roles as $role): ?>
        <div class="col-lg-6">
            <form class="card-block" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="role_id" value="<?= (int)$role['id'] ?>">
                <h2 class="h5"><?= e($role['display_name']) ?></h2>
                <?php
                $checked = $map[$role['id']] ?? [];
                $hide = $role['name'] === 'biller' ? ['SETTINGS_VIEW', 'SETTINGS_EDIT'] : [];
                render_permission_checkboxes($perms, $checked, $hide);
                ?>
                <?php if ($role['name'] === 'biller'): ?>
                    <div class="mt-3 pt-3 border-top">
                        <label class="form-label" for="billerHistoryLimit">History invoice limit</label>
                        <input class="form-control" id="billerHistoryLimit" type="number" name="biller_history_limit" min="1" max="100" value="<?= e((string) biller_history_limit()) ?>">
                        <div class="form-text">Turn on “View billing history”, then set how many latest invoices the biller can see (example: 5). Click Save Biller.</div>
                    </div>
                <?php endif; ?>
                <button class="btn btn-primary mt-3" type="submit">Save <?= e($role['display_name']) ?></button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
