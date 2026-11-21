<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_login();
if (is_post()) {
    api_require_csrf();
    $id = (int) (api_post_data()['id'] ?? 0);
    db()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR user_id IS NULL)')
        ->execute([$id, (int) $_SESSION['user_id']]);
    json_response(true, 'Marked read.');
}
$stmt = db()->prepare(
    'SELECT * FROM notifications WHERE user_id = ? OR user_id IS NULL ORDER BY id DESC LIMIT 20'
);
$stmt->execute([(int) $_SESSION['user_id']]);
json_response(true, 'OK', ['items' => $stmt->fetchAll(), 'unread' => unread_notification_count()]);
