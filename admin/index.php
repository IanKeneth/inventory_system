<?php
session_start();
require_once '../auth/conn.php'; 

/** @var PDO $pdo */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$totalSales = 0; $dailySales = 0; $monthlySales = 0; $yearlySales = 0;
$avgOrderValue = 0; $lowStockCount = 0; $productCount = 0;
$totalPending = 0; $approvedRequests = 0;
$demandData = []; $labels = []; $values = [];

try {
    $stats = $pdo->query("SELECT 
        COALESCE(SUM(total_price), 0) as total_rev,
        COUNT(order_id) as order_count
        FROM orders WHERE status = 'Approved'")->fetch(PDO::FETCH_ASSOC);

    $totalSales = (float)$stats['total_rev'];
    $avgOrderValue = ($stats['order_count'] > 0) ? ($totalSales / $stats['order_count']) : 0;

    $dailySales = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'Approved' AND DATE(created_at) = CURDATE()")->fetchColumn();

    $monthlySales = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'Approved' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
    $yearlySales = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'Approved' AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();

    $productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $lowStockCount = $pdo->query("SELECT COUNT(*) FROM products WHERE max_quantity > 0 AND (quantity / max_quantity) <= 0.15")->fetchColumn();

    $totalPending = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();

    $demandStmt = $pdo->query("SELECT product_name, SUM(quantity) as total_qty, SUM(total_price) as revenue
                            FROM orders 
                            WHERE status = 'Approved'
                            GROUP BY product_id 
                            ORDER BY total_qty DESC LIMIT 5");
    $demandData = $demandStmt->fetchAll(PDO::FETCH_ASSOC);

    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i Day"));
        $labels[] = date('M d', strtotime($date));
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE DATE(created_at) = ? AND status = 'Approved'");
        $stmt->execute([$date]);
        $values[] = (float)$stmt->fetchColumn();
    }

} catch (PDOException $e) {
    $db_error = $e->getMessage();
}

/** @param mixed $value */
function e($value): string { 
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Inventory Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
    <style>
        :root {
            --primary-orange: #f28c28; --success-green: #27ae60; --danger-red: #e74c3c;
            --text-main: #2c3e50; --text-muted: #858796; --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            --corporate-blue: #4e73df;
        }
        body { background-color: #f8f9fc; color: var(--text-main); font-family: 'Inter', sans-serif; margin: 0; }
        
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; padding: 25px; }
        
        .stat-card { 
            background: white; padding: 20px; border-radius: 12px; box-shadow: var(--card-shadow);
            border-left: 5px solid var(--primary-orange); position: relative; transition: 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card h3 { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--primary-orange); margin: 0 0 10px 0; letter-spacing: 0.5px;}
        .stat-card .value { font-size: 1.5rem; font-weight: 800; color: #5a5c69; margin-bottom: 5px; }
        .stat-card .sub-value { font-size: 0.7rem; color: var(--text-muted); display: block; }
        .card-icon { position: absolute; right: 15px; top: 25px; font-size: 1.8rem; opacity: 0.1; color: #b7b9cc; }
        
        .analysis-row { display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 24px; padding: 0 25px 25px; }
        .content-box { background: white; padding: 25px; border-radius: 12px; box-shadow: var(--card-shadow); border: 1px solid #e3e6f0; }
        .content-box h2 { font-size: 0.95rem; font-weight: 700; margin: 0 0 20px 0; color: var(--corporate-blue); text-transform: uppercase; }

        .report-btn { 
            background: white; border: 1px solid #d1d3e2; padding: 8px 15px; 
            border-radius: 5px; font-size: 0.75rem; cursor: pointer; font-weight: 700; color: #6e707e;
        }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 0.65rem; color: #b7b9cc; padding: 12px; border-bottom: 1px solid #e3e6f0; text-transform: uppercase; }
        td { padding: 12px; font-size: 0.85rem; border-bottom: 1px solid #f8f9fc; }
        
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; background: #f8f9fc; color: var(--corporate-blue); border: 1px solid #e3e6f0; }
        
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
                <a href="inventory_logs.php" class="nav-item"><i class="fa-solid fa-route"></i> <span>Inventory Logs</span></a>
                <a href="track_request.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> <span>Track Requests</span></a>
                <a href="view_orders.php" class="nav-item"><i class="fa-solid fa-file-invoice-dollar"></i> <span>View Orders</span></a>
                <a href="User-management.php" class="nav-item"><i class="fa-solid fa-users"></i> <span>User Management</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-item" style="color:var(--danger-red);"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                <div style="display:flex; align-items:center; gap:15px;">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1 style="font-size:1.2rem; font-weight:700;">Operational Analytics</h1>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <div class="value">₱<?= number_format($totalSales, 2) ?></div>
                    <span class="sub-value"><i class="fa-solid fa-calendar"></i> Yearly: ₱<?= number_format($yearlySales) ?></span>
                    <i class="fa-solid fa-coins card-icon"></i>
                </div>
                <div class="stat-card" style="border-left-color: var(--corporate-blue);">
                    <h3>Sales Performance</h3>
                    <div class="value">₱<?= number_format($dailySales, 2) ?></div>
                    <span class="sub-value"><i class="fa-solid fa-calendar-check"></i> Monthly: ₱<?= number_format($monthlySales) ?></span>
                    <i class="fa-solid fa-chart-line card-icon"></i>
                </div>
                <div class="stat-card" style="border-left-color: <?= $lowStockCount > 0 ? 'var(--danger-red)' : 'var(--success-green)' ?>;">
                    <h3>Stock Alerts</h3>
                    <div class="value"><?= $lowStockCount ?></div>
                    <span class="sub-value">Items requiring attention</span>
                    <i class="fa-solid fa-triangle-exclamation card-icon"></i>
                </div>
                <div class="stat-card" style="border-left-color: #f6c23e;">
                    <h3>Pending Tasks</h3>
                    <div class="value"><?= $totalPending ?></div>
                    <span class="sub-value">Requests awaiting action</span>
                    <i class="fa-solid fa-clock card-icon"></i>
                </div>
            </div>

            <div class="analysis-row">
                <div class="content-box">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h2>Revenue Trend </h2>
                        <button onclick="exportExcel('sales')" class="report-btn">Export Trend</button>
                    </div>
                    <div style="height: 300px;"><canvas id="salesChart"></canvas></div>
                </div>

                <div class="content-box">
                    <h2>Top Product Analysis</h2>
                    <div style="height: 250px;"><canvas id="topSellersChart"></canvas></div>
                    <div style="margin-top: 15px; padding: 12px; background: #f8f9fc; border-radius: 8px; font-size: 0.75rem; border: 1px solid #e3e6f0;">
                        <i class="fa-solid fa-lightbulb" style="color:#f6c23e"></i> 
                        <strong>Insight:</strong> <?= !empty($demandData) ? e($demandData[0]['product_name']) . " is your top product." : "Insufficient data." ?>
                    </div>
                </div>
            </div>

            <div style="padding: 0 25px 25px;">
                <div class="content-box">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h2>Product Sales Performance</h2>
                        <button onclick="exportExcel('demand')" class="report-btn">Full Report</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Units Sold</th>
                                <th>Gross Revenue</th>
                                <th>Share of Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($demandData as $d): ?>
                            <tr>
                                <td style="font-weight:700;"><?= e($d['product_name']) ?></td>
                                <td><span class="badge"><?= $d['total_qty'] ?> Units</span></td>
                                <td>₱<?= number_format($d['revenue'], 2) ?></td>
                                <td>
                                    <?php $percent = ($totalSales > 0) ? ($d['revenue'] / $totalSales) * 100 : 0; ?>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div style="flex-grow:1; height:7px; background:#eaecf4; border-radius:10px; overflow:hidden;">
                                            <div style="width:<?= $percent ?>%; height:100%; background:var(--primary-orange);"></div>
                                        </div>
                                        <span style="font-size:0.75rem; font-weight:800;"><?= number_format($percent, 1) ?>%</span>
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
        // Line Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{ 
                    label: 'Revenue', 
                    data: <?= json_encode($values) ?>, 
                    borderColor: '#f28c28', 
                    backgroundColor: 'rgba(242, 140, 40, 0.05)', 
                    fill: true, tension: 0.3, pointRadius: 4
                }]
            },
            options: { 
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f3f3f3' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // Doughnut Chart
        const topCtx = document.getElementById('topSellersChart').getContext('2d');
        const demandData = <?= json_encode($demandData) ?>;
        new Chart(topCtx, {
            type: 'doughnut',
            data: {
                labels: demandData.map(d => d.product_name),
                datasets: [{
                    data: demandData.map(d => d.revenue),
                    backgroundColor: ['#f28c28', '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } }
            }
        });

        function exportExcel(type) {
            let data = [];
            if(type === 'demand') {
                data = [["Product", "Units Sold", "Revenue", "Share %"]];
                <?php foreach($demandData as $d): ?>
                data.push(["<?= e($d['product_name']) ?>", "<?= $d['total_qty'] ?>", "<?= $d['revenue'] ?>", "<?= number_format(($d['revenue'] / ($totalSales ?: 1)) * 100, 1) ?>%"]);
                <?php endforeach; ?>
            } else {
                data = [["Date", "Revenue"]];
                <?php for($i=0; $i<count($labels); $i++): ?>
                data.push(["<?= $labels[$i] ?>", "<?= $values[$i] ?>"]);
                <?php endfor; ?>
            }
            const ws = XLSX.utils.aoa_to_sheet(data);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Analytics");
            XLSX.writeFile(wb, `Report_${type}.xlsx`);
        }

        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>