<?php
declare(strict_types=1);
$role = current_role();
?>
<nav class="mobile-bottom-nav no-print" aria-label="Mobile navigation">
    <?php if ($role === ROLE_SUPER_ADMIN): ?>
        <a class="<?= active_nav('/super-admin/dashboard') ?>" href="<?= e(url('super-admin/dashboard.php')) ?>"><i class="bi bi-grid"></i><span>Home</span></a>
        <a class="<?= active_nav('/super-admin/products') ?>" href="<?= e(url('super-admin/products.php')) ?>"><i class="bi bi-box-seam"></i><span>Products</span></a>
        <a class="<?= active_nav('/super-admin/admins') ?>" href="<?= e(url('super-admin/admins.php')) ?>"><i class="bi bi-people"></i><span>Users</span></a>
        <a class="<?= active_nav('/super-admin/reports') ?>" href="<?= e(url('super-admin/reports.php')) ?>"><i class="bi bi-graph-up"></i><span>Reports</span></a>
        <a class="<?= active_nav('/super-admin/settings') ?>" href="<?= e(url('super-admin/settings.php')) ?>"><i class="bi bi-gear"></i><span>Settings</span></a>
    <?php elseif ($role === ROLE_ADMIN): ?>
        <a class="<?= active_nav('/admin/dashboard') ?>" href="<?= e(url('admin/dashboard.php')) ?>"><i class="bi bi-grid"></i><span>Home</span></a>
        <a class="<?= active_nav('/admin/products') ?>" href="<?= e(url('admin/products.php')) ?>"><i class="bi bi-box-seam"></i><span>Products</span></a>
        <a class="<?= active_nav('/admin/stock') ?>" href="<?= e(url('admin/stock.php')) ?>"><i class="bi bi-stack"></i><span>Stock</span></a>
        <a class="<?= active_nav('/admin/sales') ?>" href="<?= e(url('admin/sales.php')) ?>"><i class="bi bi-receipt"></i><span>Sales</span></a>
        <a class="<?= active_nav('/admin/profile') ?>" href="<?= e(url('admin/profile.php')) ?>"><i class="bi bi-person"></i><span>Profile</span></a>
    <?php else: ?>
        <a class="<?= active_nav('/biller/pos') ?>" href="<?= e(url('biller/pos.php')) ?>"><i class="bi bi-house"></i><span>Home</span></a>
        <a href="<?= e(url('biller/pos.php')) ?>"><i class="bi bi-plus-lg"></i><span>New Bill</span></a>
        <?php if (has_permission(PERM_BILL_HISTORY_VIEW)): ?>
            <a class="<?= active_nav('/biller/history') ?>" href="<?= e(url('biller/history.php')) ?>"><i class="bi bi-clock-history"></i><span>History</span></a>
        <?php endif; ?>
        <a class="<?= active_nav('/biller/profile') ?>" href="<?= e(url('biller/profile.php')) ?>"><i class="bi bi-person"></i><span>Profile</span></a>
    <?php endif; ?>
</nav>
