<?php
session_start();
require_once '../auth/conn.php'; 

/** @var PDO $pdo */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

try {
    // REVENUE QUERIES 
    $totalSales = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'Approved'")->fetchColumn();
    $dailySales = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'Approved' AND DATE(created_at) = CURDATE()")->fetchColumn();
    $monthlySales = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'Approved' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
    $yearlySales = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'Approved' AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();

    // INVENTORY & ALERTS
    $productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $lowStockCount = $pdo->query("SELECT COUNT(*) FROM products WHERE max_quantity > 0 AND (quantity / max_quantity) * 100 <= 15")->fetchColumn();

    //REQUEST TRACKING
    $totalRequests = $pdo->query("SELECT COUNT(*) FROM transfer_requests")->fetchColumn();
    $pendingRequests = $pdo->query("SELECT COUNT(*) FROM transfer_requests WHERE status = 'Pending'")->fetchColumn();
    $approvedRequests = $pdo->query("SELECT COUNT(*) FROM transfer_requests WHERE status = 'Approved'")->fetchColumn();

    // RECENT ACTIVITY
    $stmt = $pdo->query("SELECT o.*, p.product_name FROM orders o 
                        JOIN products p ON o.product_id = p.id 
                        ORDER BY o.created_at DESC LIMIT 5");
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = []; $values = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i Day"));
        $labels[] = date('M d', strtotime($date));
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE DATE(created_at) = ? AND status = 'Approved'");
        $stmt->execute([$date]);
        $values[] = $stmt->fetchColumn();
    }
} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    die("System Maintenance. Please try again later.");
}

function e($value) { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Dashboard | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-orange: #f28c28;
            --success-green: #27ae60;
            --danger-red: #e74c3c;
            --text-main: #2c3e50;
            --text-muted: #858796;
            --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }

        body { background-color: #f8f9fc; color: var(--text-main); font-family: 'Inter', sans-serif; }

        .dashboard-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); 
            gap: 24px; 
            padding: 25px; 
        }


        .stat-card { 
            background: white; 
            padding: 24px; 
            border-radius: 12px; 
            box-shadow: var(--card-shadow); 
            border-left: 5px solid var(--primary-orange);
            position: relative; 
            transition: transform 0.2s ease;
        }
        .stat-card:hover { transform: translateY(-4px); }

        .stat-card h3 { 
            font-size: 0.75rem; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 0.1em; 
            color: var(--primary-orange); 
            margin-bottom: 8px;
        }

        .stat-card .value { font-size: 1.75rem; font-weight: 800; color: #4e73df; color: var(--text-main); }
        .stat-card p { font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; }

        .card-icon { 
            position: absolute; right: 20px; top: 20px; 
            font-size: 2.2rem; opacity: 0.15; color: var(--text-muted); 
        }

        .revenue-footer { 
            display: flex; gap: 15px; margin-top: 15px; 
            padding-top: 12px; border-top: 1px solid #eaecf4; 
        }
        .rev-item label { display: block; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; }
        .rev-item span { font-size: 0.85rem; font-weight: 700; color: var(--text-main); }


        .bottom-row { display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px; padding: 0 25px 25px 25px; }
        @media (max-width: 1024px) { .bottom-row { grid-template-columns: 1fr; } }

        .content-box { background: white; padding: 25px; border-radius: 12px; box-shadow: var(--card-shadow); }
        .content-box h2 { font-size: 1rem; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

        .activity-item {
            display: flex; align-items: center; padding: 12px; margin-bottom: 10px;
            background: #f8f9fc; border-radius: 8px; border: 1px solid #e3e6f0;
        }
        .activity-icon {
            width: 36px; height: 36px; border-radius: 50%; background: white;
            display: flex; align-items: center; justify-content: center;
            color: var(--primary-orange); margin-right: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .status-badge {
            font-size: 0.65rem; font-weight: 800; padding: 4px 10px;
            border-radius: 20px; text-transform: uppercase;
        }
        .status-approved { background: #e6fffa; color: #27ae60; }
        .status-pending { background: #fff4e6; color: #f28c28; }
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
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-gears"></i> <span>Settings</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-item" style="color:var(--danger-red);"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                <div style="display:flex; align-items:center; gap:15px;">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1 style="font-size:1.25rem; font-weight:700;">Dashboard Overview</h1>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <div class="value">₱<?= number_format($totalSales, 2) ?></div>
                    <div class="revenue-footer">
                        <div class="rev-item"><label>Daily</label><span>₱<?= number_format($dailySales) ?></span></div>
                        <div class="rev-item"><label>Monthly</label><span>₱<?= number_format($monthlySales) ?></span></div>
                        <div class="rev-item"><label>Yearly</label><span>₱<?= number_format($yearlySales) ?></span></div>
                    </div>
                    <i class="fa-solid fa-coins card-icon"></i>
                </div>

                <div class="stat-card">
                    <h3>Active Products</h3>
                    <div class="value"><?= $productCount ?></div>
                    <p>Total items in catalog</p>
                    <i class="fa-solid fa-box card-icon"></i>
                </div>

                <div class="stat-card" style="border-left-color: <?= $lowStockCount > 0 ? 'var(--danger-red)' : 'var(--success-green)' ?>;">
                    <h3>Stock Alerts</h3>
                    <div class="value" style="color: <?= $lowStockCount > 0 ? 'var(--danger-red)' : 'var(--success-green)' ?>;"><?= $lowStockCount ?></div>
                    <p>Items reaching low levels</p>
                    <i class="fa-solid fa-triangle-exclamation card-icon"></i>
                </div>

                <div class="stat-card">
                    <h3>Staff Requests</h3>
                    <div class="value"><?= $totalRequests ?></div>
                    <p>Total transfer requests</p>
                    <i class="fa-solid fa-file-invoice card-icon"></i>
                </div>

                <div class="stat-card">
                    <h3>Pending</h3>
                    <div class="value" style="color: var(--primary-orange);"><?= $pendingRequests ?></div>
                    <p>Awaiting admin action</p>
                    <i class="fa-solid fa-hourglass-half card-icon"></i>
                </div>

                <div class="stat-card">
                    <h3>Approved</h3>
                    <div class="value" style="color: var(--success-green);"><?= $approvedRequests ?></div>
                    <p>Successfully processed</p>
                    <i class="fa-solid fa-circle-check card-icon"></i>
                </div>
            </div>

            <div class="bottom-row">
                <div class="content-box">
                    <h2><i class="fa-solid fa-chart-line" style="color:var(--primary-orange)"></i> Sales Performance</h2>
                    <div style="height: 320px;"><canvas id="salesTrendChart"></canvas></div>
                </div>

                <div class="content-box">
                    <h2><i class="fa-solid fa-list-ul" style="color:var(--primary-orange)"></i> Recent Activity</h2>
                    <?php if(!empty($recentOrders)): ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <div class="activity-item">
                                <div class="activity-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                                <div style="flex-grow: 1;">
                                    <div style="font-size: 0.85rem; font-weight: 700;"><?= e($order['product_name']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($order['customer_name'] ?? 'Direct Sale') ?></div>
                                </div>
                                <span class="status-badge status-<?= strtolower($order['status']) ?>"><?= e($order['status']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align:center; color:var(--text-muted); padding:20px;">No recent transactions found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        const ctx = document.getElementById('salesTrendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode($values) ?>,
                    borderColor: '#f28c28',
                    backgroundColor: 'rgba(242, 140, 40, 0.05)',
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
                plugins: { legend: { display: false } },
                scales: { 
                    y: { 
                        beginAtZero: true,
                        suggestedMax: 1000,
                        grid: { color: "#f3f3f3", drawBorder: false },
                        ticks: { font: { size: 11 }, callback: v => '₱' + v.toLocaleString() }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });

        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>