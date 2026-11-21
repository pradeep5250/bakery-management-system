<?php
declare(strict_types=1);

function require_role(string ...$roles): void
{
    require_login();
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        if (wants_json()) {
            json_response(false, 'You do not have permission.', null, 403);
        }
        http_response_code(403);
        $page_title = 'Access Denied';
        require INCLUDES_PATH . '/header.php';
        echo '<div class="app-content"><div class="empty-state"><i class="bi bi-shield-x"></i><h2>Access Denied</h2><p>You do not have permission to view this page.</p><a class="btn btn-primary" href="' . e(url(role_home($role))) . '">Go to Dashboard</a></div></div>';
        require INCLUDES_PATH . '/footer.php';
        exit;
    }
}

function current_role(): string
{
    return (string) ($_SESSION['role'] ?? '');
}

function is_super_admin(): bool
{
    return current_role() === ROLE_SUPER_ADMIN;
}

function is_admin(): bool
{
    return current_role() === ROLE_ADMIN;
}

function is_biller(): bool
{
    return current_role() === ROLE_BILLER;
}

function prevent_manage_super_admin(int $targetUserId): void
{
    $stmt = db()->prepare(
        'SELECT r.name FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.id = ?'
    );
    $stmt->execute([$targetUserId]);
    $role = $stmt->fetchColumn();
    if ($role === ROLE_SUPER_ADMIN && !is_super_admin()) {
        json_response(false, 'You cannot modify Super Admin accounts.', null, 403);
    }
}
