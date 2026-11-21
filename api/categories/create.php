<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_CATEGORY_CREATE);
api_require_csrf();
$name = trim((string) ($_POST['name'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
if ($name === '') {
    json_response(false, 'Category name is required.', null, 400);
}
try {
    db()->prepare('INSERT INTO categories (name, description, status) VALUES (?, ?, "active")')->execute([$name, $description]);
    $id = (int) db()->lastInsertId();
    log_activity('CATEGORY_CREATE', 'categories', $id, 'Created category ' . $name);
    json_response(true, 'Category added.', ['id' => $id], 201);
} catch (PDOException $e) {
    json_response(false, 'Category name already exists.', null, 400);
}
