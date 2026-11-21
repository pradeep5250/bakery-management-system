<?php
declare(strict_types=1);

function user_permissions(?int $userId = null): array
{
    static $cache = [];
    $userId = $userId ?? (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return [];
    }
    if (isset($cache[$userId])) {
        return $cache[$userId];
    }

    $user = current_user();
    if ($user && (int) $user['id'] === $userId && $user['role_name'] === ROLE_SUPER_ADMIN) {
        $all = db()->query('SELECT permission_name FROM permissions')->fetchAll(PDO::FETCH_COLUMN);
        $cache[$userId] = $all;
        return $all;
    }

    $stmt = db()->prepare(
        'SELECT p.permission_name
         FROM role_permissions rp
         INNER JOIN permissions p ON p.id = rp.permission_id
         INNER JOIN users u ON u.role_id = rp.role_id
         WHERE u.id = ?'
    );
    $stmt->execute([$userId]);
    $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = db()->prepare(
        'SELECT p.permission_name, up.granted
         FROM user_permissions up
         INNER JOIN permissions p ON p.id = up.permission_id
         WHERE up.user_id = ?'
    );
    $stmt->execute([$userId]);
    foreach ($stmt->fetchAll() as $row) {
        if ((int) $row['granted'] === 1) {
            $perms[] = $row['permission_name'];
        } else {
            $perms = array_values(array_filter($perms, fn($p) => $p !== $row['permission_name']));
        }
    }

    $cache[$userId] = array_values(array_unique($perms));
    return $cache[$userId];
}

function has_permission(string $permission): bool
{
    if (current_role() === ROLE_SUPER_ADMIN) {
        return true;
    }
    return in_array($permission, user_permissions(), true);
}

function require_permission(string ...$permissions): void
{
    require_login();
    foreach ($permissions as $permission) {
        if (!has_permission($permission)) {
            if (wants_json()) {
                json_response(false, 'You do not have permission.', null, 403);
            }
            http_response_code(403);
            $page_title = 'Access Denied';
            require INCLUDES_PATH . '/header.php';
            echo '<div class="app-content"><div class="empty-state"><i class="bi bi-lock"></i><h2>You do not have permission</h2><p>This action is not available for your account.</p></div></div>';
            require INCLUDES_PATH . '/footer.php';
            exit;
        }
    }
}

function require_any_permission(string ...$permissions): void
{
    require_login();
    foreach ($permissions as $permission) {
        if (has_permission($permission)) {
            return;
        }
    }
    if (wants_json()) {
        json_response(false, 'You do not have permission.', null, 403);
    }
    http_response_code(403);
    exit;
}
