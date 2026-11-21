<?php
declare(strict_types=1);

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']) && !empty($_SESSION['role']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        if (wants_json()) {
            json_response(false, 'Please log in to continue.', null, 401);
        }
        redirect('poc.php');
    }

    $user = current_user();
    if (!$user || $user['status'] !== 'active') {
        $_SESSION = [];
        session_destroy();
        if (wants_json()) {
            json_response(false, 'Account inactive.', null, 401);
        }
        redirect('poc.php');
    }

    if (!empty($user['force_password_change']) && !str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'profile.php') && !str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'change-password.php')) {
        $home = role_home($user['role_name']);
        $folder = explode('/', $home)[0];
        if (wants_json()) {
            json_response(false, 'Password change required.', ['redirect' => url($folder . '/profile.php')], 403);
        }
        redirect($folder . '/profile.php?force=1');
    }
}

function wants_json(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return str_contains($uri, '/api/') || str_contains($accept, 'application/json');
}

function login_user(array $user, string $expectedRole): bool
{
    if ($user['role_name'] !== $expectedRole) {
        return false;
    }
    if ($user['status'] !== 'active') {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role_name'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['login_history_id'] = null;

    db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([(int) $user['id']]);

    $stmt = db()->prepare(
        'INSERT INTO login_history (user_id, login_time, ip_address, user_agent, device, status)
         VALUES (?, NOW(), ?, ?, ?, "success")'
    );
    $stmt->execute([(int) $user['id'], client_ip(), user_agent(), detect_device()]);
    $_SESSION['login_history_id'] = (int) db()->lastInsertId();

    log_activity('LOGIN', 'auth', (int) $user['id'], 'User logged in as ' . $user['role_name']);
    return true;
}

function record_failed_login(int $userId): void
{
    $stmt = db()->prepare(
        'INSERT INTO login_history (user_id, login_time, ip_address, user_agent, device, status)
         VALUES (?, NOW(), ?, ?, ?, "failed")'
    );
    $stmt->execute([$userId, client_ip(), user_agent(), detect_device()]);
}

function logout_user(): void
{
    if (!empty($_SESSION['login_history_id'])) {
        $stmt = db()->prepare(
            'UPDATE login_history SET logout_time = NOW(), status = "logout" WHERE id = ?'
        );
        $stmt->execute([(int) $_SESSION['login_history_id']]);
    }
    if (!empty($_SESSION['user_id'])) {
        log_activity('LOGOUT', 'auth', (int) $_SESSION['user_id'], 'User logged out');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'] ?? false, $p['httponly'] ?? true);
    }
    session_destroy();
}

function find_user_by_username(string $username): ?array
{
    $stmt = db()->prepare(
        'SELECT u.*, r.name AS role_name, r.display_name AS role_display
         FROM users u INNER JOIN roles r ON r.id = u.role_id
         WHERE u.username = ? LIMIT 1'
    );
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    return $row ?: null;
}
