<?php
require_once "../auth/conn.php";


if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = trim($_POST['status']); 

    $stmt = $pdo->prepare("SELECT status, quantity, product_id FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $current = $stmt->fetch();

    if ($current) {
        try {
            $pdo->beginTransaction();


            if ($current['status'] !== 'Approved' && $new_status === 'Approved') {
                $up = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                $up->execute([$current['quantity'], $current['product_id']]);
            }

            if ($current['status'] === 'Approved' && $new_status !== 'Approved') {
                $up = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
                $up->execute([$current['quantity'], $current['product_id']]);
            }

            $update = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $update->execute([$new_status, $order_id]);

            $pdo->commit();
            header("Location: view_orders.php?success=1");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Error: " . $e->getMessage());
        }
    }
}

$all_orders = $pdo->query("SELECT o.*, p.product_name, p.price, p.quantity as current_stock 
                            FROM orders o 
                            JOIN products p ON o.product_id = p.id 
                            ORDER BY o.created_at ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Track & Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        :root { --primary-color: #f28c28; --success-color: #27ae60; }
        .admin-section { padding: 30px; }
        .table-container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-top: 20px; }
        .orders-table { width: 100%; border-collapse: collapse; }
        .orders-table th { background-color: #fdfaf7; color: #555; font-size: 0.85rem; padding: 18px; border-bottom: 2px solid #eee; text-align: center; }
        .orders-table td { padding: 15px 18px; border-bottom: 1px solid #eee; font-size: 0.9rem; color: #444; text-align: center; vertical-align: middle; }
        
        /* Status Badge Styling */
        .badge { padding: 8px 16px; border-radius: 6px; font-size: 0.85rem; font-weight: bold; display: inline-block; min-width: 100px; text-align: center; }
        .status-Pending { background: #fff3cd; color: #856404; }
        .status-Approved { background: #d4edda; color: #155724; }
        .status-Delivered { background: #e2e3e5; color: #383d41; }
        
        .update-btn { background: var(--primary-color); color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; transition: 0.3s; }
        .update-btn:hover { background: #d67616; }
        .status-select { padding: 8px; border-radius: 6px; border: 1px solid #ddd; outline: none; }
        .total-price { color: var(--success-color); font-weight: bold; }
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
                <a href="track&reports.php" class="nav-item"><i class="fa-solid fa-route"></i></i> <span>Track & Reports</span></a>
                <a href="view_orders.php" class="nav-item active"><i class="fa-solid fa-file-invoice-dollar"></i> <span>view orders</span></a>
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
                    <h1 style="margin: 0; font-size: 1.5rem;">Track & Reports</h1>
                </div>
                <div class="user-profile"><i class="fa-solid fa-circle-user"></i></div>
            </header>

            <section class="admin-section">
                <?php if(isset($_GET['success'])): ?>
                    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 500;">
                        <i class="fa-solid fa-circle-check"></i> Order updated and stock adjusted successfully!
                    </div>
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
                                $display_status = !empty($row['status']) ? $row['status'] : 'Pending';
                            ?>
                            <tr>
                                <td>#<?= $row['order_id'] ?></td>
                                <td><strong><?= htmlspecialchars($row['customer_name'] ?? 'N/A') ?></strong></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= $row['quantity'] ?></td>
                                <td class="total-price">₱<?= number_format($total_price, 2) ?></td>
                                <td><?= $row['current_stock'] ?></td>
                                <td><span class="badge status-<?= $display_status ?>"><?= $display_status ?></span></td>
                                <td>
                                    <form method="POST" style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                        <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                                        <select name="status" class="status-select">
                                            <option value="Pending" <?= $display_status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Approved" <?= $display_status == 'Approved' ? 'selected' : '' ?>>Approved</option>
                                            <option value="Delivered" <?= $display_status == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                        </select>
                                        <button type="submit" name="update_status" class="update-btn"><i class="fa-solid fa-check"></i></button>
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
    </script>
</body>
</html>