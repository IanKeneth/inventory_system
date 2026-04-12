<?php
require_once "../auth/conn.php";
/** @var PDO $pdo */ 

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'All';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// --- DATABASE LOGIC ---
$whereClauses = [];
if ($filter === 'In') {
    $whereClauses[] = "il.type = 'In'";
} elseif ($filter === 'Out') {
    $whereClauses[] = "il.type = 'Out'";
}

if (!empty($search)) {
    $whereClauses[] = "p.product_name LIKE :search";
}

$whereSQL = !empty($whereClauses) ? " WHERE " . implode(" AND ", $whereClauses) : "";

$query = "SELECT 
            il.id, 
            p.product_name, 
            p.variation,
            il.type, 
            il.quantity, 
            il.reason, 
            il.created_at 
            FROM inventory_logs il 
            JOIN products p ON il.product_id = p.id
            $whereSQL
            ORDER BY il.created_at DESC";

$stmt = $pdo->prepare($query);
if (!empty($search)) {
    $stmt->bindValue(':search', '%' . $search . '%'); 
}
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- EXPORT LOGIC (Must be before any HTML) ---
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = "Inventory_Report_" . date('Y-m-d_His') . ".csv";
    
    // Set headers to force download as Excel/CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    
    // Column Headers
    fputcsv($output, ['Timestamp', 'Product Name', 'Variation', 'Movement Type', 'Quantity', 'Reference Note']);
    
    // Data Rows
    foreach ($logs as $row) {
        fputcsv($output, [
            $row['created_at'],
            $row['product_name'],
            $row['variation'],
            $row['type'],
            $row['quantity'],
            $row['reason']
        ]);
    }
    fclose($output);
    exit; // Stop executing to prevent HTML from being added to the file
}

// Totals calculation
$totalIn = $pdo->query("SELECT SUM(quantity) FROM inventory_logs WHERE type = 'In'")->fetchColumn() ?? 0;
$totalOut = $pdo->query("SELECT SUM(quantity) FROM inventory_logs WHERE type = 'Out'")->fetchColumn() ?? 0;
$netStock = $totalIn - $totalOut;

function e($value) { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track & Reports - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        :root {
            --primary: #f28c28;
            --success: #27ae60;
            --danger: #e53e3e;
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
        }

        .report-container { padding: 30px; background: var(--bg-light); min-height: 100vh; }
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .s-card { background: white; padding: 20px; border-radius: 16px; display: flex; align-items: center; gap: 20px; transition: transform 0.2s; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .s-card:hover { transform: translateY(-5px); }
        .s-icon { width: 55px; height: 55px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }

        .controls-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .search-box { position: relative; flex: 1; max-width: 400px; }
        .search-box input { width: 100%; padding: 10px 15px 10px 40px; border-radius: 10px; border: 1px solid #e2e8f0; outline: none; }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }

        .filter-group { display: flex; gap: 8px; }
        .filter-btn, .export-btn { 
            padding: 10px 20px; 
            border-radius: 10px; 
            background: white; 
            color: #64748b; 
            text-decoration: none; 
            font-size: 0.9rem; 
            font-weight: 600;
            border: 1px solid #e2e8f0;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .filter-btn.active { background: var(--primary); color: white; border-color: var(--primary); box-shadow: 0 4px 12px rgba(242, 140, 40, 0.3); }
        
        .export-btn { background: #1e293b; color: white; border: none; }
        .export-btn:hover { background: #0f172a; cursor: pointer; }

        .report-card { background: white; border-radius: 16px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); overflow: hidden; }
        .table-responsive { width: 100%; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; padding: 18px; text-align: left; font-size: 0.8rem; letter-spacing: 0.05em; color: #475569; text-transform: uppercase; }
        td { padding: 18px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:hover { background-color: #f8fafc; }

        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; display: inline-flex; align-items: center; gap: 5px; }
        .badge-in { background: #dcfce7; color: var(--success); }
        .badge-out { background: #fee2e2; color: var(--danger); }
        
        .product-info b { color: var(--text-dark); display: block; margin-bottom: 2px; }
        .product-info small { color: var(--primary); font-weight: 600; }
        .qty-text { font-family: 'Courier New', monospace; font-size: 1.1rem; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Admin Panel</span></div>
              <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a>
                <a href="inventory.php" class="nav-item"><i class="fa-solid fa-boxes-packing"></i> <span>Inventory</span></a>
                <a href="supplies.php" class="nav-item"><i class="fa-solid fa-truck-ramp-box"></i> <span>Supplies</span></a>
                <a href="track&reports.php" class="nav-item"><i class="fa-solid fa-route"></i> <span>Track & Reports</span></a>
                <a href="track_request.php" class="nav-item active"><i class="fa-solid fa-clipboard-list"></i> <span>Track Requests</span></a>
                
                <a href="view_orders.php" class="nav-item"><i class="fa-solid fa-file-invoice-dollar"></i> <span>View Orders</span></a>
                <a href="User-management.php" class="nav-item"><i class="fa-solid fa-users"></i> <span>User Management</span></a>
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-gears"></i> <span>Settings</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left"><h1>Analytics & History</h1></div>
                <div class="header-right">
                    <a href="?export=excel&filter=<?= $filter ?>&search=<?= urlencode($search) ?>" class="export-btn">
                        <i class="fa-solid fa-file-excel"></i> Export to Excel
                    </a>
                </div>
            </header>

            <section class="report-container">
                <div class="summary-cards">
                    <div class="s-card">
                        <div class="s-icon" style="background: #dcfce7; color: var(--success);"><i class="fa-solid fa-layer-group"></i></div>
                        <div><p style="color:#64748b; margin:0; font-size: 0.9rem;">Total Stock In</p><h3 style="margin:0;"><?= number_format($totalIn) ?></h3></div>
                    </div>
                    <div class="s-card">
                        <div class="s-icon" style="background: #fee2e2; color: var(--danger);"><i class="fa-solid fa-cart-arrow-down"></i></div>
                        <div><p style="color:#64748b; margin:0; font-size: 0.9rem;">Stock Out</p><h3 style="margin:0;"><?= number_format($totalOut) ?></h3></div>
                    </div>
                    <div class="s-card" style="border-left: 5px solid var(--primary);">
                        <div class="s-icon" style="background: #fef3c7; color: var(--primary);"><i class="fa-solid fa-scale-balanced"></i></div>
                        <div><p style="color:#64748b; margin:0; font-size: 0.9rem;">Available Balance</p><h3 style="margin:0;"><?= number_format($netStock) ?></h3></div>
                    </div>
                </div>

                <div class="controls-row">
                    <div class="filter-group">
                        <a href="?filter=All" class="filter-btn <?= $filter === 'All' ? 'active' : '' ?>">All Logs</a>
                        <a href="?filter=In" class="filter-btn <?= $filter === 'In' ? 'active' : '' ?>">Additions</a>
                        <a href="?filter=Out" class="filter-btn <?= $filter === 'Out' ? 'active' : '' ?>">Sales</a>
                    </div>

                    <form action="" method="GET" class="search-box">
                        <input type="hidden" name="filter" value="<?= e($filter) ?>">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" placeholder="Search product name..." value="<?= e($search) ?>">
                    </form>
                </div>

                <div class="report-card">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Product Details</th>
                                    <th>Movement</th>
                                    <th>Quantity</th>
                                    <th>Reference Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($logs)): ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td style="color:#64748b; font-size: 0.85rem;">
                                                <i class="fa-regular fa-calendar-check"></i> <?= date('M d, Y', strtotime($log['created_at'])) ?><br>
                                                <i class="fa-regular fa-clock"></i> <?= date('h:i A', strtotime($log['created_at'])) ?>
                                            </td>
                                            <td class="product-info">
                                                <b><?= e($log['product_name']) ?></b>
                                                <small><?= e($log['variation']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?= $log['type'] == 'In' ? 'badge-in' : 'badge-out' ?>">
                                                    <i class="fa-solid <?= $log['type'] == 'In' ? 'fa-arrow-up' : 'fa-arrow-down' ?>"></i>
                                                    <?= $log['type'] == 'In' ? 'STOCK IN' : 'SALE / OUT' ?>
                                                </span>
                                            </td>
                                            <td class="qty-text" style="color: <?= $log['type'] == 'In' ? 'var(--success)' : 'var(--danger)' ?>">
                                                <?= $log['type'] == 'In' ? '+' : '-' ?> <?= number_format($log['quantity']) ?>
                                            </td>
                                            <td style="color: #64748b; font-size: 0.9rem;">
                                                <i class="fa-solid fa-tag" style="font-size: 0.7rem; opacity: 0.5;"></i> <?= e($log['reason']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center; padding:100px;">
                                            <p style="color:#94a3b8;">No activity logs match your criteria.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>