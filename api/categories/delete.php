<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_CATEGORY_DELETE);
api_require_csrf();
$data = api_post_data();
$id = (int) ($data['id'] ?? 0);
$used = db()->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
$used->execute([$id]);
if ((int) $used->fetchColumn() > 0) {
    db()->prepare('UPDATE categories SET status = "inactive" WHERE id = ?')->execute([$id]);
    log_activity('CATEGORY_UPDATE', 'categories', $id, 'Deactivated category in use');
    json_response(true, 'Category deactivated because products are linked to it.');
}
db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
log_activity('CATEGORY_DELETE', 'categories', $id, 'Deleted category');
json_response(true, 'Category deleted.');
