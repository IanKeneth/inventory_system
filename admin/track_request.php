<?php
session_start();
require_once "../auth/conn.php";

// 1. Handle Approval or Decline Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    $newStatus = ($action === 'approve') ? 'Approved' : 'Declined';

    try {
        $updateSql = "UPDATE transfer_requests SET status = ? WHERE id = ?";
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([$newStatus, $id]);
        
        // Redirect to refresh and remove GET parameters from URL
        header("Location: track_request.php?msg=Status Updated");
        exit();
    } catch (PDOException $e) {
        die("Error updating status: " . $e->getMessage());
    }
}

// 2. Fetch all requests (Pending first, then by date)
try {
    $query = "SELECT * FROM transfer_requests ORDER BY FIELD(status, 'Pending', 'Approved', 'Declined'), created_at DESC";
    $stmt = $pdo->query($query);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching requests: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Track Requests</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .admin-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
        .admin-table th { background: #2c3e50; color: white; padding: 15px; text-align: left; font-size: 0.8rem; text-transform: uppercase; }
        .admin-table td { padding: 15px; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        
        /* Action Buttons */
        .btn-action { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.75rem; transition: 0.3s; margin-right: 5px; }
        .btn-approve { background: #2ecc71; color: white; border: 1px solid #27ae60; }
        .btn-approve:hover { background: #27ae60; }
        .btn-decline { background: #e74c3c; color: white; border: 1px solid #c0392b; }
        .btn-decline:hover { background: #c0392b; }
        
        /* Status Badges */
        .status-pill { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; text-transform: uppercase; }
        .status-pending { background: #f1c40f; color: #fff; }
        .status-approved { background: #2ecc71; color: #fff; }
        .status-declined { background: #e74c3c; color: #fff; }

        .request-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin: 20px; }
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
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1>Manage Transfer Requests</h1>
                </div>
            </header>

            <section class="request-card">
                <h2 style="margin-bottom: 20px; color: #2c3e50;"><i class="fa-solid fa-envelopes-bulk" style="color: #f28c28;"></i> Incoming Requests</h2>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Qty</th>
                            <th>From -> To</th>
                            <th>Date Requested</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($requests) > 0): ?>
                            <?php foreach ($requests as $row): ?>
                                <tr>
                                    <td>#<?= $row['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($row['item_name']) ?></strong></td>
                                    <td><?= $row['qty'] ?></td>
                                    <td>
                                        <small><?= htmlspecialchars($row['source_location']) ?></small> 
                                        <i class="fa-solid fa-arrow-right" style="font-size: 0.7rem; color: #aaa;"></i> 
                                        <small><?= htmlspecialchars($row['destination']) ?></small>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['request_date'])) ?></td>
                                    <td>
                                        <span class="status-pill status-<?= strtolower($row['status']) ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] === 'Pending'): ?>
                                            <a href="?action=approve&id=<?= $row['id'] ?>" class="btn-action btn-approve" onclick="return confirm('Approve this transfer?')">Approve</a>
                                            <a href="?action=decline&id=<?= $row['id'] ?>" class="btn-action btn-decline" onclick="return confirm('Decline this transfer?')">Decline</a>
                                        <?php else: ?>
                                            <span style="color: #999; font-size: 0.8rem;">Processed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center; padding:30px;">No transfer requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <script>
        // Sidebar Toggle
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    </script>
</body>
</html>