<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_CUSTOMER_VIEW);
$q = trim((string) ($_GET['q'] ?? ''));
if ($q !== '') {
    $stmt = db()->prepare('SELECT * FROM customers WHERE name LIKE ? OR phone LIKE ? ORDER BY name LIMIT 30');
    $like = '%' . $q . '%';
    $stmt->execute([$like, $like]);
    json_response(true, 'OK', $stmt->fetchAll());
}
json_response(true, 'OK', db()->query('SELECT * FROM customers ORDER BY id DESC LIMIT 50')->fetchAll());
