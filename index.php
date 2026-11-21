<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
$page_title = 'Bakery Management System';
$guest = true;
$body_class = 'landing-body';
$extra_css = [asset('css/landing.css')];
require INCLUDES_PATH . '/header.php';
?>
<main class="landing-wrap">
    <header class="landing-hero">
        <p class="eyebrow">Bakery Management System</p>
        <h1>BAKERY MANAGEMENT SYSTEM</h1>
        <p>Manage your bakery products, inventory and billing from one place.</p>
        <p class="mt-3 mb-0">Smart Bakery Management &amp; Point of Sale System</p>
    </header>
    <div class="choice-grid">
        <article class="choice-card">
            <div class="choice-icon"><i class="bi bi-calculator"></i></div>
            <p class="eyebrow">Module</p>
            <h2>BILL DETERMINATION</h2>
            <p>Billing and bill calculation system</p>
            <a class="btn btn-primary" href="<?= e(url('bill-determination.php')) ?>">OPEN BILL DETERMINATION</a>
        </article>
        <article class="choice-card">
            <div class="choice-icon"><i class="bi bi-shop-window"></i></div>
            <p class="eyebrow">Point of Control</p>
            <h2>POC</h2>
            <p>Bakery Point of Control &amp; POS</p>
            <a class="btn btn-gold" href="<?= e(url('poc.php')) ?>">ENTER POC</a>
        </article>
    </div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
