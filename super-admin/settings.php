<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_SUPER_ADMIN);
require_permission(PERM_SETTINGS_VIEW);
$msg = $err = '';
if (isset($_GET['saved'])) {
    $msg = 'Settings saved.';
}
if (is_post() && has_permission(PERM_SETTINGS_EDIT)) {
    if (!verify_csrf_token()) {
        $err = 'Invalid security token.';
    } else {
        $keys = ['bakery_name','address','phone','email','gst_number','invoice_prefix','currency_symbol','default_gst','receipt_footer','thank_you_message','business_hours','printer_type'];
        $upd = db()->prepare('INSERT INTO settings (setting_key, setting_value, updated_by) VALUES (?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by)');
        foreach ($keys as $k) {
            $upd->execute([$k, trim((string)($_POST[$k] ?? '')), (int)$_SESSION['user_id']]);
        }
        if (!empty($_FILES['logo']['name'])) {
            try {
                $path = safe_upload_image($_FILES['logo'], 'logo');
                $upd->execute(['logo', $path, (int)$_SESSION['user_id']]);
            } catch (Throwable $e) {
                $err = $e->getMessage();
            }
        }
        log_activity('SETTINGS_UPDATE', 'settings', null, 'Updated settings');
        if (!$err) {
            redirect('super-admin/settings.php?saved=1');
        }
    }
}
$page_title = 'Settings';
require INCLUDES_PATH . '/header.php';
$s = fn($k, $d='') => e(get_setting($k, $d) ?? $d);
?>
<main class="app-content">
    <div class="page-head"><div><h1>Settings</h1></div></div>
    <?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
    <form class="card-block" method="post" enctype="multipart/form-data" style="max-width:820px">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Bakery name</label><input class="form-control" name="bakery_name" value="<?= $s('bakery_name') ?>"></div>
            <div class="col-md-6"><label class="form-label">GST number</label><input class="form-control" name="gst_number" value="<?= $s('gst_number') ?>"></div>
            <div class="col-12"><label class="form-label">Address</label><input class="form-control" name="address" value="<?= $s('address') ?>"></div>
            <div class="col-md-4"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= $s('phone') ?>"></div>
            <div class="col-md-4"><label class="form-label">Email</label><input class="form-control" name="email" value="<?= $s('email') ?>"></div>
            <div class="col-md-4"><label class="form-label">Invoice prefix</label><input class="form-control" name="invoice_prefix" value="<?= $s('invoice_prefix','INV') ?>"></div>
            <div class="col-md-4"><label class="form-label">Currency symbol</label><input class="form-control" name="currency_symbol" value="<?= $s('currency_symbol','₹') ?>"></div>
            <div class="col-md-4"><label class="form-label">Default GST %</label><input class="form-control" name="default_gst" value="<?= $s('default_gst','0') ?>"></div>
            <div class="col-md-4"><label class="form-label">Business hours</label><input class="form-control" name="business_hours" value="<?= $s('business_hours') ?>"></div>
            <div class="col-md-6"><label class="form-label">Receipt footer</label><input class="form-control" name="receipt_footer" value="<?= $s('receipt_footer') ?>"></div>
            <div class="col-md-6"><label class="form-label">Thank you message</label><input class="form-control" name="thank_you_message" value="<?= $s('thank_you_message') ?>"></div>
            <div class="col-md-6"><label class="form-label">Printer</label>
                <select class="form-select" name="printer_type">
                    <option value="a4" <?= get_setting('printer_type')==='a4'?'selected':'' ?>>A4 Invoice</option>
                    <option value="thermal" <?= get_setting('printer_type')==='thermal'?'selected':'' ?>>80mm Thermal</option>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label">Logo</label><input class="form-control" type="file" name="logo" accept="image/jpeg,image/png,image/webp"></div>
        </div>
        <?php if (has_permission(PERM_SETTINGS_EDIT)): ?><button class="btn btn-primary mt-4">Save Settings</button><?php endif; ?>
    </form>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
