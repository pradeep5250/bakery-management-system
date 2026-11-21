<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
$page_title = 'Forgot Password';
$guest = true;
$body_class = 'landing-body';
$extra_css = [asset('css/landing.css')];
$message = 'Email is not configured on this local XAMPP setup. Ask a Super Admin to reset your password from Admins or Billers, then change it after login.';
require INCLUDES_PATH . '/header.php';
?>
<main class="login-wrap">
    <div class="login-card">
        <p class="eyebrow mb-2">Account</p>
        <h1 class="h4 mb-3">Forgot Password</h1>
        <div class="alert alert-info"><?= e($message) ?></div>
        <a class="btn btn-primary" href="<?= e(url('poc.php')) ?>">Back to Login</a>
    </div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
