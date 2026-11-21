<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_SUPER_ADMIN);
require_permission(PERM_CUSTOMER_VIEW);
if (is_post() && verify_csrf_token() && has_permission(PERM_CUSTOMER_CREATE)) {
    db()->prepare('INSERT INTO customers (name, phone, email, address, customer_type) VALUES (?, ?, ?, ?, "registered")')
        ->execute([trim($_POST['name']), trim($_POST['phone'] ?? ''), trim($_POST['email'] ?? ''), trim($_POST['address'] ?? '')]);
}
$rows = db()->query('SELECT * FROM customers ORDER BY id DESC')->fetchAll();
$page_title = 'Customers';
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head"><div><h1>Customers</h1><p>Walk-in sales do not require registration.</p></div></div>
    <form class="card-block mb-4" method="post">
        <?= csrf_field() ?>
        <div class="row g-2">
            <div class="col-md-3"><input class="form-control" name="name" placeholder="Name" required></div>
            <div class="col-md-2"><input class="form-control" name="phone" placeholder="Phone"></div>
            <div class="col-md-3"><input class="form-control" name="email" placeholder="Email"></div>
            <div class="col-md-3"><input class="form-control" name="address" placeholder="Address"></div>
            <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
        </div>
    </form>
    <div class="table-wrap"><table class="table table-app"><thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Type</th><th>Created</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
        <tr><td><?= e($r['name']) ?></td><td><?= e($r['phone']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['customer_type']) ?></td><td><?= e(format_date($r['created_at'])) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
