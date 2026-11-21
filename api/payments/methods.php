<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_PAYMENT_VIEW);
$rows = db()->query('SELECT * FROM payment_methods ORDER BY id')->fetchAll();
json_response(true, 'OK', $rows);
