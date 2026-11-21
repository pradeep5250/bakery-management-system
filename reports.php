<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
require_login();
redirect(is_super_admin() ? 'super-admin/reports.php' : (is_admin() ? 'admin/reports.php' : (has_permission(PERM_BILL_HISTORY_VIEW) ? 'biller/history.php' : 'biller/pos.php')));
