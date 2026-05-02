<?php
require_once "../auth/conn.php";
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../auth/login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_order'])) {
    $customer_name = $_POST['customer_name'];
    $product_id = $_POST['product_id']; 
    $quantity = (int)$_POST['quantity'];
    $method = $_POST['fulfillment_method']; 
    $variation = $_POST['variation']; 
    $user_id = $current_user_id;

    $stmt_product = $pdo->prepare("SELECT product_name, price FROM products WHERE id = ?");
    $stmt_product->execute([$product_id]);
    $product_info = $stmt_product->fetch();

    if ($product_info) {
        $name = $product_info['product_name'];
        $unit_price = $product_info['price'];
        $total_price = $unit_price * $quantity;

        try {
            $sql_order = "INSERT INTO orders (
                        product_id, user_id, customer_name, product_name, 
                        variation, unit_price, quantity, total_price, fulfillment_method, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
            
            $stmt_order = $pdo->prepare($sql_order);
            $stmt_order->execute([
                $product_id, $user_id, $customer_name, $name, 
                $variation, $unit_price, $quantity, $total_price, $method
            ]);

            header("Location: orders.php?success=1");
            exit();

        } catch (Exception $e) {
            die("Error processing order: " . $e->getMessage());
        }
    }
}

$all_products = $pdo->query("SELECT id, product_name, price FROM products")->fetchAll();

$stmt_all_orders = $pdo->prepare("SELECT o.*, p.category 
                            FROM orders o 
                            LEFT JOIN products p ON o.product_id = p.id 
                            WHERE o.user_id = ? 
                            AND DATE(o.created_at) = CURDATE() 
                            ORDER BY o.created_at DESC");
$stmt_all_orders->execute([$current_user_id]);
$all_orders = $stmt_all_orders->fetchAll();

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
    <title>Track Orders - Daily View</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    
    <style>
        .order-section { padding: 20px; }
        .order-title-bar { background-color: #f28c28; color: white; padding: 12px; border-radius: 25px; text-align: center; font-weight: bold; margin-bottom: 30px; max-width: 600px; margin: 0 auto 30px auto; }
        .orders-table { width: 100%; background: white; border-collapse: collapse; border: 1px solid #ddd; }
        .orders-table th { border: 1px solid #ddd; padding: 15px; background-color: #fffdfa; color: #333; font-size: 0.9rem; }
        .orders-table td { border: 1px solid #ddd; padding: 12px; text-align: center; font-size: 0.85rem; }
        .status-Pending { color: #e67e22; font-weight: bold; }
        .status-Approved { color: #27ae60; font-weight: bold; }
        .category-badge { background: #f0f0f0; color: #666; padding: 4px 8px; border-radius: 4px; font-size: 15px; }
        .refresh-btn { background-color: #db740dec; color: white; border: none; padding: 10px 25px; border-radius: 20px; cursor: pointer; font-weight: bold; margin-left: 10px; float: right; margin-top: 20px; transition: 0.3s; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background: #fff; margin: 10% auto; padding: 20px; width: 400px; border-radius: 10px; }
        .modal-content input, .modal-content select { width: 100%; padding: 10px; margin: 5px 0 15px; box-sizing: border-box; border: 1px solid #ddd; border-radius: 5px; }
        .close { float: right; cursor: pointer; font-size: 20px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Orders</span></div>
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item "><i class="fa-solid fa-table-columns"></i> <span>Dashboard</span></a>
                <a href="user_inventory.php" class="nav-item"><i class="fa-solid fa-box"></i> <span>Inventory</span></a>
                <a href="user_invLog.php" class="nav-item"><i class="fa-solid fa-clock-rotate-left"></i> <span>Inventory_Log</span></a>
                <a href="transfer_request.php" class="nav-item"><i class="fa-solid fa-right-left"></i> <span>My Transfers</span></a>
                <a href="sales.php" class="nav-item "><i class="fa-solid fa-coins"></i> <span>Sales</span></a>
                <a href="orders.php" class="nav-item active"><i class="fa-solid fa-pen-to-square"></i> <span>Orders</span></a>
                <a href="basic_reports.php" class="nav-item"><i class="fa-solid fa-pen-to-square"></i> <span>My Reports</span></a>
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-user-gear"></i> <span>Profile</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-item"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1>Daily Order Tracking</h1>
                </div>
            </header>

            <button class="refresh-btn" onclick="openForm()">Add Customer Order</button>
            
            <section class="order-section">
                <div class="order-title-bar">Orders for Today (<?= date('F d, Y') ?>)</div>

                <table class="orders-table" id="ordersTable">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer Name</th>
                            <th>Category</th>
                            <th>Product Details</th>
                            <th>Variation</th>
                            <th>Qty</th>
                            <th>Total Price</th>
                            <th>Fulfillment Method</th>
                            <th>Status</th> 
                            <th>Order Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($all_orders) > 0): ?>
                            <?php foreach ($all_orders as $row): ?>
                            <tr>
                                <td>#<?= e($row['order_id']) ?></td>
                                <td><?= e($row['customer_name']) ?></td>
                                <td><span class="category-badge"><?= e($row['category'] ?? 'N/A') ?></span></td>
                                <td><strong><?= e($row['product_name']) ?></strong></td>
                                <td><?= e($row['variation']) ?: 'N/A' ?></td>
                                <td><?= (int)$row['quantity'] ?></td>
                                <td><strong>₱<?= number_format($row['total_price'], 2) ?></strong></td>
                                <td><?= e($row['fulfillment_method'] ?? 'N/A') ?></td>
                                <td><span class="status-<?= e($row['status']) ?>"><?= e($row['status']) ?></span></td>
                                <td><?= $row['created_at'] ? date('h:i A', strtotime($row['created_at'])) : 'N/A' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" style="padding: 40px; color: #666; text-align:center;">
                                    <i class="fa-solid fa-calendar-day" style="font-size: 2rem; display: block; margin-bottom: 10px; color: #ddd;"></i>
                                    No orders found for today.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <div id="popupForm" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeForm()">&times;</span>
            <h2 style="color: #f28c28; margin-bottom: 20px;">New Order Entry</h2>
            <form method="POST">
                <label>Customer Name:</label>
                <input type="text" name="customer_name" required>
                
                <label>Product:</label>
                <select name="product_id" required>
                    <option value="">-- Select Product --</option>
                    <?php foreach ($all_products as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= e($p['product_name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Variation:</label>
                <input type="text" name="variation" placeholder="e.g. Blue, Large, 500ml" required>
                
                <label>Quantity:</label>
                <input type="number" name="quantity" min="1" required>

                <label>Method:</label>
                <select name="fulfillment_method" required>
                    <option value="">-- Fulfillment Method --</option>
                    <option value="Delivery">Delivery</option>
                    <option value="Picked Up">Picked Up</option>
                </select>
                
                <button type="submit" name="save_order" style="background: #f28c28; color: white; border: none; padding: 12px; width: 100%; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 10px;">Save Order</button>
            </form>
        </div>
    </div>

    <script>
        function openForm() { document.getElementById("popupForm").style.display = "block"; }
        function closeForm() { document.getElementById("popupForm").style.display = "none"; }
        
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });

        window.onclick = function(event) {
            let modal = document.getElementById("popupForm");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>