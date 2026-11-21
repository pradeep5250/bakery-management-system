<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Kolkata');

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('LOGS_PATH', ROOT_PATH . '/logs');
define('SESSION_TIMEOUT', 3600);
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024);
define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_IMAGE_MIME', ['image/jpeg', 'image/png', 'image/webp']);

$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ROOT_PATH) ?: ROOT_PATH);
$rootReal = str_replace('\\', '/', realpath(ROOT_PATH) ?: ROOT_PATH);
$base = '';
if (stripos($rootReal, $docRoot) === 0) {
    $base = substr($rootReal, strlen(rtrim($docRoot, '/')));
}
$segments = array_values(array_filter(explode('/', str_replace('\\', '/', $base)), fn($p) => $p !== ''));
$encoded = implode('/', array_map('rawurlencode', $segments));
define('BASE_URL', ($encoded === '' ? '/' : '/' . $encoded . '/'));
define('ASSETS_URL', BASE_URL . 'assets/');
define('UPLOADS_URL', BASE_URL . 'uploads/');

if (!is_dir(LOGS_PATH)) {
    @mkdir(LOGS_PATH, 0755, true);
}

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', LOGS_PATH . '/php-errors.log');
error_reporting(E_ALL);
