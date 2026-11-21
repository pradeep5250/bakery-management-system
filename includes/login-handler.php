<?php
declare(strict_types=1);

function handle_role_login(string $expectedRole, string $heading): void
{
    require_once __DIR__ . '/init.php';

    if (is_logged_in() && ($_SESSION['role'] ?? '') === $expectedRole) {
        redirect(role_home($expectedRole));
    }

    $error = '';
    if (is_post()) {
        if (!verify_csrf_token()) {
            $error = 'Invalid security token. Please refresh and try again.';
        } else {
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            if (login_rate_limited($username)) {
                $error = 'Too many attempts. Please wait 5 minutes.';
            } elseif ($username === '' || $password === '') {
                $error = 'Username and password are required.';
            } else {
                $user = find_user_by_username($username);
                if (!$user || !password_verify($password, $user['password_hash'])) {
                    if ($user) {
                        record_failed_login((int) $user['id']);
                    }
                    record_login_attempt($username, false);
                    $error = 'Invalid login credentials.';
                } elseif ($user['status'] !== 'active') {
                    record_login_attempt($username, false);
                    $error = 'Account inactive.';
                } elseif ($user['role_name'] !== $expectedRole) {
                    record_login_attempt($username, false);
                    $error = 'This account cannot log in as ' . strtoupper(str_replace('_', ' ', $expectedRole)) . '.';
                } elseif (login_user($user, $expectedRole)) {
                    record_login_attempt($username, true);
                    redirect(role_home($expectedRole));
                } else {
                    $error = 'Invalid login credentials.';
                }
            }
        }
    }

    $page_title = $heading;
    $guest = true;
    $body_class = 'landing-body';
    $extra_css = [asset('css/landing.css')];
    require INCLUDES_PATH . '/header.php';
    ?>
    <main class="login-wrap">
        <div class="login-card">
            <p class="eyebrow mb-2">POC Access</p>
            <h1 class="h3 mb-4"><?= e($heading) ?></h1>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <form method="post" autocomplete="username">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" id="password" name="password" type="password" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">LOGIN</button>
            </form>
            <div class="login-actions">
                <a href="<?= e(url('forgot-password.php')) ?>">Forgot Password?</a>
                <a class="back-link mt-0" href="<?= e(url('poc.php')) ?>">&larr; Back to Account Type</a>
            </div>
        </div>
    </main>
    <?php
    require INCLUDES_PATH . '/footer.php';
}
