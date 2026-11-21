<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_role(ROLE_BILLER);
$uid = (int) $_SESSION['user_id'];
$today = date('Y-m-d');
$stmt = db()->prepare("SELECT COUNT(*) bills, COALESCE(SUM(total),0) sales FROM sales WHERE biller_id=? AND status='COMPLETED' AND DATE(created_at)=?");
$stmt->execute([$uid, $today]);
json_response(true, 'OK', $stmt->fetch());
