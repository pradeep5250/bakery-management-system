<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_login();
if (!is_post()) {
    json_response(false, 'Method not allowed.', null, 405);
}
api_require_csrf();
$data = api_post_data();
$username = trim((string) ($data['username'] ?? ''));
$password = (string) ($data['password'] ?? '');
$role = (string) ($data['role'] ?? '');
$user = find_user_by_username($username);
if (!$user || !password_verify($password, $user['password_hash']) || $user['role_name'] !== $role || $user['status'] !== 'active') {
    json_response(false, 'Invalid login credentials.', null, 401);
}
login_user($user, $role);
json_response(true, 'Login successful.', ['redirect' => url(role_home($role))]);
