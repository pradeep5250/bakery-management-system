<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_SUPER_ADMIN);
require_permission(PERM_HISTORY_VIEW);
$q = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT a.*, u.name FROM activity_logs a LEFT JOIN users u ON u.id=a.user_id WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (a.action LIKE ? OR a.description LIKE ? OR u.name LIKE ?)';
    $params = ['%'.$q.'%','%'.$q.'%','%'.$q.'%'];
}
$sql .= ' ORDER BY a.id DESC LIMIT 300';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$page_title = 'Activity History';
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head"><div><h1>Activity History</h1></div></div>
    <form class="filter-bar" method="get"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Action, user, description"><button class="btn btn-outline-dark">Search</button></form>
    <div class="table-wrap"><table class="table table-app"><thead><tr><th>Time</th><th>User</th><th>Role</th><th>Action</th><th>Module</th><th>Description</th><th>IP</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e(format_date($r['created_at'])) ?></td>
            <td><?= e($r['name']) ?></td>
            <td><?= e($r['role']) ?></td>
            <td><?= e($r['action']) ?></td>
            <td><?= e($r['module']) ?></td>
            <td><?= e($r['description']) ?></td>
            <td><?= e($r['ip_address']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
