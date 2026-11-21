<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
require_login();
$path = match (current_role()) {
    ROLE_SUPER_ADMIN => 'super-admin/customers.php',
    ROLE_ADMIN => 'admin/sales.php',
    default => 'biller/pos.php',
};
redirect($path);
