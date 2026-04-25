<?php
session_start();
require_once "../auth/conn.php";
if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['type'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    $type = $_GET['type'];
    $newStatus = ($action === 'approve') ? 'Approved' : 'Declined';

    try {
        if ($type === 'transfer') {
            $sql = "UPDATE transfer_requests SET status = ? WHERE id = ?";
        } else {
            $sql = "UPDATE basic_reports SET status = ? WHERE id = ?";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$newStatus, $id]);
        
        header("Location: track_request.php?msg=Status Updated&type=$type");
        exit();
    } catch (PDOException $e) {
        die("Error updating status: " . $e->getMessage());
    }
}

try {
    $sql_transfers = "SELECT t.*, u.name 
            FROM transfer_requests t 
            JOIN users u ON t.user_id = u.id 
            ORDER BY FIELD(t.status, 'Pending', 'Approved', 'Declined'), t.request_date DESC";

    $transfer_requests = $pdo->query($sql_transfers)->fetchAll(PDO::FETCH_ASSOC);

    $sql_reports = "SELECT r.*, u.name 
                    FROM basic_reports r 
                    JOIN users u ON r.user_id = u.id 
                    ORDER BY FIELD(r.status, 'Pending', 'Approved', 'Declined'), r.created_at DESC";
    $basic_reports = $pdo->query($sql_reports)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Unified Tracking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .tab-container { display: flex; gap: 15px; margin: 20px; }
        .tab-btn { padding: 10px 25px; border: 2px solid #f28c28; background: white; color: #f28c28; border-radius: 30px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .tab-btn.active { background: #f28c28; color: white; }
        
        .content-section { display: none; }
        .content-section.active { display: block; }
        .request-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin: 20px; overflow-x: auto; }
        
        .admin-table { width: 100%; border-collapse: collapse; min-width: 900px; }
        .admin-table th { background: #f28c28; color: white; padding: 12px; text-align: left; font-size: 0.75rem; text-transform: uppercase; }
        .admin-table td { padding: 12px; border-bottom: 1px solid #eee; font-size: 0.85rem; vertical-align: top; }

        .status-pill { padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: bold; text-transform: uppercase; color: white; display: inline-block; }
        .status-pending { background: #f1c40f; }
        .status-approved { background: #2ecc71; }
        .status-declined { background: #e74c3c; }

        .btn-action { padding: 5px 10px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.7rem; color: white; margin-right: 5px; display: inline-block; }
        .btn-approve { background: #2ecc71; border: 1px solid #27ae60; }
        .btn-decline { background: #e74c3c; border: 1px solid #c0392b; }
        .badge-cat { border: 1px solid #f28c28; color: #f28c28; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; white-space: nowrap; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-user-shield"></i> <span>Admin Panel</span></div>
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a>
                <a href="inventory.php" class="nav-item"><i class="fa-solid fa-boxes-packing"></i> <span>Inventory</span></a>
                <a href="supplies.php" class="nav-item"><i class="fa-solid fa-truck-ramp-box"></i> <span>Supplies</span></a>
                <a href="inventory_logs.php" class="nav-item"><i class="fa-solid fa-route"></i> <span>Inventory Logs</span></a>
                <a href="track_request.php" class="nav-item active"><i class="fa-solid fa-clipboard-list"></i> <span>Track Requests</span></a>
                <a href="view_orders.php" class="nav-item"><i class="fa-solid fa-file-invoice-dollar"></i> <span>View Orders</span></a>
                <a href="User-management.php" class="nav-item"><i class="fa-solid fa-users"></i> <span>User Management</span></a>
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-gears"></i> <span>Settings</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-item" style="color: #e74c3c;"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1>Management Tracking</h1>
                </div>
            </header>

            <div class="tab-container">
                <button class="tab-btn active" onclick="switchTab(event, 'transfer-tab')">Transfer Requests</button>
                <button class="tab-btn" onclick="switchTab(event, 'reports-tab')">Basic Reports</button>
            </div>

            <div id="transfer-tab" class="content-section active">
                <section class="request-card">
                    <h2 style="margin-bottom:15px; color:#2c3e50;"><i class="fa-solid fa-right-left" style="color:#f28c28"></i> Item Transfers</h2>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Staff Name</th>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Route</th>
                                <th>Notes</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transfer_requests as $row): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                                    <td><strong><?= htmlspecialchars($row['item_name']) ?></strong></td>
                                    <td><?= $row['qty'] ?></td>
                                    <td><small><?= $row['source_location'] ?> <i class="fa-solid fa-arrow-right"></i> <?= $row['destination'] ?></small></td>
                                    <td style="max-width: 200px;"><?= htmlspecialchars($row['notes']) ?></td>
                                    <td><span class="status-pill status-<?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                                    <td>
                                        <?php if ($row['status'] === 'Pending'): ?>
                                            <a href="?type=transfer&action=approve&id=<?= $row['id'] ?>" class="btn-action btn-approve">Approve</a>
                                            <a href="?type=transfer&action=decline&id=<?= $row['id'] ?>" class="btn-action btn-decline">Decline</a>
                                        <?php else: ?>
                                            <small style="color:#999">Handled</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            </div>

            <div id="reports-tab" class="content-section">
                <section class="request-card">
                    <h2 style="margin-bottom:15px; color:#2c3e50;"><i class="fa-solid fa-file-lines" style="color:#f28c28"></i> Product Updates</h2>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Staff Name</th>
                                <th>Product</th>
                                <th>Stock</th>
                                <th>Report Title</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($basic_reports as $rep): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($rep['name']) ?></strong></td>
                                    <td><strong><?= htmlspecialchars($rep['product_name']) ?></strong></td>
                                    <td style="color:#f28c28; font-weight:bold;"><?= $rep['quantity_on_hand'] ?></td>
                                    <td style="font-weight:600;"><?= htmlspecialchars($rep['report_title']) ?></td>
                                    <td style="font-size:0.8rem; color:#555; max-width: 250px;"><?= htmlspecialchars($rep['description']) ?></td>
                                    <td><span class="badge-cat"><?= htmlspecialchars($rep['category']) ?></span></td>
                                    <td><span class="status-pill status-<?= strtolower($rep['status'] ?? 'pending') ?>"><?= $rep['status'] ?? 'Pending' ?></span></td>
                                    <td>
                                        <?php if (($rep['status'] ?? 'Pending') === 'Pending'): ?>
                                            <a href="?type=report&action=approve&id=<?= $rep['id'] ?>" class="btn-action btn-approve">Resolve</a>
                                            <a href="?type=report&action=decline&id=<?= $rep['id'] ?>" class="btn-action btn-decline">Ignore</a>
                                        <?php else: ?>
                                            <small style="color:#999">Closed</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            </div>
        </main>
    </div>

    <script>
        function switchTab(event, tabId) {
            document.querySelectorAll('.content-section').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        const sidebar = document.querySelector('.sidebar');
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    </script>
</body>
</html>