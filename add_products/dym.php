<?php
require_once "../auth/conn.php";

$firstSaleDate = $pdo->query("SELECT MIN(created_at) FROM orders")->fetchColumn();
$daysSinceStart = 0;
if ($firstSaleDate) {
    $start = new DateTime($firstSaleDate);
    $now = new DateTime();
    $daysSinceStart = $start->diff($now)->days;
}

$dailySales = $pdo->query("SELECT SUM(o.quantity * p.price) FROM orders o JOIN products p ON o.product_id = p.id WHERE o.status IN ('Approved', 'Delivered') AND DATE(o.created_at) = CURDATE()")->fetchColumn() ?? 0;

$monthlySales = $pdo->query("SELECT SUM(o.quantity * p.price) FROM orders o JOIN products p ON o.product_id = p.id WHERE o.status IN ('Approved', 'Delivered') AND MONTH(o.created_at) = MONTH(CURRENT_DATE()) AND YEAR(o.created_at) = YEAR(CURRENT_DATE())")->fetchColumn() ?? 0;

$yearlySales = $pdo->query("SELECT SUM(o.quantity * p.price) FROM orders o JOIN products p ON o.product_id = p.id WHERE o.status IN ('Approved', 'Delivered') AND YEAR(o.created_at) = YEAR(CURRENT_DATE())")->fetchColumn() ?? 0;

$totalSales = $pdo->query("SELECT SUM(o.quantity * p.price) FROM orders o JOIN products p ON o.product_id = p.id WHERE o.status IN ('Approved', 'Delivered')")->fetchColumn() ?? 0;


$productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStockCount = $pdo->query("SELECT COUNT(*) FROM products WHERE (quantity / max_quantity) <= 0.15")->fetchColumn();

$recentOrders = $pdo->query("SELECT o.order_id, o.customer_name, p.product_name, o.status FROM orders o JOIN products p ON o.product_id = p.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll();
$trendQuery = $pdo->query("SELECT DATE(o.created_at) as date, SUM(o.quantity * p.price) as daily_total FROM orders o JOIN products p ON o.product_id = p.id WHERE o.status IN ('Approved', 'Delivered') AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(o.created_at) ORDER BY date ASC");
$trendData = $trendQuery->fetchAll(PDO::FETCH_ASSOC);
$labels = []; $values = [];
foreach ($trendData as $row) {
    $labels[] = date('M d', strtotime($row['date']));
    $values[] = $row['daily_total'];
}
function e($value) { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
?>