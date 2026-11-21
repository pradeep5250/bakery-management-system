<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/config/constants.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/functions-ui.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role.php';
require_once ROOT_PATH . '/middleware/permission.php';

secure_headers();
start_app_session();

$script = basename($_SERVER['SCRIPT_NAME'] ?? '');
$skipDb = in_array($script, ['install.php'], true);

if (!$skipDb && !database_ready()) {
    redirect('install.php');
}
if (!$skipDb && database_ready()) {
    ensure_billing_history_controls();
    ensure_indian_snack_products();
    ensure_sample_product_images();
}
