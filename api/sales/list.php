<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_init.php';
require_permission(PERM_SALES_VIEW);
$filter = $_GET['filter'] ?? 'today';
[$from, $to] = date_range_from_filter($filter, $_GET['from'] ?? null, $_GET['to'] ?? null);
$sql = 'SELECT s.*, u.name AS biller_name, c.name AS customer_name
        FROM sales s
        INNER JOIN users u ON u.id = s.biller_id
        LEFT JOIN customers c ON c.id = s.customer_id
        WHERE DATE(s.created_at) BETWEEN ? AND ?';
$params = [$from, $to];
if (is_biller()) {
    require_permission(PERM_BILL_HISTORY_VIEW);
    $sql = 'SELECT s.*, u.name AS biller_name, c.name AS customer_name
            FROM sales s
            INNER JOIN users u ON u.id = s.biller_id
            LEFT JOIN customers c ON c.id = s.customer_id
            WHERE s.biller_id = ?
            ORDER BY s.id DESC LIMIT ' . biller_history_limit();
    $params = [(int) $_SESSION['user_id']];
} else {
    $sql .= ' ORDER BY s.id DESC LIMIT 200';
}
$stmt = db()->prepare($sql);
$stmt->execute($params);
json_response(true, 'OK', $stmt->fetchAll());
