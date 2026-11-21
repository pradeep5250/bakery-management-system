<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_SUPER_ADMIN);
require_permission(PERM_CATEGORY_VIEW);
$q = trim((string) ($_GET['q'] ?? ''));
if ($q !== '') {
    $stmt = db()->prepare('SELECT * FROM categories WHERE name LIKE ? ORDER BY name');
    $stmt->execute(['%' . $q . '%']);
    $rows = $stmt->fetchAll();
} else {
    $rows = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
}
$page_title = 'Categories';
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head"><div><h1>Categories</h1></div></div>
    <form class="filter-bar" method="get"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search category"><button class="btn btn-outline-dark">Search</button></form>
    <?php if (has_permission(PERM_CATEGORY_CREATE)): ?>
    <form class="card-block mb-4" method="post" action="<?= e(url('api/categories/create.php')) ?>" onsubmit="return appPost(this)">
        <?= csrf_field() ?>
        <div class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
            <div class="col-md-6"><label class="form-label">Description</label><input class="form-control" name="description"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
        </div>
    </form>
    <?php endif; ?>
    <div class="table-wrap"><table class="table table-app"><thead><tr><th>Name</th><th>Description</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['name']) ?></td><td><?= e($r['description']) ?></td>
            <td><span class="badge <?= $r['status']==='active'?'badge-ok':'badge-out' ?>"><?= e($r['status']) ?></span></td>
            <td>
                <?php if (has_permission(PERM_CATEGORY_DELETE)): ?>
                <button class="btn btn-sm btn-outline-danger" onclick="appConfirm('Delete category?','<?= e($r['name']) ?>', () => appJson('api/categories/delete.php',{id:<?= (int)$r['id'] ?>}))">Delete</button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
