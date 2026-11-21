<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
$page_title = 'POC';
$guest = true;
$body_class = 'landing-body';
$extra_css = [asset('css/landing.css')];
require INCLUDES_PATH . '/header.php';
?>
<main class="landing-wrap">
    <a class="back-link" href="<?= e(url('index.php')) ?>">&larr; Back to Main</a>
    <header class="landing-hero mt-4">
        <p class="eyebrow">Point of Control</p>
        <h1>POC</h1>
        <p>Select Account Type</p>
    </header>
    <div class="role-grid">
        <article class="role-card">
            <div class="role-icon"><i class="bi bi-shield-fill-check"></i></div>
            <h2>SUPER ADMIN</h2>
            <p>Full System Control</p>
            <a class="btn btn-primary" href="<?= e(url('super-admin-login.php')) ?>">CONTINUE</a>
        </article>
        <article class="role-card">
            <div class="role-icon"><i class="bi bi-gear-wide-connected"></i></div>
            <h2>ADMIN</h2>
            <p>Operational Management</p>
            <a class="btn btn-primary" href="<?= e(url('admin-login.php')) ?>">CONTINUE</a>
        </article>
        <article class="role-card">
            <div class="role-icon"><i class="bi bi-receipt"></i></div>
            <h2>BILLER</h2>
            <p>POS &amp; Billing</p>
            <a class="btn btn-primary" href="<?= e(url('biller-login.php')) ?>">CONTINUE</a>
        </article>
    </div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
