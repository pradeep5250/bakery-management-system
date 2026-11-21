<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_role(ROLE_SUPER_ADMIN);
require_permission(PERM_SETTINGS_EDIT);
api_require_csrf();

$keys = ['bakery_name','address','phone','email','gst_number','invoice_prefix','currency','currency_symbol','default_gst','receipt_footer','thank_you_message','business_hours','printer_type'];
$pdo = db();
$upd = $pdo->prepare('INSERT INTO settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)');
foreach ($keys as $key) {
    if (isset($_POST[$key])) {
        $upd->execute([$key, trim((string) $_POST[$key]), (int) $_SESSION['user_id']]);
    }
}
if (!empty($_FILES['logo']) && is_array($_FILES['logo']) && ($_FILES['logo']['error'] ?? 4) !== 4) {
    try {
        $path = safe_upload_image($_FILES['logo'], 'logo');
        $upd->execute(['logo', $path, (int) $_SESSION['user_id']]);
    } catch (Throwable $e) {
        json_response(false, $e->getMessage(), null, 400);
    }
}
log_activity('SETTINGS_UPDATE', 'settings', null, 'Updated system settings');
json_response(true, 'Settings saved.');
