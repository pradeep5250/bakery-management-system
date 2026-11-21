<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_CUSTOMER_CREATE);
api_require_csrf();
$data = api_post_data();
$name = trim((string) ($data['name'] ?? ''));
if ($name === '') {
    json_response(false, 'Customer name is required.', null, 400);
}
db()->prepare('INSERT INTO customers (name, phone, email, address, customer_type) VALUES (?, ?, ?, ?, "registered")')
    ->execute([$name, trim((string) ($data['phone'] ?? '')), trim((string) ($data['email'] ?? '')), trim((string) ($data['address'] ?? ''))]);
json_response(true, 'Customer saved.', ['id' => (int) db()->lastInsertId()], 201);
