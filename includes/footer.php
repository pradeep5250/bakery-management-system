<?php
declare(strict_types=1);
$guest = $guest ?? false;
$hide_chrome = $hide_chrome ?? false;
?>
<?php if (!$guest && !$hide_chrome && is_logged_in()): ?>
    </div>
    <?php require INCLUDES_PATH . '/mobile-nav.php'; ?>
<?php endif; ?>
<div class="toast-wrap" id="toastWrap"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<?php if (!empty($extra_js)): foreach ((array) $extra_js as $js): ?>
    <script src="<?= e($js) ?>"></script>
<?php endforeach; endif; ?>
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register(<?= json_encode(url('service-worker.js')) ?>).catch(function () {});
}
</script>
</body>
</html>
