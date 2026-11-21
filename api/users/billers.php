<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_role(ROLE_SUPER_ADMIN);
require_permission(PERM_USER_VIEW);
$_GET['role'] = 'biller';
require __DIR__ . '/admins.php';
