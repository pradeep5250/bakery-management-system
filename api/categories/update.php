<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_CATEGORY_EDIT);
api_require_csrf();
$id = (int) ($_POST['id'] ?? 0);
$name = trim((string) ($_POST['name'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$status = in_array($_POST['status'] ?? 'active', ['active', 'inactive'], true) ? $_POST['status'] : 'active';
if ($id <= 0 || $name === '') {
    json_response(false, 'Invalid category.', null, 400);
}
try {
    db()->prepare('UPDATE categories SET name=?, description=?, status=? WHERE id=?')->execute([$name, $description, $status, $id]);
    log_activity('CATEGORY_UPDATE', 'categories', $id, 'Updated category ' . $name);
    json_response(true, 'Category updated.');
} catch (PDOException $e) {
    json_response(false, 'Category name already exists.', null, 400);
}
