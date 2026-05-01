<?php
require_once "../auth/conn.php";
session_start(); 

// Ensure the user is logged in and is an admin (or appropriate role)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = trim($_POST['status']); 
    $decline_reason = isset($_POST['decline_reason']) ? trim($_POST['decline_reason']) : '';
    $current_admin_id = $_SESSION['user_id']; // For reference, but we want the staff ID for the log

    // FETCH the original order details including the user_id (Staff who created it)
    $stmt = $pdo->prepare("SELECT status, quantity, product_id, user_id FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $current = $stmt->fetch();

    if ($current) {
        $current_status = $current['status'];
        $original_staff_id = $current['user_id']; // This is the ID of the staff member

        $allowed_transitions = [
            'Pending'   => ['Approved', 'Delivered', 'Declined'],
            'Approved'  => [], 
            'Delivered' => [], 
            'Declined'  => [],
        ];

        // Block same status
        if ($current_status === $new_status) {
            header("Location: view_orders.php?error=" . urlencode("Order #$order_id is already marked as '$current_status'. No changes made."));
            exit();
        }

        $allowed = $allowed_transitions[$current_status] ?? [];
        if (!in_array($new_status, $allowed)) {
            if (empty($allowed)) {
                $msg = "Order #$order_id is already '$current_status' and cannot be changed anymore.";
            } else {
                $msg = "Order #$order_id is currently '$current_status'. You can only change it to: " . implode(' or ', $allowed) . ".";
            }
            header("Location: view_orders.php?error=" . urlencode($msg));
            exit();
        }

        try {
            $pdo->beginTransaction();

            $old_status_have = strtolower(trim($current_status));
            $new_status_have = strtolower($new_status);

            // Logic: Record in inventory_logs and deduct stock ONLY when status moves to Approved or Delivered
            $should_record_activity = (
                $old_status_have === 'pending' &&
                in_array($new_status_have, ['approved', 'delivered'])
            );

            if ($should_record_activity) {
                // 1. Deduct from products table
                $up = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
                $up->execute([$current['quantity'], $current['product_id'], $current['quantity']]);

                if ($up->rowCount() == 0) {
                    throw new Exception("Insufficient stock in inventory to process this order!");
                }

                // 2. Insert into inventory_logs 
                // We pass $original_staff_id so the log shows the order's creator
                $reason_label = ucfirst($new_status_have);
                $log_stmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, user_id, quantity, type, reason) VALUES (?, ?, ?, 'Out', ?)");
                $log_stmt->execute([
                    $current['product_id'], 
                    $original_staff_id, 
                    $current['quantity'], 
                    "Order #$order_id $reason_label (Admin: ".$_SESSION['user_id'].")"
                ]);
            }

            // 3. Update the Order status itself
            $update = $pdo->prepare("UPDATE orders SET status = ?, decline_reason = ? WHERE order_id = ?");
            $update->execute([$new_status, $decline_reason, $order_id]);

            $pdo->commit();
            header("Location: view_orders.php?success=1");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: view_orders.php?error=" . urlencode($e->getMessage()));
            exit();
        }
    }
}

$transition_map = [
    'Pending'   => ['Pending', 'Approved', 'Delivered', 'Declined'],
    'Approved'  => ['Approved'],
    'Delivered' => ['Delivered'],
    'Declined'  => ['Declined'],
];

$all_orders = $pdo->query("SELECT o.*, u.name as staff_name, p.quantity as current_stock_level
                            FROM orders o 
                            LEFT JOIN users u ON o.user_id = u.id 
                            LEFT JOIN products p ON o.product_id = p.id 
                            ORDER BY o.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - View Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        :root { --primary-color: #f28c28; --success-color: #27ae60; --danger-color: #e74c3c; }
        .admin-section { padding: 30px; }
        .table-container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-top: 20px; }
        .orders-table { width: 100%; border-collapse: collapse; }
        .orders-table th { background-color: #fdfaf7; color: #555; font-size: 0.85rem; padding: 18px; border-bottom: 2px solid #eee; text-align: center; }
        .orders-table td { padding: 15px 18px; border-bottom: 1px solid #eee; font-size: 0.9rem; color: #444; text-align: center; vertical-align: middle; }
        .badge { padding: 8px 16px; border-radius: 6px; font-size: 0.85rem; font-weight: bold; display: inline-block; min-width: 100px; text-align: center; }

        .status-Pending   { background: #fff3cd; color: #856404; }
        .status-Approved  { background: #d4edda; color: #155724; }
        .status-Delivered { background: #e2e3e5; color: #383d41; }
        .status-Declined  { background: #f8d7da; color: #721c24; }
        
        .reason-text { display: block; font-size: 0.75rem; color: #d9534f; margin-top: 4px; font-style: italic; }
        .decline-box { display: none; margin-top: 5px; }
        .decline-input { padding: 5px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.8rem; width: 150px; }
        
        .update-btn { background: var(--primary-color); color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; transition: 0.3s; }
        .update-btn:hover { background: #d6761d; }

        .status-select { padding: 8px; border-radius: 6px; border: 1px solid #ddd; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #d4edda; color: #155724; border-left: 5px solid #28a745; }
        .alert-error   { background: #f8d7da; color: #721c24; border-left: 5px solid #dc3545; }

        .final-badge { font-size: 0.75rem; color: #999; font-style: italic; display: block; margin-top: 4px; }
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
                <a href="track_request.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> <span>Track Requests</span></a>
                <a href="view_orders.php" class="nav-item active"><i class="fa-solid fa-file-invoice-dollar"></i> <span>View Orders</span></a>
                <a href="User-management.php" class="nav-item"><i class="fa-solid fa-users"></i> <span>User Management</span></a>
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-gears"></i> <span>Settings</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-item"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1>Manage Orders</h1>
                </div>
            </header>

            <section class="admin-section">
                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Order status updated and staff activity logged!</div>
                <?php endif; ?>
                
                <?php if(isset($_GET['error'])): ?>
                    <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($_GET['error']) ?></div>
                <?php endif; ?>

                <div class="table-container">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Staff Member</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Status</th>
                                <th>Action</th>
                                <th>Order Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $all_status_options = ['Pending', 'Approved', 'Delivered', 'Declined'];
                            foreach ($all_orders as $row): 
                                $s = $row['status'];
                                $allowed_opts = $transition_map[$s] ?? [$s];
                                $is_final = in_array($s, ['Approved', 'Delivered', 'Declined']);
                            ?>
                            <tr>
                                <td>#<?= $row['order_id'] ?></td>
                                <td><strong><?= htmlspecialchars($row['staff_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['product_name']) ?></strong><br>
                                    <small>Variation: <?= htmlspecialchars($row['variation']) ?></small>
                                </td>
                                <td><?= $row['quantity'] ?></td>
                                <td>
                                    <span class="badge status-<?= $s ?>"><?= $s ?></span>
                                    <?php if($s === 'Declined' && !empty($row['decline_reason'])): ?>
                                        <span class="reason-text">Reason: <?= htmlspecialchars($row['decline_reason']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($is_final): ?>
                                        <span class="final-badge"><i class="fa-solid fa-lock"></i> Finalized</span>
                                    <?php else: ?>
                                        <form method="POST" onsubmit="return confirmUpdate(this);" style="display: flex; flex-direction: column; align-items: center; gap: 5px;">
                                            <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $s ?>">
                                            <div style="display: flex; gap: 8px;">
                                                <select name="status" class="status-select" onchange="checkDecline(this)">
                                                    <?php foreach ($all_status_options as $opt): 
                                                        $selected = ($s === $opt) ? 'selected' : '';
                                                        $disabled = !in_array($opt, $allowed_opts) ? 'disabled' : '';
                                                    ?>
                                                        <option value="<?= $opt ?>" <?= $selected ?> <?= $disabled ?>><?= $opt ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" name="update_status" class="update-btn">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </div>
                                            <div class="decline-box">
                                                <input type="text" name="decline_reason" class="decline-input" placeholder="Why decline?">
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td><?= $row['created_at'] ? date('M d, Y', strtotime($row['created_at'])) : 'N/A' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });

        function confirmUpdate(form) {
            const selectedStatus = form.status.value;
            const currentStatus = form.current_status.value;
            if (selectedStatus === currentStatus) {
                alert("Please select a new status.");
                return false;
            }
            return confirm(`Change status to ${selectedStatus}? This will record the movement under the original staff member's log.`);
        }

        function checkDecline(select) {
            const container = select.closest('form');
            const reasonBox = container.querySelector('.decline-box');
            reasonBox.style.display = (select.value === 'Declined') ? 'block' : 'none';
        }
    </script>
</body>
</html>