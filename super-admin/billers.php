<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_once INCLUDES_PATH . '/users-ui.php';
render_user_manager('biller', 'Billers');
