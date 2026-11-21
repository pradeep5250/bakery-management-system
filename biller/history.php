<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_BILLER);
render_sales_table('self');
