<?php
session_start();
require_once "../auth/conn.php";

// Role Protection: Ensure only Admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// ── Search & Filter ───────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$params = [];
$whereSQL = "";

if ($search !== '') {
    $whereSQL = " WHERE supplier_name LIKE :search OR contact_person LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

// ── Fetch Suppliers ───────────────────────────────────────────
$query = "SELECT * FROM suppliers $whereSQL ORDER BY id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$suppliers = $stmt->fetchAll();

// ── Totals for Summary Cards ──────────────────────────────────
$totalSuppliers = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
$recentDeliveries = $pdo->query("SELECT COUNT(*) FROM inventory_logs WHERE type='In' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers – Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        :root {
            --primary: #f28c28;
            --secondary: #64748b;
            --success: #27ae60;
            --bg: #f8fafc;
            --dark: #1e293b;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-icon {
            font-size: 2rem;
            color: var(--primary);
            background: #fff3e6;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }

        .supplier-table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .action-link {
            padding: 5px 10px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            margin-right: 5px;
            border: 1px solid #e2e8f0;
            transition: 0.2s;
        }

        .action-link:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .contact-pill {
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            color: var(--secondary);
            display: inline-block;
        }
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
            <div class="header-left">
                <h1>Supplier Management</h1>
            </div>
            <div class="header-right">
                <button class="btn-primary" style="padding: 10px 20px; border-radius: 8px; border:none; cursor:pointer; background: var(--primary); color:white;">
                    <i class="fa-solid fa-plus"></i> Add New Supplier
                </button>
            </div>
        </header>

        <section style="padding: 25px;">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-building-user"></i></div>
                    <div>
                        <p style="color: var(--secondary); margin:0;">Total Suppliers</p>
                        <h2 style="margin:5px 0 0;"><?= $totalSuppliers ?></h2>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color:var(--success); background:#e6f4ea;"><i class="fa-solid fa-truck-fast"></i></div>
                    <div>
                        <p style="color: var(--secondary); margin:0;">Weekly Deliveries</p>
                        <h2 style="margin:5px 0 0;"><?= $recentDeliveries ?></h2>
                    </div>
                </div>
            </div>

            <form method="GET" style="margin-bottom: 20px; max-width: 400px; position: relative;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute; left: 15px; top: 12px; color: #94a3b8;"></i>
                <input type="text" name="search" placeholder="Search supplier or contact..." value="<?= htmlspecialchars($search) ?>" 
                       style="width:100%; padding: 10px 10px 10px 45px; border-radius: 10px; border: 1px solid #e2e8f0; outline:none;">
            </form>

            <div class="supplier-table-card">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; text-align: left; border-bottom: 2px solid #edf2f7;">
                            <th style="padding: 18px;">Supplier Name</th>
                            <th>Contact Person</th>
                            <th>Email / Phone</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($suppliers): foreach ($suppliers as $s): ?>
                        <tr style="border-bottom: 1px solid #edf2f7;">
                            <td style="padding: 18px;">
                                <strong><?= htmlspecialchars($s['supplier_name']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($s['contact_person']) ?></td>
                            <td>
                                <span class="contact-pill"><?= htmlspecialchars($s['email']) ?></span><br>
                                <small style="color: #94a3b8;"><?= htmlspecialchars($s['phone']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($s['category'] ?? 'General') ?></td>
                            <td>
                                <span style="color: var(--success); font-size: 0.8rem; font-weight: bold;">● Active</span>
                            </td>
                            <td>
                                <a href="#" class="action-link"><i class="fa-solid fa-pen"></i></a>
                                <a href="#" class="action-link" style="color: var(--danger);"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="6" style="padding: 50px; text-align: center; color: #94a3b8;">No suppliers found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
</body>
</html>