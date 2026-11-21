<?php
declare(strict_types=1);

function render_user_manager(string $roleName, string $title): void
{
    require_role(ROLE_SUPER_ADMIN);
    require_permission(PERM_USER_VIEW);
    $roleId = $roleName === 'biller' ? 3 : 2;
    $stmt = db()->prepare(
        'SELECT u.id, u.name, u.username, u.email, u.phone, u.status, u.last_login, u.created_at
         FROM users u WHERE u.role_id = ? ORDER BY u.name'
    );
    $stmt->execute([$roleId]);
    $rows = $stmt->fetchAll();
    $error = $success = '';
    if (is_post() && verify_csrf_token()) {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'create' && has_permission(PERM_USER_CREATE)) {
                if (strlen((string)$_POST['password']) < 8) {
                    throw new RuntimeException('Password must be at least 8 characters.');
                }
                db()->prepare(
                    'INSERT INTO users (name, username, email, phone, password_hash, role_id, status, force_password_change)
                     VALUES (?, ?, ?, ?, ?, ?, "active", 1)'
                )->execute([
                    trim((string)$_POST['name']),
                    trim((string)$_POST['username']),
                    trim((string)$_POST['email']),
                    trim((string)$_POST['phone']),
                    password_hash((string)$_POST['password'], PASSWORD_DEFAULT),
                    $roleId,
                ]);
                $id = (int) db()->lastInsertId();
                log_activity($roleName === 'biller' ? 'BILLER_CREATE' : 'ADMIN_CREATE', 'users', $id, 'Created user');
                notify_role('super_admin', $roleName === 'biller' ? 'New Biller' : 'New Admin', trim((string)$_POST['name']) . ' was added.', 'info');
                $success = 'User created.';
            } elseif ($action === 'update' && has_permission(PERM_USER_EDIT)) {
                $id = (int) $_POST['id'];
                prevent_manage_super_admin($id);
                $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
                db()->prepare('UPDATE users SET name=?, email=?, phone=?, status=? WHERE id=? AND role_id=?')
                    ->execute([trim((string)$_POST['name']), trim((string)$_POST['email']), trim((string)$_POST['phone']), $status, $id, $roleId]);
                if (!empty($_POST['new_password'])) {
                    if (strlen((string)$_POST['new_password']) < 8) {
                        throw new RuntimeException('Password must be at least 8 characters.');
                    }
                    db()->prepare('UPDATE users SET password_hash=?, force_password_change=1 WHERE id=? AND role_id=?')
                        ->execute([password_hash((string)$_POST['new_password'], PASSWORD_DEFAULT), $id, $roleId]);
                }
                log_activity($status === 'inactive' ? ($roleName === 'biller' ? 'BILLER_DEACTIVATE' : 'ADMIN_DEACTIVATE') : ($roleName === 'biller' ? 'BILLER_UPDATE' : 'ADMIN_UPDATE'), 'users', $id, 'Updated user');
                $success = 'User updated.';
            }
            $stmt->execute([$roleId]);
            $rows = $stmt->fetchAll();
        } catch (Throwable $e) {
            $error = str_contains($e->getMessage(), 'Duplicate') ? 'Username already exists.' : $e->getMessage();
        }
    } elseif (is_post()) {
        $error = 'Invalid security token.';
    }
    $page_title = $title;
    require INCLUDES_PATH . '/header.php';
    ?>
    <main class="app-content">
        <div class="page-head"><div><h1><?= e($title) ?></h1></div></div>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
        <?php if (has_permission(PERM_USER_CREATE)): ?>
        <form class="card-block mb-4" method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="create">
            <div class="row g-2">
                <div class="col-md-3"><input class="form-control" name="name" placeholder="Name" required></div>
                <div class="col-md-2"><input class="form-control" name="username" placeholder="Username" required></div>
                <div class="col-md-2"><input class="form-control" name="email" placeholder="Email"></div>
                <div class="col-md-2"><input class="form-control" name="phone" placeholder="Phone"></div>
                <div class="col-md-2"><input class="form-control" type="password" name="password" placeholder="Password" required minlength="8"></div>
                <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
            </div>
        </form>
        <?php endif; ?>
        <div class="table-wrap"><table class="table table-app"><thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Status</th><th>Last Login</th><th></th></tr></thead><tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['name']) ?></td><td><?= e($r['username']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['phone']) ?></td>
                <td><span class="badge <?= $r['status']==='active'?'badge-ok':'badge-out' ?>"><?= e($r['status']) ?></span></td>
                <td><?= e(format_date($r['last_login'])) ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-dark" data-bs-toggle="collapse" data-bs-target="#u<?= (int)$r['id'] ?>">Edit</button>
                    <a class="btn btn-sm btn-outline-dark" href="<?= e(url('super-admin/login-history.php?user_id=' . $r['id'])) ?>">Logins</a>
                </td>
            </tr>
            <tr class="collapse" id="u<?= (int)$r['id'] ?>"><td colspan="7">
                <form method="post" class="row g-2 p-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <div class="col-md-3"><input class="form-control" name="name" value="<?= e($r['name']) ?>"></div>
                    <div class="col-md-3"><input class="form-control" name="email" value="<?= e($r['email']) ?>"></div>
                    <div class="col-md-2"><input class="form-control" name="phone" value="<?= e($r['phone']) ?>"></div>
                    <div class="col-md-2">
                        <select class="form-select" name="status"><option value="active">Active</option><option value="inactive" <?= $r['status']==='inactive'?'selected':'' ?>>Inactive</option></select>
                    </div>
                    <div class="col-md-2"><input class="form-control" type="password" name="new_password" placeholder="Reset password"></div>
                    <div class="col-12"><button class="btn btn-primary btn-sm">Save</button></div>
                </form>
            </td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </main>
    <?php
    require INCLUDES_PATH . '/footer.php';
}
