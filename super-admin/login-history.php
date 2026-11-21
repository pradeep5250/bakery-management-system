<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_SUPER_ADMIN);
require_permission(PERM_LOGIN_HISTORY_VIEW);
$uid = (int) ($_GET['user_id'] ?? 0);
$sql = 'SELECT l.*, u.name, u.username FROM login_history l INNER JOIN users u ON u.id=l.user_id WHERE 1=1';
$params = [];
if ($uid) { $sql .= ' AND l.user_id = ?'; $params[] = $uid; }
$sql .= ' ORDER BY l.id DESC LIMIT 300';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$page_title = 'Login History';
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head"><div><h1>Login History</h1></div></div>
    <div class="table-wrap"><table class="table table-app"><thead><tr><th>User</th><th>Login</th><th>Logout</th><th>IP</th><th>Device</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['name']) ?> (<?= e($r['username']) ?>)</td>
            <td><?= e(format_date($r['login_time'])) ?></td>
            <td><?= e(format_date($r['logout_time'])) ?></td>
            <td><?= e($r['ip_address']) ?></td>
            <td><?= e($r['device']) ?></td>
            <td><?= e($r['status']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
