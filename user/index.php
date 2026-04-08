
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; padding: 25px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); position: relative; overflow: hidden; }
        .stat-card h3 { color: #7f8c8d; font-size: 0.85rem; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .stat-card .value { font-size: 1.8rem; font-weight: bold; color: #2c3e50; margin-bottom: 15px; }
        .card-icon { position: absolute; right: -10px; bottom: -10px; font-size: 4.5rem; opacity: 0.07; color: #f28c28; }

        .sales-breakdown { display: flex; gap: 15px; border-top: 1px solid #f1f1f1; padding-top: 15px; }
        .sales-sub { flex: 1; }
        .sales-sub label { font-size: 0.65rem; color: #95a5a6; display: block; text-transform: uppercase; margin-bottom: 3px; }
        .sales-sub span { font-size: 0.9rem; font-weight: 700; color: #2c3e50; }

        .bottom-row { display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px; padding: 0 25px 25px 25px; }
        @media (max-width: 992px) { .bottom-row { grid-template-columns: 1fr; } }

        .chart-box, .recent-orders-box { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .order-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f8f9fa; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; text-transform: uppercase; }
        .status-pending { background: #fff4e6; color: #f28c28; }
        .status-approved, .status-delivered { background: #e6fffa; color: #27ae60; }
    </style>
</head>
<body>

    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fa-solid fa-boxes-stacked"></i> <span>Staff Dashboard</span>
            </div>
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item active ">
                    <i class="fa-solid fa-table-columns"></i> 
                    <span>Dashboard</span>
                </a>
                <a href="transfer_request.php" class="nav-item">
                    <i class="fa-solid fa-right-left"></i> 
                    <span>Transfer Request</span>
                </a>
                <a href="basic_reports.php" class="nav-item ">
                    <i class="fa-solid fa-pen-to-square"></i> 
                    <span>Basic Reports</span>
                </a>
                 <a href="orders.php" class="nav-item ">
                    <i class="fa-solid fa-pen-to-square"></i> 
                    <span>Order</span>
                </a>
                <a href="sales.php" class="nav-item">
                    <i class="fa-solid fa-chart-simple"></i> 
                    <span>Sales</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <i class="fa-solid fa-user-gear"></i> 
                    <span>Profile</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="../app/logout.php" class="nav-item"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1>STaff Dashboard</h1>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="stat-card">
                    <h3>Revenue Breakdown</h3>
                    <div class="value">₱<?= number_format($totalSales, 2) ?></div>
                    <div class="sales-breakdown">
                        <div class="sales-sub"><label>Daily</label><span>₱<?= number_format($dailySales, 0) ?></span></div>
                        <div class="sales-sub"><label>Monthly</label><span>₱<?= number_format($monthlySales, 0) ?></span></div>
                        <div class="sales-sub"><label>Yearly</label><span style="color: #f28c28;">₱<?= number_format($yearlySales, 0) ?></span></div>
                    </div>
                    <i class="fa-solid fa-sack-dollar card-icon"></i>
                </div>

                <div class="stat-card">
                    <h3>Active Products</h3>
                    <div class="value"><?= number_format($productCount) ?></div>
                    <p style="color: #7f8c8d; font-size: 0.8rem;">Items currently in catalog</p>
                    <i class="fa-solid fa-box card-icon"></i>
                </div>

                <div class="stat-card">
                    <h3>Stock Alerts</h3>
                    <div class="value" style="color: <?= $lowStockCount > 0 ? '#e74c3c' : '#27ae60' ?>;"><?= $lowStockCount ?></div>
                    <p style="color: #7f8c8d; font-size: 0.8rem;">Items near or below 15% stock</p>
                    <i class="fa-solid fa-triangle-exclamation card-icon" style="color: #e74c3c;"></i>
                </div>
            </div>

            <div class="bottom-row">
                <div class="chart-box">
                    <h2><i class="fa-solid fa-chart-line" style="color: #f28c28;"></i> Sales Trend (Last 7 Days)</h2>
                    <canvas id="salesTrendChart" style="max-height: 280px;"></canvas>
                </div>

                <div class="recent-orders-box">
                    <h2><i class="fa-solid fa-clock-rotate-left" style="color: #f28c28;"></i> Recent Activity</h2>
                    <?php if (count($recentOrders) > 0): ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <div class="order-row">
                                <div>
                                    <strong><?= e($order['customer_name']) ?></strong><br>
                                    <small style="color: #7f8c8d;"><?= e($order['product_name']) ?></small>
                                </div>
                                <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                    <?= e($order['status']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #999; text-align: center; padding-top: 20px;">No recent orders.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Sidebar Toggle
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });

        // Chart.js Implementation
        const ctx = document.getElementById('salesTrendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode($values) ?>,
                    borderColor: '#f28c28',
                    backgroundColor: 'rgba(242, 140, 40, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#f28c28'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, grid: { color: '#f8f9fa' } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>