<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_BARCODE_PRINT);
api_require_csrf();
$data = api_post_data();
$ids = $data['product_ids'] ?? [];
if (!is_array($ids) || !$ids) {
    json_response(false, 'Select products to print.', null, 400);
}
$ids = array_map('intval', $ids);
log_activity('BARCODE_PRINT', 'barcode', null, 'Printed barcodes for ' . count($ids) . ' products');
json_response(true, 'Print prepared.', ['product_ids' => $ids]);
