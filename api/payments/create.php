<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_PAYMENT_CONFIG);
api_require_csrf();
$data = api_post_data();
$id = (int) ($data['id'] ?? 0);
$status = ($data['status'] ?? '') === 'active' ? 'active' : 'inactive';
db()->prepare('UPDATE payment_methods SET status = ? WHERE id = ?')->execute([$status, $id]);
log_activity('PAYMENT_CONFIG', 'payments', $id, 'Payment method status ' . $status);
json_response(true, 'Payment method updated.');
