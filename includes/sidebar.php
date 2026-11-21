<?php
declare(strict_types=1);
$role = current_role();
$logo = get_setting('logo');
?>
<aside class="app-sidebar no-print" id="appSidebar">
    <div class="sidebar-brand">
        <?php if ($logo): ?>
            <img src="<?= e(UPLOADS_URL . $logo) ?>" alt="" class="sidebar-logo">
        <?php else: ?>
            <span class="logo-mark"><i class="bi bi-cup-hot-fill"></i></span>
        <?php endif; ?>
        <div>
            <strong><?= e(bakery_name()) ?></strong>
            <small>POC Control</small>
        </div>
    </div>
    <nav class="sidebar-nav">
        <?php if ($role === ROLE_SUPER_ADMIN): ?>
            <a class="<?= active_nav('/super-admin/dashboard.php') ?>" href="<?= e(url('super-admin/dashboard.php')) ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
            <p class="nav-label">Product Management</p>
            <a class="<?= active_nav('/super-admin/products') ?>" href="<?= e(url('super-admin/products.php')) ?>"><i class="bi bi-box-seam"></i> Products</a>
            <a class="<?= active_nav('/super-admin/categories') ?>" href="<?= e(url('super-admin/categories.php')) ?>"><i class="bi bi-tags"></i> Categories</a>
            <a class="<?= active_nav('/super-admin/stock') ?>" href="<?= e(url('super-admin/stock.php')) ?>"><i class="bi bi-stack"></i> Stock</a>
            <a class="<?= active_nav('/super-admin/barcode') ?>" href="<?= e(url('super-admin/barcode.php')) ?>"><i class="bi bi-upc-scan"></i> Barcode</a>
            <p class="nav-label">User Management</p>
            <a class="<?= active_nav('/super-admin/admins') ?>" href="<?= e(url('super-admin/admins.php')) ?>"><i class="bi bi-shield-check"></i> Admins</a>
            <a class="<?= active_nav('/super-admin/billers') ?>" href="<?= e(url('super-admin/billers.php')) ?>"><i class="bi bi-person-badge"></i> Billers</a>
            <a class="<?= active_nav('/super-admin/permissions') ?>" href="<?= e(url('super-admin/permissions.php')) ?>"><i class="bi bi-key"></i> Permissions</a>
            <p class="nav-label">Sales</p>
            <a class="<?= active_nav('/super-admin/sales') ?>" href="<?= e(url('super-admin/sales.php')) ?>"><i class="bi bi-receipt"></i> Sales</a>
            <a class="<?= active_nav('/super-admin/payments') ?>" href="<?= e(url('super-admin/payments.php')) ?>"><i class="bi bi-wallet2"></i> Payments</a>
            <a class="<?= active_nav('/super-admin/customers') ?>" href="<?= e(url('super-admin/customers.php')) ?>"><i class="bi bi-people"></i> Customers</a>
            <p class="nav-label">Reports</p>
            <a class="<?= active_nav('/super-admin/reports') ?>" href="<?= e(url('super-admin/reports.php')) ?>"><i class="bi bi-graph-up"></i> Reports</a>
            <p class="nav-label">System</p>
            <a class="<?= active_nav('/super-admin/activity-logs') ?>" href="<?= e(url('super-admin/activity-logs.php')) ?>"><i class="bi bi-clock-history"></i> Activity History</a>
            <a class="<?= active_nav('/super-admin/login-history') ?>" href="<?= e(url('super-admin/login-history.php')) ?>"><i class="bi bi-door-open"></i> Login History</a>
            <a class="<?= active_nav('/super-admin/settings') ?>" href="<?= e(url('super-admin/settings.php')) ?>"><i class="bi bi-gear"></i> Settings</a>
            <a class="<?= active_nav('/super-admin/profile') ?>" href="<?= e(url('super-admin/profile.php')) ?>"><i class="bi bi-person"></i> Profile</a>
        <?php elseif ($role === ROLE_ADMIN): ?>
            <a class="<?= active_nav('/admin/dashboard.php') ?>" href="<?= e(url('admin/dashboard.php')) ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
            <?php if (has_permission(PERM_PRODUCT_VIEW)): ?>
                <a class="<?= active_nav('/admin/products') ?>" href="<?= e(url('admin/products.php')) ?>"><i class="bi bi-box-seam"></i> Products</a>
            <?php endif; ?>
            <?php if (has_permission(PERM_STOCK_VIEW)): ?>
                <a class="<?= active_nav('/admin/stock') ?>" href="<?= e(url('admin/stock.php')) ?>"><i class="bi bi-stack"></i> Stock</a>
            <?php endif; ?>
            <?php if (has_permission(PERM_BARCODE_VIEW)): ?>
                <a class="<?= active_nav('/admin/barcode') ?>" href="<?= e(url('admin/barcode.php')) ?>"><i class="bi bi-upc-scan"></i> Barcode</a>
            <?php endif; ?>
            <?php if (has_permission(PERM_SALES_VIEW)): ?>
                <a class="<?= active_nav('/admin/sales') ?>" href="<?= e(url('admin/sales.php')) ?>"><i class="bi bi-receipt"></i> Sales</a>
                <a class="<?= active_nav('/admin/biller-history') ?>" href="<?= e(url('admin/biller-history.php')) ?>"><i class="bi bi-person-lines-fill"></i> Biller History</a>
            <?php endif; ?>
            <?php if (has_permission(PERM_REPORT_VIEW)): ?>
                <a class="<?= active_nav('/admin/reports') ?>" href="<?= e(url('admin/reports.php')) ?>"><i class="bi bi-graph-up"></i> Reports</a>
            <?php endif; ?>
            <?php if (has_permission(PERM_PRINT_CONTROL)): ?>
                <a class="<?= active_nav('/admin/print') ?>" href="<?= e(url('admin/print.php')) ?>"><i class="bi bi-printer"></i> Print</a>
            <?php endif; ?>
            <?php if (has_permission(PERM_USER_EDIT) || has_permission(PERM_SALES_VIEW)): ?>
                <a class="<?= active_nav('/admin/permissions') ?>" href="<?= e(url('admin/permissions.php')) ?>"><i class="bi bi-key"></i> Permissions</a>
            <?php endif; ?>
            <a class="<?= active_nav('/admin/profile') ?>" href="<?= e(url('admin/profile.php')) ?>"><i class="bi bi-person"></i> Profile</a>
        <?php else: ?>
            <a class="<?= active_nav('/biller/pos') ?>" href="<?= e(url('biller/pos.php')) ?>"><i class="bi bi-cash-register"></i> POS</a>
            <?php if (has_permission(PERM_BILL_HISTORY_VIEW)): ?>
                <a class="<?= active_nav('/biller/history') ?>" href="<?= e(url('biller/history.php')) ?>"><i class="bi bi-clock-history"></i> History</a>
            <?php endif; ?>
            <?php if (has_permission(PERM_BILL_PRINT) || has_permission(PERM_BILL_HISTORY_VIEW)): ?>
                <a class="<?= active_nav('/biller/invoice') ?>" href="<?= e(url('biller/invoice.php')) ?>"><i class="bi bi-receipt"></i> Invoice</a>
            <?php endif; ?>
            <a class="<?= active_nav('/biller/profile') ?>" href="<?= e(url('biller/profile.php')) ?>"><i class="bi bi-person"></i> Profile</a>
        <?php endif; ?>
        <a href="<?= e(url('logout.php')) ?>"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
</aside>
<div class="sidebar-backdrop no-print" id="sidebarBackdrop"></div>
