<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_role(ROLE_SUPER_ADMIN);
api_require_csrf();
$data = api_post_data();
$action = $data['action'] ?? 'save_user';

if ($action === 'save_user') {
    require_permission(PERM_USER_CREATE);
    $name = trim((string) ($data['name'] ?? ''));
    $username = trim((string) ($data['username'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $password = (string) ($data['password'] ?? '');
    $roleName = (string) ($data['role'] ?? 'admin');
    if (!in_array($roleName, ['admin', 'biller'], true)) {
        json_response(false, 'Invalid role.', null, 400);
    }
    if ($name === '' || $username === '' || strlen($password) < 8) {
        json_response(false, 'Name, username and an 8+ character password are required.', null, 400);
    }
    $roleId = $roleName === 'biller' ? 3 : 2;
    try {
        db()->prepare(
            'INSERT INTO users (name, username, email, phone, password_hash, role_id, status, force_password_change)
             VALUES (?, ?, ?, ?, ?, ?, "active", 1)'
        )->execute([$name, $username, $email, $phone, password_hash($password, PASSWORD_DEFAULT), $roleId]);
        $id = (int) db()->lastInsertId();
        log_activity($roleName === 'biller' ? 'BILLER_CREATE' : 'ADMIN_CREATE', 'users', $id, 'Created ' . $username);
        notify_role('super_admin', $roleName === 'biller' ? 'New Biller' : 'New Admin', $name . ' was added.', 'info');
        json_response(true, 'User created.', ['id' => $id], 201);
    } catch (PDOException $e) {
        json_response(false, 'Username already exists.', null, 400);
    }
}

if ($action === 'update_user') {
    require_permission(PERM_USER_EDIT);
    $id = (int) ($data['id'] ?? 0);
    prevent_manage_super_admin($id);
    $name = trim((string) ($data['name'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $status = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    db()->prepare('UPDATE users SET name=?, email=?, phone=?, status=? WHERE id=? AND role_id IN (2,3)')
        ->execute([$name, $email, $phone, $status, $id]);
    $act = $status === 'inactive' ? 'DEACTIVATE' : 'UPDATE';
    log_activity('USER_' . $act, 'users', $id, 'Updated user');
    json_response(true, 'User updated.');
}

if ($action === 'reset_password') {
    require_permission(PERM_USER_EDIT);
    $id = (int) ($data['id'] ?? 0);
    prevent_manage_super_admin($id);
    $password = (string) ($data['password'] ?? '');
    if (strlen($password) < 8) {
        json_response(false, 'Password must be at least 8 characters.', null, 400);
    }
    db()->prepare('UPDATE users SET password_hash = ?, force_password_change = 1 WHERE id = ? AND role_id IN (2,3)')
        ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    log_activity('PASSWORD_RESET', 'users', $id, 'Password reset by super admin');
    json_response(true, 'Password reset. User must change it on next login.');
}

if ($action === 'save_role_permissions') {
    $roleId = (int) ($data['role_id'] ?? 0);
    $permIds = $data['permission_ids'] ?? [];
    if ($roleId === 1) {
        json_response(false, 'Super Admin already has all permissions.', null, 400);
    }
    if (!in_array($roleId, [2, 3], true) || !is_array($permIds)) {
        json_response(false, 'Invalid permissions payload.', null, 400);
    }
    $pdo = db();
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$roleId]);
    $ins = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
    foreach ($permIds as $pid) {
        $ins->execute([$roleId, (int) $pid]);
    }
    $pdo->commit();
    log_activity('PERMISSION_UPDATE', 'permissions', $roleId, 'Updated role permissions');
    json_response(true, 'Permissions updated.');
}

json_response(false, 'Unknown action.', null, 400);
