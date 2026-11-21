<?php
declare(strict_types=1);

function render_profile_page(): void
{
    require_login();
    $user = current_user();
    $error = '';
    $success = '';
    $force = isset($_GET['force']);

    if (is_post()) {
        if (!verify_csrf_token()) {
            $error = 'Invalid security token.';
        } else {
            $action = $_POST['action'] ?? 'profile';
            try {
                if ($action === 'profile') {
                    $name = trim((string) ($_POST['name'] ?? ''));
                    $email = trim((string) ($_POST['email'] ?? ''));
                    $phone = trim((string) ($_POST['phone'] ?? ''));
                    if ($name === '') {
                        throw new RuntimeException('Name is required.');
                    }
                    db()->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?')
                        ->execute([$name, $email, $phone, (int) $user['id']]);
                    $_SESSION['name'] = $name;
                    log_activity('PROFILE_UPDATE', 'users', (int) $user['id'], 'Updated profile');
                    $success = 'Profile updated.';
                    $user = current_user(true);
                } elseif ($action === 'password') {
                    $current = (string) ($_POST['current_password'] ?? '');
                    $new = (string) ($_POST['new_password'] ?? '');
                    $confirm = (string) ($_POST['confirm_password'] ?? '');
                    if (!password_verify($current, $user['password_hash'])) {
                        throw new RuntimeException('Current password is incorrect.');
                    }
                    if (strlen($new) < 8) {
                        throw new RuntimeException('Password must be at least 8 characters.');
                    }
                    if ($new !== $confirm) {
                        throw new RuntimeException('New passwords do not match.');
                    }
                    db()->prepare('UPDATE users SET password_hash = ?, force_password_change = 0 WHERE id = ?')
                        ->execute([password_hash($new, PASSWORD_DEFAULT), (int) $user['id']]);
                    log_activity('PASSWORD_CHANGE', 'auth', (int) $user['id'], 'Changed password');
                    $success = 'Password changed successfully.';
                }
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }
        $user = current_user(true);
    }

    $page_title = 'Profile';
    require INCLUDES_PATH . '/header.php';
    ?>
    <main class="app-content">
        <div class="page-head">
            <div>
                <h1>Profile</h1>
                <p>Manage your account details and password.</p>
            </div>
        </div>
        <?php if ($force): ?><div class="alert alert-warning">You must change your password before continuing.</div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card-block">
                    <h2 class="h5 mb-3">Account</h2>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="profile">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input class="form-control" name="name" value="<?= e($user['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input class="form-control" value="<?= e($user['username']) ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input class="form-control" type="email" name="email" value="<?= e($user['email']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input class="form-control" name="phone" value="<?= e($user['phone']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <input class="form-control" value="<?= e($user['role_display']) ?>" disabled>
                        </div>
                        <p class="text-muted small mb-3">Created <?= e(format_date($user['created_at'])) ?> · Last login <?= e(format_date($user['last_login'])) ?></p>
                        <button class="btn btn-primary">Save Profile</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card-block">
                    <h2 class="h5 mb-3">Change Password</h2>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="password">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input class="form-control" type="password" name="current_password" required autocomplete="current-password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input class="form-control" type="password" name="new_password" required minlength="8" autocomplete="new-password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input class="form-control" type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
                        </div>
                        <button class="btn btn-dark">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <?php
    require INCLUDES_PATH . '/footer.php';
}

function render_sales_table(string $scope = 'all'): void
{
    $isBillerHistory = $scope === 'self' && is_biller();
    if ($isBillerHistory) {
        require_permission(PERM_BILL_HISTORY_VIEW);
    } else {
        require_permission(PERM_SALES_VIEW);
    }
    $filter = $_GET['filter'] ?? 'today';
    $q = trim((string) ($_GET['q'] ?? ''));
    [$from, $to] = date_range_from_filter($filter, $_GET['from'] ?? null, $_GET['to'] ?? null);
    $limit = $isBillerHistory ? biller_history_limit() : 200;

    $sql = 'SELECT s.*, u.name AS biller_name, c.name AS customer_name,
                   pm.name AS payment_method
            FROM sales s
            INNER JOIN users u ON u.id = s.biller_id
            LEFT JOIN customers c ON c.id = s.customer_id
            LEFT JOIN payments p ON p.sale_id = s.id
            LEFT JOIN payment_methods pm ON pm.id = p.payment_method_id
            WHERE 1=1';
    $params = [];
    if ($isBillerHistory) {
        $sql .= ' AND s.biller_id = ?';
        $params[] = (int) $_SESSION['user_id'];
    } else {
        $sql .= ' AND DATE(s.created_at) BETWEEN ? AND ?';
        $params[] = $from;
        $params[] = $to;
        if ($scope === 'self') {
            $sql .= ' AND s.biller_id = ?';
            $params[] = (int) $_SESSION['user_id'];
        }
    }
    if ($q !== '') {
        $sql .= ' AND (s.invoice_number LIKE ? OR u.name LIKE ? OR c.name LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like);
    }
    $sql .= ' ORDER BY s.id DESC LIMIT ' . (int) $limit;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $page_title = $isBillerHistory ? 'Billing History' : 'Sales';
    require INCLUDES_PATH . '/header.php';
    $invoiceBase = is_biller() ? 'biller/invoice.php' : (is_admin() ? 'admin/print.php' : 'biller/invoice.php');
    ?>
    <main class="app-content">
        <div class="page-head">
            <div>
                <h1><?= $isBillerHistory ? 'My Billing History' : ($scope === 'self' ? 'My Billing History' : 'Sales') ?></h1>
                <?php if ($isBillerHistory): ?>
                    <p>Last <?= (int) $limit ?> invoices only.</p>
                <?php else: ?>
                    <p><?= e(format_date($from, 'date')) ?> to <?= e(format_date($to, 'date')) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!$isBillerHistory): ?>
        <form class="filter-bar" method="get">
            <select name="filter" class="form-select">
                <?php foreach (['today' => 'Today', 'yesterday' => 'Yesterday', 'week' => 'This Week', 'month' => 'This Month', 'custom' => 'Custom Date'] as $k => $l): ?>
                    <option value="<?= $k ?>" <?= $filter === $k ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="from" class="form-control" value="<?= e($from) ?>">
            <input type="date" name="to" class="form-control" value="<?= e($to) ?>">
            <input type="search" name="q" class="form-control" placeholder="Invoice, biller, customer" value="<?= e($q) ?>">
            <button class="btn btn-primary">Filter</button>
        </form>
        <?php endif; ?>
        <div class="table-wrap">
            <table class="table table-app">
                <thead>
                <tr>
                    <th>Invoice</th><th>Biller</th><th>Customer</th><th>Date</th><th>Time</th>
                    <th>Subtotal</th><th>Discount</th><th>GST</th><th>Total</th>
                    <th>Payment</th><th>Pay Status</th><th>Sale</th><th></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="13" class="text-center text-muted py-4">No sales found.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= e($r['invoice_number']) ?></td>
                        <td><?= e($r['biller_name']) ?></td>
                        <td><?= e($r['customer_name'] ?: 'Walk-in') ?></td>
                        <td><?= e(format_date($r['created_at'], 'date')) ?></td>
                        <td><?= e(format_date($r['created_at'], 'time')) ?></td>
                        <td><?= e(money_inr($r['subtotal'])) ?></td>
                        <td><?= e(money_inr($r['discount'])) ?></td>
                        <td><?= e(money_inr($r['gst'])) ?></td>
                        <td><strong><?= e(money_inr($r['total'])) ?></strong></td>
                        <td><?= e($r['payment_method'] ?: '—') ?></td>
                        <td><span class="badge <?= $r['payment_status'] === 'PAID' ? 'badge-ok' : 'badge-low' ?>"><?= e($r['payment_status']) ?></span></td>
                        <td><span class="badge <?= $r['status'] === 'COMPLETED' ? 'badge-ok' : 'badge-out' ?>"><?= e($r['status']) ?></span></td>
                        <td><a class="btn btn-sm btn-outline-dark" href="<?= e(url($invoiceBase . '?id=' . $r['id'])) ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    <?php
    require INCLUDES_PATH . '/footer.php';
}

function render_permission_checkboxes(array $perms, array $checkedIds, array $hideNames = []): void
{
    foreach (grouped_permissions($perms, $hideNames) as $module => $items) {
        echo '<div class="perm-group">';
        echo '<h3>' . e(permission_module_label((string) $module)) . '</h3>';
        echo '<div class="row">';
        foreach ($items as $perm) {
            $id = (int) $perm['id'];
            $on = isset($checkedIds[$id]);
            echo '<div class="col-md-6">';
            echo '<label class="form-check">';
            echo '<input class="form-check-input" type="checkbox" name="permission_ids[]" value="' . $id . '"' . ($on ? ' checked' : '') . '> ';
            echo e(permission_label((string) $perm['permission_name'], $perm['description'] ?? null));
            echo '</label></div>';
        }
        echo '</div></div>';
    }
}
