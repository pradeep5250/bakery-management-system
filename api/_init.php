<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function api_post_data(): array
{
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($ct, 'application/json')) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '[]', true);
        return is_array($data) ? $data : [];
    }
    return $_POST;
}

function api_require_csrf(): void
{
    $data = api_post_data();
    $token = $data['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!verify_csrf_token(is_string($token) ? $token : null)) {
        json_response(false, 'Invalid security token. Please refresh and try again.', null, 403);
    }
}
