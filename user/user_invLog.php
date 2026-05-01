<?php
session_start();
require_once "../auth/conn.php";
/** @var PDO $pdo */ 

// 1. Session & Role Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'Staff'; 

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'All';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$whereClauses = [];
$params = [];

// 2. Permission Logic: Only Staff are restricted to their own ID
if ($user_role !== 'Admin') {
    $whereClauses[] = "il.user_id = :current_user_id";
    $params[':current_user_id'] = $current_user_id;
}

if ($filter === 'In') {
    $whereClauses[] = "il.type = 'In'";
} elseif ($filter === 'Out') {
    $whereClauses[] = "il.type = 'Out'";
}

if (!empty($search)) {
    $whereClauses[] = "(p.product_name LIKE :search OR il.reason LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$whereSQL = !empty($whereClauses) ? " WHERE " . implode(" AND ", $whereClauses) : "";

// 3. Main Query
$query = "SELECT 
            il.*, 
            p.product_name, 
            p.variation,
            u.name AS staff_name 
            FROM inventory_logs il 
            JOIN products p ON il.product_id = p.id
            LEFT JOIN users u ON il.user_id = u.id 
            $whereSQL
            ORDER BY il.log_date DESC";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Restricted Summary Totals
$sumWhere = ($user_role !== 'Admin') ? " WHERE user_id = :uid" : "";
$sumParams = ($user_role !== 'Admin') ? [':uid' => $current_user_id] : [];

$tIn = $pdo->prepare("SELECT SUM(quantity) FROM inventory_logs $sumWhere " . ($sumWhere ? " AND " : " WHERE ") . " type = 'In'");
$tIn->execute($sumParams);
$totalIn = $tIn->fetchColumn() ?? 0;

$tOut = $pdo->prepare("SELECT SUM(quantity) FROM inventory_logs $sumWhere " . ($sumWhere ? " AND " : " WHERE ") . " type = 'Out'");
$tOut->execute($sumParams);
$totalOut = $tOut->fetchColumn() ?? 0;

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
    <title>Track & Reports - <?= e($user_role) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        :root { --primary: #f28c28; --success: #27ae60; --danger: #e53e3e; --bg-light: #f8fafc; }
        .report-container { padding: 30px; background: var(--bg-light); min-height: 100vh; }
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .s-card { background: white; padding: 20px; border-radius: 16px; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .s-icon { width: 55px; height: 55px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .controls-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .search-box { position: relative; flex: 1; max-width: 300px; }
        .search-box input { width: 100%; padding: 10px 15px 10px 40px; border-radius: 10px; border: 1px solid #e2e8f0; outline: none; }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .filter-group { display: flex; gap: 8px; }
        .filter-btn { padding: 10px 20px; border-radius: 10px; background: white; color: #64748b; text-decoration: none; font-size: 0.9rem; font-weight: 600; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; gap: 8px; }
        .filter-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
        .report-card { background: white; border-radius: 16px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); overflow: hidden; }
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; padding: 18px; text-align: left; font-size: 0.8rem; color: #475569; text-transform: uppercase; }
        td { padding: 18px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; display: inline-flex; align-items: center; gap: 5px; }
        .badge-in { background: #dcfce7; color: var(--success); }
        .badge-out { background: #fee2e2; color: var(--danger); }
        .staff-tag { font-size: 0.85rem; color: #1e293b; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Admin Panel</span></div>
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
        </aside>

        <main class="main-content">
            <header class="header">
                <div style="display:flex; align-items:center; gap:15px;">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1><?= $user_role === 'Admin' ? 'Global Activity Logs' : 'My Activity Logs' ?></h1>
                </div>
            </header>

            <section class="report-container">
                <div class="summary-cards">
                    <div class="s-card">
                        <div class="s-icon" style="background: #dcfce7; color: var(--success);"><i class="fa-solid fa-arrow-down"></i></div>
                        <div><p style="color:#64748b; margin:0;">Total In</p><h3 style="margin:0;"><?= number_format($totalIn) ?></h3></div>
                    </div>
                    <div class="s-card">
                        <div class="s-icon" style="background: #fee2e2; color: var(--danger);"><i class="fa-solid fa-arrow-up"></i></div>
                        <div><p style="color:#64748b; margin:0;">Total Out</p><h3 style="margin:0;"><?= number_format($totalOut) ?></h3></div>
                    </div>
                </div>

                <div class="controls-row">
                    <div class="filter-group">
                        <a href="?filter=All" class="filter-btn <?= $filter === 'All' ? 'active' : '' ?>">All Logs</a>
                        <a href="?filter=In" class="filter-btn <?= $filter === 'In' ? 'active' : '' ?>">Stock In</a>
                        <a href="?filter=Out" class="filter-btn <?= $filter === 'Out' ? 'active' : '' ?>">Stock Out</a>
                    </div>
                    <form action="" method="GET" class="search-box">
                        <input type="hidden" name="filter" value="<?= e($filter) ?>">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" placeholder="Search product..." value="<?= e($search) ?>">
                    </form>
                </div>

                <div class="report-card">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Performed By</th>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th>Qty</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td style="color:#64748b; font-size: 0.85rem;">
                                        <?= date('M d, Y', strtotime($log['log_date'])) ?><br>
                                        <?= date('h:i A', strtotime($log['log_date'])) ?>
                                    </td>
                                    <td class="staff-tag">
                                        <i class="fa-solid fa-user-<?= $log['staff_name'] == 'Admin' ? 'shield' : 'circle' ?>" style="color: var(--primary);"></i> 
                                        <?= e($log['staff_name'] ?? 'System') ?>
                                    </td>
                                    <td>
                                        <b><?= e($log['product_name']) ?></b>
                                    </td>
                                    <td>
                                        <span class="badge <?= $log['type'] == 'In' ? 'badge-in' : 'badge-out' ?>">
                                            <?= $log['type'] == 'In' ? 'STOCK IN' : 'STOCK OUT' ?>
                                        </span>
                                    </td>
                                    <td style="font-weight:bold; color: <?= $log['type'] == 'In' ? 'var(--success)' : 'var(--danger)' ?>;">
                                        <?= $log['type'] == 'In' ? '+' : '-' ?> <?= number_format($log['quantity']) ?>
                                    </td>
                                    <td style="color:#64748b; font-size: 0.9rem;"><?= e($log['reason']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });
    </script>
</body>
</html>