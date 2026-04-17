<?php
require_once "../auth/conn.php";


if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = trim($_POST['status']); 
    $decline_reason = isset($_POST['decline_reason']) ? trim($_POST['decline_reason']) : '';


    $stmt = $pdo->prepare("SELECT o.status, o.quantity, o.product_id, p.product_name, p.variation 
                FROM orders o JOIN products p ON o.product_id = p.id WHERE o.order_id = ?");
    $stmt->execute([$order_id]);
    $current = $stmt->fetch();

    if ($current) {
        try {
            $pdo->beginTransaction();
            if ($current['status'] !== 'Approved' && $new_status === 'Approved') {
                $up = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
                $up->execute([$current['quantity'], $current['product_id'], $current['quantity']]);
                
                if ($up->rowCount() == 0) {
                    throw new Exception("Insufficient stock to approve this order!");
                }

                $log_stmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, type, quantity, reason) 
                            VALUES (?, 'Out', ?, ?)");
                $log_stmt->execute([
                    $current['product_id'], 
                    $current['quantity'], 
                    "Order #" . $order_id . " Approved"
                ]);
            }


            if ($current['status'] === 'Approved' && $new_status !== 'Approved') {
                $up = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
                $up->execute([$current['quantity'], $current['product_id']]);

                $log_stmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, type, quantity, reason) 
                            VALUES (?, 'In', ?, ?)");
                $log_stmt->execute([
                    $current['product_id'], 
                    $current['quantity'], 
                    "Order #" . $order_id . "Declined"
                ]);
            }


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

$all_orders = $pdo->query("SELECT o.*, p.product_name, p.variation, p.price, p.quantity as current_stock 
                            FROM orders o 
                            JOIN products p ON o.product_id = p.id 
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
        
        /* Status Colors */
        .status-Pending { background: #fff3cd; color: #856404; }
        .status-Approved { background: #d4edda; color: #155724; }
        .status-Delivered { background: #e2e3e5; color: #383d41; }
        .status-Declined { background: #f8d7da; color: #721c24; }
        
        .reason-text { display: block; font-size: 0.75rem; color: #d9534f; margin-top: 4px; font-style: italic; }
        .decline-box { display: none; margin-top: 5px; }
        .decline-input { padding: 5px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.8rem; width: 150px; }
        
        .update-btn { background: var(--primary-color); color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; }
        .status-select { padding: 8px; border-radius: 6px; border: 1px solid #ddd; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
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
                <a href="inventory_logs.php" class="nav-item "><i class="fa-solid fa-route"></i> <span>Inventory Logs</span></a>
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
                <div class="user-profile"><i class="fa-solid fa-circle-user"></i></div>
            </header>

            <section class="admin-section">
                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Order updated successfully!</div>
                <?php endif; ?>
                
                <?php if(isset($_GET['error'])): ?>
                    <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($_GET['error']) ?></div>
                <?php endif; ?>

                <div class="table-container">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Product Details</th>
                                <th>Qty</th>
                                <th>Total Price</th>
                                <th>Stock Left</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_orders as $row): 
                                $total_price = $row['quantity'] * $row['price'];
                                $display_status = $row['status'];
                            ?>
                            <tr>
                                <td>#<?= $row['order_id'] ?></td>
                                <td><strong><?= htmlspecialchars($row['customer_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= $row['quantity'] ?></td>
                                <td style="color: #27ae60; font-weight: bold;">₱<?= number_format($total_price, 2) ?></td>
                                <td><?= $row['current_stock'] ?></td>
                                <td>
                                    <span class="badge status-<?= $display_status ?>"><?= $display_status ?></span>
                                    <?php if($display_status === 'Declined' && !empty($row['decline_reason'])): ?>
                                        <span class="reason-text">Reason: <?= htmlspecialchars($row['decline_reason']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: flex; flex-direction: column; align-items: center; gap: 5px;">
                                        <div style="display: flex; gap: 8px;">
                                            <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                                            <select name="status" class="status-select" onchange="checkDecline(this)">
                                                <option value="Pending" <?= $display_status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="Approved" <?= $display_status == 'Approved' ? 'selected' : '' ?>>Approved</option>
                                                <option value="Declined" <?= $display_status == 'Declined' ? 'selected' : '' ?>>Declined</option>
                                            </select>
                                            <button type="submit" name="update_status" class="update-btn"><i class="fa-solid fa-check"></i></button>
                                        </div>
                                        <div class="decline-box">
                                            <input type="text" name="decline_reason" class="decline-input" placeholder="Why decline? (e.g. Out of stock)">
                                        </div>
                                    </form>
                                </td>
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

        function checkDecline(select) {
            const container = select.closest('form');
            const reasonBox = container.querySelector('.decline-box');
            if (select.value === 'Declined') {
                reasonBox.style.display = 'block';
                reasonBox.querySelector('input').required = true;
            } else {
                reasonBox.style.display = 'none';
                reasonBox.querySelector('input').required = false;
            }
        }
    </script>
</body>
</html>