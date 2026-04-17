<?php
session_start();
require_once '../auth/conn.php'; 

/** @var PDO $pdo */


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

try {
   
    $totalSales = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'Approved'")->fetchColumn();
    $dailySales = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'Approved' AND DATE(created_at) = CURDATE()")->fetchColumn();
    $monthlySales = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'Approved' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
    $yearlySales = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'Approved' AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();


    $stmt = $pdo->query("SELECT DATEDIFF(CURDATE(), MIN(created_at)) as days FROM orders");
    $daysSinceStart = $stmt->fetch()['days'] ?? 0;

 
    $productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $lowStockCount = $pdo->query("SELECT COUNT(*) FROM products WHERE (quantity / max_quantity) * 100 <= 15")->fetchColumn();


    $stmt = $pdo->query("SELECT o.*, p.product_name FROM orders o 
                        JOIN products p ON o.product_id = p.id 
                        ORDER BY o.created_at DESC LIMIT 5");
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

   
    $labels = [];
    $values = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i Month"));
        $labels[] = date('M Y ', strtotime($date));
        
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE DATE(created_at) = ? AND status = 'Approved'");
        $stmt->execute([$date]);
        $values[] = $stmt->fetchColumn();
    }

} catch (PDOException $e) {
    error_log("Dashboard Database Error: " . $e->getMessage());
    die("<div style='padding:50px; text-align:center; font-family:sans-serif;'><h2>⚠️ System Maintenance</h2><p>We are currently experiencing database issues. Please try again later.</p></div>");
}

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; padding: 25px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); position: relative; overflow: hidden; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card h3 { color: #7f8c8d; font-size: 0.9rem; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .stat-card .value { font-size: 1.8rem; font-weight: bold; color: #2c3e50; }
        .card-icon { position: absolute; right: -10px; bottom: -10px; font-size: 4.5rem; opacity: 0.07; color: #f28c28; }
        .sales-breakdown { display: flex; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #f8f9fa; }
        .sales-sub { flex: 1; }
        .sales-sub label { font-size: 0.6rem; color: #95a5a6; display: block; text-transform: uppercase; }
        .sales-sub span { font-size: 0.8rem; font-weight: 600; color: #2c3e50; }
        .bottom-row { display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px; padding: 0 25px 25px 25px; }
        @media (max-width: 992px) { .bottom-row { grid-template-columns: 1fr; } }
        .chart-box, .recent-orders-box { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .recent-orders-box h2, .chart-box h2 { font-size: 1.1rem; margin-bottom: 20px; color: #2c3e50; display: flex; align-items: center; gap: 10px; }
        .order-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #f8f9fa; }
        .order-row:last-child { border-bottom: none; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
        .status-pending { background: #fff4e6; color: #f28c28; }
        .status-approved { background: #e6fffa; color: #27ae60; }
        .status-shipped { background: #e3f2fd; color: #1e88e5; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 20px 25px; background: white; border-bottom: 1px solid #eee; }
        .report-group { position: relative; }
        .report-btn { background: #f28c28; color: white; padding: 10px 18px; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .report-btn:hover { background: #d3761d; }
        .report-dropdown-content { display: none; position: absolute; right: 0; background-color: white; min-width: 180px; box-shadow: 0px 8px 16px rgba(0,0,0,0.1); z-index: 1000; border-radius: 8px; margin-top: 5px; overflow: hidden; border: 1px solid #eee; }
        .report-dropdown-content a { color: #2c3e50; padding: 12px 16px; text-decoration: none; display: block; font-size: 0.85rem; transition: 0.2s; }
        .report-dropdown-content a:hover { background-color: #f8f9fa; color: #f28c28; }
        .show { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Admin Panel</span></div>
              <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> <span>Dashboard</span></a>
                <a href="user_inventory.php" class="nav-item"><i class="fa-solid fa-right-left"></i> <span>User Inventory</span></a>
                <a href="transfer_request.php" class="nav-item"><i class="fa-solid fa-right-left"></i> <span>Transfer Request</span></a>
                <a href="basic_reports.php" class="nav-item"><i class="fa-solid fa-pen-to-square"></i> <span>Basic Reports</span></a>
                <a href="orders.php" class="nav-item"><i class="fa-solid fa-pen-to-square"></i> <span>Order</span></a>
                <a href="sales.php" class="nav-item active"><i class="fa-solid fa-chart-simple"></i> <span>Sales</span></a>
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-user-gear"></i> <span>Profile</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-item"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left" style="display: flex; align-items: center; gap: 15px;">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1 style="margin: 0; font-size: 1.5rem; color: #2c3e50;">Management Overview</h1>
                </div>
                <div class="header-right">
                    <div class="report-group">
                        <button class="report-btn" onclick="toggleReportMenu()">
                            <i class="fa-solid fa-file-export"></i> Generate Report <i class="fa-solid fa-chevron-down" style="font-size: 0.7rem;"></i>
                        </button>
                        <div id="reportMenu" class="report-dropdown-content">
                            <a href="../add_products/generate_report.php?format=csv"><i class="fa-solid fa-file-csv"></i> Export as CSV</a>
                            <a href="../add_products/generate_report.php?format=excel"><i class="fa-solid fa-file-excel"></i> Export as Excel</a>
                            <a href="../add_products/generate_report.php?format=pdf" target="_blank"><i class="fa-solid fa-file-pdf"></i> View/Print PDF</a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="stat-card">
                    <h3>Revenue Overview</h3>
                    <div class="value">₱<?= number_format($totalSales, 2) ?></div>
                    <div class="sales-breakdown">
                        <div class="sales-sub"><label>Daily</label><span>₱<?= number_format($dailySales) ?></span></div>
                        <div class="sales-sub">
                            <label>Monthly</label>
                            <span><?= $daysSinceStart >= 30 ? '₱'.number_format($monthlySales) : 'Pending' ?></span>
                        </div>
                        <div class="sales-sub">
                            <label>Yearly</label>
                            <span><?= $daysSinceStart >= 365 ? '₱'.number_format($yearlySales) : 'Pending' ?></span>
                        </div>
                    </div>
                    <i class="fa-solid fa-sack-dollar card-icon"></i>
                </div>

                <div class="stat-card">
                    <h3>Active Products</h3>
                    <div class="value"><?= $productCount ?></div>
                    <p style="color: #7f8c8d; font-size: 0.8rem; margin-top:5px;">Currently in your catalog</p>
                    <i class="fa-solid fa-box card-icon"></i>
                </div>

                <div class="stat-card">
                    <h3>Stock Alerts</h3>
                    <div class="value" style="color: <?= $lowStockCount > 0 ? '#e74c3c' : '#27ae60' ?>;"><?= $lowStockCount ?></div>
                    <p style="color: #7f8c8d; font-size: 0.8rem; margin-top:5px;">Items below 15% stock</p>
                    <i class="fa-solid fa-bell card-icon"></i>
                </div>
            </div>

            <div class="bottom-row">
                <div class="chart-box">
                    <h2><i class="fa-solid fa-chart-line" style="color: #f28c28;"></i> 7-Day Sales Trend</h2>
                    <div style="height: 300px;">
                        <canvas id="salesTrendChart"></canvas>
                    </div>
                </div>

                <div class="recent-orders-box">
                    <h2><i class="fa-solid fa-clock-rotate-left" style="color: #f28c28;"></i> Recent Activity</h2>
                    <?php if (!empty($recentOrders)): ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <div class="order-row">
                                <div style="font-size: 0.85rem;">
                                    <strong><?= e($order['customer_name'] ?? 'Walk-in') ?></strong><br>
                                    <span style="color: #7f8c8d;"><?= e($order['product_name']) ?> (x<?= $order['qty'] ?? 1 ?>)</span>
                                </div>
                                <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                    <?= e($order['status']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align:center; padding: 40px 0;">
                            <i class="fa-solid fa-inbox" style="font-size: 2rem; color: #eee; margin-bottom: 10px;"></i>
                            <p style="color: #999;">No recent orders recorded.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        const ctx = document.getElementById('salesTrendChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(242, 140, 40, 0.4)');
        gradient.addColorStop(1, 'rgba(242, 140, 40, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Daily Revenue',
                    data: <?= json_encode($values) ?>,
                    borderColor: '#f28c28',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#f28c28'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { 
                        beginAtZero: true,
                        suggestedMax: 1000,   
                        ticks: {
                            precision: 0,      
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                        // ---------------------------------------
                    }
                }
            }
        });

        function toggleReportMenu() {
            document.getElementById("reportMenu").classList.toggle("show");
        }

        window.onclick = function(event) {
            if (!event.target.closest('.report-group')) {
                const dropdowns = document.getElementsByClassName("report-dropdown-content");
                for (let i = 0; i < dropdowns.length; i++) {
                    dropdowns[i].classList.remove('show');
                }
            }
        }
    </script>
</body>
</html>