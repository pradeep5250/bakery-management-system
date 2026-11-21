<?php
declare(strict_types=1);
$user = current_user();
$count = unread_notification_count();
$role = current_role();
$profile = match ($role) {
    ROLE_SUPER_ADMIN => 'super-admin/profile.php',
    ROLE_ADMIN => 'admin/profile.php',
    default => 'biller/profile.php',
};
?>
<header class="app-navbar no-print">
    <button class="nav-toggle" type="button" id="sidebarToggle" aria-label="Open menu">
        <i class="bi bi-list"></i>
    </button>
    <div class="brand-mini d-lg-none"><?= e(bakery_name()) ?></div>
    <form class="nav-search d-none d-md-flex" action="<?= e(url($role === ROLE_BILLER ? 'biller/pos.php' : ($role === ROLE_ADMIN ? 'admin/products.php' : 'super-admin/products.php'))) ?>" method="get">
        <i class="bi bi-search"></i>
        <input type="search" name="q" placeholder="Search products, barcode or invoice..." aria-label="Search">
    </form>
    <div class="nav-right">
        <div class="dropdown">
            <button class="icon-btn" data-bs-toggle="dropdown" aria-label="Notifications">
                <i class="bi bi-bell"></i>
                <?php if ($count > 0): ?><span class="badge-count"><?= (int) $count ?></span><?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end notify-menu p-0">
                <div class="p-3 border-bottom fw-semibold">Notifications</div>
                <div id="notifyList" class="notify-list">Loading...</div>
            </div>
        </div>
        <div class="dropdown">
            <button class="profile-chip" data-bs-toggle="dropdown">
                <span class="avatar"><?= e(strtoupper(substr($user['name'] ?? 'U', 0, 1))) ?></span>
                <span class="d-none d-md-block">
                    <strong><?= e($user['name'] ?? '') ?></strong>
                    <small><?= e($user['role_display'] ?? '') ?></small>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= e(url($profile)) ?>"><i class="bi bi-person me-2"></i>Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= e(url('logout.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</header>
