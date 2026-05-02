<?php
session_start();
require_once '../auth/conn.php'; 

/** @var PDO $pdo */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
  
    $lowStockCount = $pdo->query("SELECT COUNT(*) FROM products WHERE max_quantity > 0 AND (quantity / max_quantity) * 100 <= 15")->fetchColumn();

    $stmtATrans = $pdo->prepare("SELECT COUNT(*) FROM transfer_requests WHERE user_id = ? AND status NOT IN ('Approved', 'Declined')");
    $stmtATrans->execute([$user_id]);
    $activeTransfers = $stmtATrans->fetchColumn();

    $stmtAOrders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status NOT IN ('Approved', 'Delivered', 'Declined')");
    $stmtAOrders->execute([$user_id]);
    $activeOrders = $stmtAOrders->fetchColumn();

    $totalRequests = $activeTransfers + $activeOrders;

    $stmtPTrans = $pdo->prepare("SELECT COUNT(*) FROM transfer_requests WHERE user_id = ? AND status = 'Pending'");
    $stmtPTrans->execute([$user_id]);
    $pTransCount = $stmtPTrans->fetchColumn();

    $stmtPReports = $pdo->prepare("SELECT COUNT(*) FROM basic_reports WHERE user_id = ? AND status = 'Pending'");
    $stmtPReports->execute([$user_id]);
    $pReportsCount = $stmtPReports->fetchColumn(); 

    $stmtPOrders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'Pending'");
    $stmtPOrders->execute([$user_id]);
    $pOrdersCount = $stmtPOrders->fetchColumn();

    $pendingRequests = $pTransCount + $pOrdersCount + $pReportsCount;

    $stmtDTrans = $pdo->prepare("SELECT COUNT(*) FROM transfer_requests WHERE user_id = ? AND status = 'Approved' AND DATE(created_at) = CURDATE()");
    $stmtDTrans->execute([$user_id]);
    
    $stmtDOrders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND (status = 'Approved' OR status = 'Delivered') AND DATE(created_at) = CURDATE()");
    $stmtDOrders->execute([$user_id]);

    $approvedRequests = $stmtDTrans->fetchColumn() + $stmtDOrders->fetchColumn();

    $query = "
        (SELECT 'Transfer' as activity_type, item_name as main_label, qty as quantity, '' as product_name, '' as variation, status, created_at 
        FROM transfer_requests WHERE user_id = :uid1)
        UNION ALL
        (SELECT 'Order' as activity_type, customer_name as main_label, quantity as quantity, product_name as product_name, variation as variation, status, created_at 
        FROM orders WHERE user_id = :uid2)
        ORDER BY created_at DESC LIMIT 5";

    $stmt = $pdo->prepare($query);
    $stmt->execute(['uid1' => $user_id, 'uid2' => $user_id]);
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    die("System Maintenance. Please try again later.");
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
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        :root {
            --primary-orange: #f28c28;
            --success-green: #27ae60;
            --danger-red: #e74c3c;
            --text-main: #2c3e50;
            --text-muted: #858796;
            --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }

        body { background-color: #f8f9fc; color: var(--text-main); font-family: 'Inter', sans-serif; margin: 0; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px; padding: 25px; }
        
        .stat-card { 
            background: white; padding: 24px; border-radius: 12px; 
            box-shadow: var(--card-shadow); border-left: 5px solid var(--primary-orange);
            position: relative; transition: transform 0.2s ease;
        }
        .stat-card:hover { transform: translateY(-4px); }
        .stat-card h3 { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--primary-orange); margin-bottom: 8px; }
        .stat-card .value { font-size: 1.75rem; font-weight: 800; color: var(--text-main); }
        .stat-card p { font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; }
        .card-icon { position: absolute; right: 20px; top: 20px; font-size: 2.2rem; opacity: 0.15; color: var(--text-muted); }

        .bottom-row { padding: 0 25px 25px 25px; }
        .content-box { background: white; padding: 25px; border-radius: 12px; box-shadow: var(--card-shadow); }
        .content-box h2 { font-size: 1rem; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

        .activity-item { display: flex; align-items: center; padding: 12px; margin-bottom: 10px; background: #f8f9fc; border-radius: 8px; border: 1px solid #e3e6f0; }
        .activity-icon { width: 36px; height: 36px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; color: var(--primary-orange); margin-right: 12px; }

        .status-badge { font-size: 0.65rem; font-weight: 800; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; }
        .status-approved, .status-delivered { background: #e6fffa; color: #27ae60; }
        .status-pending { background: #fff4e6; color: #f28c28; }
        .status-declined, .status-cancelled { background: #ffebeb; color: #e74c3c; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Staff Panel</span></div>
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item active"><i class="fa-solid fa-table-columns"></i> <span>Dashboard</span></a>
                <a href="user_inventory.php" class="nav-item"><i class="fa-solid fa-box"></i> <span>Inventory</span></a>
                <a href="user_invLog.php" class="nav-item"><i class="fa-solid fa-clock-rotate-left"></i> <span>Inventory_Log</span></a>
                <a href="transfer_request.php" class="nav-item"><i class="fa-solid fa-right-left"></i> <span>My Transfers</span></a>
                <a href="sales.php" class="nav-item "><i class="fa-solid fa-coins"></i> <span>Sales</span></a>
                <a href="orders.php" class="nav-item"><i class="fa-solid fa-pen-to-square"></i> <span>Orders</span></a>
                <a href="basic_reports.php" class="nav-item"><i class="fa-solid fa-pen-to-square"></i> <span>My Reports</span></a>
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-user-gear"></i> <span>Profile</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-item"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header" style="padding: 15px 25px; background:#f28c28; border-bottom: 1px solid #f24628; margin-bottom:10px;">
                <div style="display:flex; align-items:center; gap:15px;">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1 style="font-size:1.25rem; font-weight:700;">Staff Overview</h1>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="stat-card" style="border-left-color: <?= $lowStockCount > 0 ? 'var(--danger-red)' : 'var(--success-green)' ?>;">
                    <h3>Stock Alerts</h3>
                    <div class="value" style="color: <?= $lowStockCount > 0 ? 'var(--danger-red)' : 'var(--success-green)' ?>;"><?= $lowStockCount ?></div>
                    <p>Items needing attention</p>
                    <i class="fa-solid fa-triangle-exclamation card-icon"></i>
                </div>

                <div class="stat-card">
                    <h3>My Requests</h3>
                    <div class="value"><?= $totalRequests ?></div>
                    <p>Current active tasks</p>
                    <i class="fa-solid fa-file-invoice card-icon"></i>
                </div>

                <div class="stat-card">
                    <h3>My Pending</h3>
                    <div class="value" style="color: var(--primary-orange);"><?= $pendingRequests ?></div>
                    <p>Awaiting admin approval</p>
                    <i class="fa-solid fa-hourglass-half card-icon"></i>
                </div>

                <div class="stat-card" style="border-left-color: var(--success-green);">
                    <h3>Today's Approved</h3>
                    <div class="value" style="color: var(--success-green);"><?= $approvedRequests ?></div>
                    <p>Completed today</p>
                    <i class="fa-solid fa-circle-check card-icon"></i>
                </div>
            </div>

            <div class="bottom-row">
                <div class="content-box">
                    <h2><i class="fa-solid fa-clock-rotate-left" style="color:var(--primary-orange)"></i> Recent Activity</h2>
                    <?php if(!empty($recentActivity)): ?>
                        <?php foreach ($recentActivity as $act): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fa-solid <?= $act['activity_type'] === 'Transfer' ? 'fa-right-left' : 'fa-cart-shopping' ?>"></i>
                                </div>
                                <div style="flex-grow: 1;">
                                    <div style="font-size: 0.85rem; font-weight: 700;">
                                        <span style="color: var(--text-muted); font-weight: 400; font-size: 0.7rem;">[<?= e($act['activity_type']) ?>]</span> 
                                        <?= e($act['main_label']) ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        Qty: <?= e($act['quantity']) ?> 
                                        <?= !empty($act['product_name']) ? "| Product: <span style='color:var(--primary-orange)'>".e($act['product_name'])."</span>" : "" ?>
                                        | <?= date('M d, g:i A', strtotime($act['created_at'])) ?>
                                    </div>
                                </div>
                                <span class="status-badge status-<?= strtolower($act['status']) ?>">
                                    <?= e($act['status']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align:center; padding:30px;">
                             <i class="fa-solid fa-inbox" style="font-size:2rem; color:#e3e6f0; margin-bottom:10px;"></i>
                             <p style="color:var(--text-muted); margin:0;">No recent activity yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        const sidebar = document.querySelector('.sidebar');
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    </script>
</body>
</html>