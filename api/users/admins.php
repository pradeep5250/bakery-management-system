<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_role(ROLE_SUPER_ADMIN);
require_permission(PERM_USER_VIEW);

$role = $_GET['role'] ?? 'admin';
$roleId = $role === 'biller' ? 3 : 2;
$stmt = db()->prepare(
    'SELECT u.id, u.name, u.username, u.email, u.phone, u.status, u.last_login, u.created_at, r.display_name AS role_display
     FROM users u INNER JOIN roles r ON r.id = u.role_id
     WHERE u.role_id = ? ORDER BY u.name'
);
$stmt->execute([$roleId]);
json_response(true, 'OK', $stmt->fetchAll());
