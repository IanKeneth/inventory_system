<?php
session_start();
require_once '../auth/conn.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$totalSales = 0; $monthlySales = 0; $yearlySales = 0; $dailySales = 0;
$lastMonthSales = 0; $lowStockCount = 0; $activeProductCount = 0;
$totalPending = 0;
$monthlyLabels = []; $monthlyValues = [];
$demandData = [];

try {
    $totalSales = (float)$pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'Approved'")->fetchColumn();

    $monthlySales = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'Approved' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();

    $lastMonthSales = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'Approved' AND created_at >= DATE_SUB(DATE_FORMAT(NOW() ,'%Y-%m-01'), INTERVAL 1 MONTH) AND created_at < DATE_FORMAT(NOW() ,'%Y-%m-01')")->fetchColumn();
    
    $yearlySales = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'Approved' AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();

    $dailySales = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'Approved' AND DATE(created_at) = CURDATE()")->fetchColumn();


    $activeProductCount = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity > 0")->fetchColumn();
    $lowStockCount = $pdo->query("SELECT COUNT(*) FROM products WHERE max_quantity > 0 AND (quantity / max_quantity) <= 0.15")->fetchColumn();
    $totalPending = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();

    for ($i = 5; $i >= 0; $i--) {
        $timestamp = strtotime("-$i Month");
        $m = date('m', $timestamp);
        $y = date('Y', $timestamp);
        
        $monthlyLabels[] = date('M', $timestamp);
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE MONTH(created_at) = ? AND YEAR(created_at) = ? AND status = 'Approved'");
        $stmt->execute([$m, $y]);
        $monthlyValues[] = (float)$stmt->fetchColumn();
    }

    $demandStmt = $pdo->query("SELECT product_name, SUM(quantity) as total_qty, SUM(total_price) as revenue FROM orders WHERE status = 'Approved' GROUP BY product_id ORDER BY total_qty DESC LIMIT 5");
    $demandData = $demandStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { $db_error = $e->getMessage(); }

$growth = ($lastMonthSales > 0) ? (($monthlySales - $lastMonthSales) / $lastMonthSales) * 100 : 0;
function e($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Live Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-orange: #f28c28; --success-green: #27ae60; --danger-red: #e74c3c;
            --text-main: #2c3e50; --text-muted: #858796; --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            --corporate-blue: #4e73df;
        }
        body { background-color: #f8f9fc; color: var(--text-main); font-family: 'Inter', sans-serif; margin: 0; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; padding: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: var(--card-shadow); border-left: 5px solid var(--primary-orange); position: relative; transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card h3 { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--primary-orange); margin: 0 0 10px 0; }
        .stat-card .value { font-size: 1.5rem; font-weight: 800; color: #5a5c69; display: flex; align-items: center; gap: 8px;}
        .growth-badge { font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; font-weight: 700; }
        .up { background: #e1f5e9; color: var(--success-green); }
        .down { background: #fdeaea; color: var(--danger-red); }
        .analysis-row { display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 24px; padding: 0 25px 25px; }
        .content-box { background: white; padding: 25px; border-radius: 12px; box-shadow: var(--card-shadow); border: 1px solid #e3e6f0; }
        .content-box h2 { font-size: 0.9rem; font-weight: 700; margin-bottom: 20px; color: var(--corporate-blue); text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 0.65rem; color: #b7b9cc; padding: 12px; border-bottom: 1px solid #e3e6f0; text-transform: uppercase; }
        td { padding: 12px; font-size: 0.85rem; border-bottom: 1px solid #f8f9fc; }
        .card-icon { position: absolute; right: 15px; top: 25px; font-size: 1.8rem; opacity: 0.1; color: #b7b9cc; }
        @media (max-width: 992px) { .analysis-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Admin Panel</span></div>
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item active"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a>
                <a href="inventory.php" class="nav-item"><i class="fa-solid fa-boxes-packing"></i> <span>Inventory</span></a>
                <a href="supplies.php" class="nav-item"><i class="fa-solid fa-truck-ramp-box"></i> <span>Supplies</span></a>
                <a href="view_orders.php" class="nav-item"><i class="fa-solid fa-file-invoice-dollar"></i> <span>View Orders</span></a>
                <a href="User-management.php" class="nav-item"><i class="fa-solid fa-users"></i> <span>User Management</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-item" style="color:var(--danger-red);"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header" style="padding: 20px 25px; background:#f28c28; border-bottom: 1px solid #f28c28;">
                <div style="display:flex; align-items:center; gap:15px;">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1 style="font-size:1.1rem; font-weight:700; ">Dashboard Overview</h1>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="stat-card" style="border-left-color: #36b9cc;">
                    <h3 style="color: #36b9cc;">Daily Revenue</h3>
                    <div class="value">₱<?= number_format($dailySales, 2) ?></div>
                    <span class="sub-value">Earnings Today</span>
                    <i class="fa-solid fa-calendar-day card-icon"></i>
                </div>

                <div class="stat-card">
                    <h3>Monthly Revenue</h3>
                    <div class="value">
                        ₱<?= number_format($monthlySales, 2) ?>
                        <span class="growth-badge <?= $growth >= 0 ? 'up' : 'down' ?>">
                            <?= $growth >= 0 ? '↑' : '↓' ?> <?= abs(round($growth, 1)) ?>%
                        </span>
                    </div>
                    <span class="sub-value">Previous Month: ₱<?= number_format($lastMonthSales) ?></span>
                    <i class="fa-solid fa-coins card-icon"></i>
                </div>

                <div class="stat-card" style="border-left-color: var(--corporate-blue);">
                    <h3 style="color: var(--corporate-blue);">Yearly Revenue</h3>
                    <div class="value">₱<?= number_format($yearlySales, 2) ?></div>
                    <span class="sub-value">Total for <?= date('Y') ?></span>
                    <i class="fa-solid fa-chart-bar card-icon"></i>
                </div>

                <div class="stat-card" style="border-left-color: var(--danger-red);">
                    <h3 style="color: var(--danger-red);">Stock Alerts</h3>
                    <div class="value"><?= $lowStockCount ?></div>
                    <span class="sub-value">Items near depletion</span>
                    <i class="fa-solid fa-triangle-exclamation card-icon"></i>
                </div>

                <div class="stat-card" style="border-left-color: var(--success-green);">
                    <h3 style="color: var(--success-green);">Active Inventory</h3>
                    <div class="value"><?= $activeProductCount ?></div>
                    <span class="sub-value">Products in stock</span>
                    <i class="fa-solid fa-box-open card-icon"></i>
                </div>

                <div class="stat-card" style="border-left-color: #f6c23e;">
                    <h3 style="color: #f6c23e;">Pending Approvals</h3>
                    <div class="value"><?= $totalPending ?></div>
                    <span class="sub-value">Requests needing action</span>
                    <i class="fa-solid fa-clock card-icon"></i>
                </div>
            </div>

            <div class="analysis-row">
                <div class="content-box">
                    <h2>6-Month Revenue Trajectory</h2>
                    <div style="height: 320px;"><canvas id="liveTrendChart"></canvas></div>
                </div>

                <div class="content-box">
                    <h2>Top Sellers Contribution</h2>
                    <div style="height: 250px;"><canvas id="shareChart"></canvas></div>
                </div>
            </div>

            <div style="padding: 0 25px 25px;">
                <div class="content-box">
                    <h2>Product Performance Details</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Sold</th>
                                <th>Revenue</th>
                                <th>Share %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($demandData as $d): ?>
                            <tr>
                                <td style="font-weight:700;"><?= e($d['product_name']) ?></td>
                                <td><span class="badge"><?= $d['total_qty'] ?></span></td>
                                <td>₱<?= number_format($d['revenue'], 2) ?></td>
                                <td>
                                    <?php $p = ($totalSales > 0) ? ($d['revenue'] / $totalSales) * 100 : 0; ?>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div style="flex-grow:1; height:6px; background:#eee; border-radius:10px; overflow:hidden;">
                                            <div style="width:<?= $p ?>%; height:100%; background:var(--primary-orange);"></div>
                                        </div>
                                        <span style="font-size:0.7rem; font-weight:700;"><?= round($p, 1) ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        const ctxTrend = document.getElementById('liveTrendChart').getContext('2d');
        new Chart(ctxTrend, {
            data: {
                labels: <?= json_encode($monthlyLabels) ?>,
                datasets: [
                    {
                        type: 'line',
                        label: 'Trend',
                        data: <?= json_encode($monthlyValues) ?>,
                        borderColor: '#f28c28',
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        type: 'bar',
                        label: 'Revenue',
                        data: <?= json_encode($monthlyValues) ?>,
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        borderColor: '#4e73df',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true }, x: { grid: { display: false } } }
            }
        });

        const ctxShare = document.getElementById('shareChart').getContext('2d');
        new Chart(ctxShare, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($demandData, 'product_name')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($demandData, 'revenue')) ?>,
                    backgroundColor: ['#f28c28', '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: { legend: { position: 'bottom' } }
            }
        });

        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('active');
        });
         document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });
    </script>
</body>
</html>