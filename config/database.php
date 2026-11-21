<?php
declare(strict_types=1);

function db_config(): array
{
    return [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'bakery_management',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ];
}

function db(bool $reconnect = false): PDO
{
    static $pdo = null;

    if ($reconnect) {
        $pdo = null;
    }

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = db_config();
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'],
        $cfg['port'],
        $cfg['name'],
        $cfg['charset']
    );

    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function db_server(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = db_config();
    $dsn = sprintf(
        'mysql:host=%s;port=%d;charset=%s',
        $cfg['host'],
        $cfg['port'],
        $cfg['charset']
    );

    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function database_ready(): bool
{
    try {
        $count = db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
        return (int) $count > 0;
    } catch (Throwable $e) {
        return false;
    }
}
