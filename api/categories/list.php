<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_CATEGORY_VIEW);
$rows = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
json_response(true, 'OK', $rows);
