<?php
declare(strict_types=1);

define('ROOT_PATH', __DIR__);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

secure_headers();
start_app_session();

$installed = false;
try {
    $installed = database_ready();
} catch (Throwable $e) {
    $installed = false;
}

$message = '';
$error = '';

function run_sql_file(PDO $pdo, string $file): void
{
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException('Unable to read ' . basename($file));
    }
    if (strncmp($sql, "\xEF\xBB\xBF", 3) === 0) {
        $sql = substr($sql, 3);
    }
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if ($stmt === '') {
            continue;
        }
        $pdo->exec($stmt);
    }
}

function create_demo_users(PDO $pdo): void
{
    $pdo->exec("DELETE FROM users WHERE username IN ('superadmin','admin','biller')");
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $ins = $pdo->prepare(
        'INSERT INTO users (name, username, email, phone, password_hash, role_id, status, force_password_change)
         VALUES (?, ?, ?, ?, ?, ?, "active", 0)'
    );
    $ins->execute(['System Super Admin', 'superadmin', 'superadmin@bakery.local', '9876543210', $hash, 1]);
    $ins->execute(['Bakery Admin', 'admin', 'admin@bakery.local', '9876543211', $hash, 2]);
    $ins->execute(['Counter Biller', 'biller', 'biller@bakery.local', '9876543212', $hash, 3]);
}

if (is_post()) {
    if (!verify_csrf_token()) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        try {
            $server = db_server();
            $server->exec('DROP DATABASE IF EXISTS bakery_management');
            run_sql_file($server, ROOT_PATH . '/database/bakery.sql');
            $pdo = db(true);
            create_demo_users($pdo);
            notify(null, 'System Installed', 'Bakery Management System was installed successfully.', 'success');
            $installed = true;
            $message = 'Database, tables, sample products and demo users were created successfully.';
        } catch (Throwable $e) {
            app_log('Install failed: ' . $e->getMessage());
            $error = 'Installation failed: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install | Bakery Management System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body class="landing-body">
<header class="guest-top">
    <a class="guest-brand" href="index.php">
        <span class="logo-mark"><i class="bi bi-cup-hot-fill"></i></span>
        <span><strong>Golden Crust Bakery</strong><small>Setup</small></span>
    </a>
</header>
<main class="container pb-5" style="max-width:560px">
    <div class="landing-card p-4 p-md-5 mt-4">
        <p class="eyebrow mb-2">Setup</p>
        <h1 class="h3 mb-3">Install Bakery Management</h1>
        <p class="text-muted">This creates the <strong>bakery_management</strong> database, all tables, sample products and demo users.</p>
        <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if ($installed && !$error): ?>
            <div class="alert alert-info">
                <strong>Demo logins</strong> (password for all: <code>admin123</code>)<br>
                Super Admin: <code>superadmin</code><br>
                Admin: <code>admin</code><br>
                Biller: <code>biller</code>
            </div>
            <a class="btn btn-primary btn-lg w-100" href="index.php">Open Application</a>
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <button class="btn btn-outline-dark w-100" type="submit" onclick="return confirm('This will delete and recreate the bakery_management database. Continue?')">Recreate Database</button>
            </form>
        <?php else: ?>
            <form method="post">
                <?= csrf_field() ?>
                <button class="btn btn-primary btn-lg w-100" type="submit">Create Database &amp; Seed Data</button>
            </form>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
