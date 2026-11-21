<?php
declare(strict_types=1);

$guest = $guest ?? false;
$page_title = $page_title ?? bakery_name();
$body_class = $body_class ?? ($guest ? 'guest-body' : 'app-body');
$hide_chrome = $hide_chrome ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#6B3E26">
    <title><?= e($page_title) ?> | <?= e(bakery_name()) ?></title>
    <link rel="manifest" href="<?= e(url('manifest.json')) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,650&display=swap">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/dashboard.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/pos.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/mobile.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/print.css')) ?>">
    <?php if (!empty($extra_css)): foreach ((array) $extra_css as $css): ?>
        <link rel="stylesheet" href="<?= e($css) ?>">
    <?php endforeach; endif; ?>
    <script>
        window.APP = {
            baseUrl: <?= json_encode(BASE_URL) ?>,
            csrf: <?= json_encode(csrf_token()) ?>,
            currency: <?= json_encode(get_setting('currency_symbol', '₹')) ?>,
            role: <?= json_encode($_SESSION['role'] ?? '') ?>
        };
    </script>
</head>
<body class="<?= e($body_class) ?>">
<?php if (!empty($guest)): ?>
<header class="guest-top no-print">
    <a class="guest-brand" href="<?= e(url('index.php')) ?>">
        <span class="logo-mark"><i class="bi bi-cup-hot-fill"></i></span>
        <span>
            <strong><?= e(bakery_name()) ?></strong>
            <small>Management &amp; POS</small>
        </span>
    </a>
</header>
<?php endif; ?>
<?php if (!$guest && !$hide_chrome && is_logged_in()): ?>
    <?php require INCLUDES_PATH . '/navbar.php'; ?>
    <?php require INCLUDES_PATH . '/sidebar.php'; ?>
    <div class="app-shell">
<?php endif; ?>
