<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_login();
api_require_csrf();
$data = api_post_data();
$user = current_user();
$current = (string) ($data['current_password'] ?? '');
$new = (string) ($data['new_password'] ?? '');
if (!password_verify($current, $user['password_hash'])) {
    json_response(false, 'Current password is incorrect.', null, 400);
}
if (strlen($new) < 8) {
    json_response(false, 'Password must be at least 8 characters.', null, 400);
}
db()->prepare('UPDATE users SET password_hash = ?, force_password_change = 0 WHERE id = ?')
    ->execute([password_hash($new, PASSWORD_DEFAULT), (int) $user['id']]);
log_activity('PASSWORD_CHANGE', 'auth', (int) $user['id'], 'Changed password');
json_response(true, 'Password changed successfully.');
