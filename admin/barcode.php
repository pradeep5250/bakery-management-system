<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_ADMIN);
require_once INCLUDES_PATH . '/barcode-ui.php';
render_barcode_page();
