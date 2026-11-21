<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_login();
if (is_post()) {
    api_require_csrf();
}
logout_user();
json_response(true, 'Logged out.');
