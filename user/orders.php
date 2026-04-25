<?php
require_once "../auth/conn.php";
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../auth/login.php");
    exit();
}

// Get the current logged-in user's ID
$current_user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_order'])) {
    $customer_name = $_POST['customer_name'];
    $product_id = $_POST['product_id']; 
    $quantity = (int)$_POST['quantity'];
    $method = $_POST['fulfillment_method']; 
    $user_id = $current_user_id; // Explicitly use the logged-in user's ID

    $stmt_product = $pdo->prepare("SELECT product_name, price, variation FROM products WHERE id = ?");
    $stmt_product->execute([$product_id]);
    $product_info = $stmt_product->fetch();

    if ($product_info) {
        $name = $product_info['product_name'];
        $variation = $product_info['variation'];
        $unit_price = $product_info['price'];
        $total_price = $unit_price * $quantity;

        $sql = "INSERT INTO orders (
                    product_id, 
                    user_id, 
                    customer_name, 
                    product_name, 
                    variation, 
                    unit_price, 
                    quantity, 
                    total_price, 
                    fulfillment_method,
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $product_id, 
            $user_id, 
            $customer_name, 
            $name, 
            $variation, 
            $unit_price, 
            $quantity, 
            $total_price,
            $method
        ]);

        header("Location: orders.php?success=1");
        exit();
    }
}

// Fetch only products available
$all_products = $pdo->query("SELECT id, product_name, price FROM products")->fetchAll();

// UPDATED: Fetch ONLY orders belonging to the logged-in staff member
$stmt_all_orders = $pdo->prepare("SELECT o.*, p.category 
                            FROM orders o 
                            LEFT JOIN products p ON o.product_id = p.id 
                            WHERE o.user_id = ? 
                            ORDER BY o.created_at DESC");
$stmt_all_orders->execute([$current_user_id]);
$all_orders = $stmt_all_orders->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/table-to-excel@1.0.4/dist/tableToExcel.min.js"></script>

    <style>
        .order-section { padding: 20px; }
        .order-title-bar { background-color: #f28c28; color: white; padding: 12px; border-radius: 25px; text-align: center; font-weight: bold; margin-bottom: 30px; max-width: 600px; margin: 0 auto 30px auto; }
        .orders-table { width: 100%; background: white; border-collapse: collapse; border: 1px solid #ddd; }
        .orders-table th { border: 1px solid #ddd; padding: 15px; background-color: #fffdfa; color: #333; font-size: 0.9rem; }
        .orders-table td { border: 1px solid #ddd; padding: 12px; text-align: center; font-size: 0.85rem; }
        .status-Pending { color: #e67e22; font-weight: bold; }
        .status-Approved { color: #27ae60; font-weight: bold; }
        .category-badge { background: #f0f0f0; color: #666; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; }
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
                <a href="index.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> <span>Dashboard</span></a>
                <a href="user_inventory.php" class="nav-item"><i class="fa-solid fa-right-left"></i> <span>User Inventory</span></a>
                <a href="transfer_request.php" class="nav-item"><i class="fa-solid fa-right-left"></i> <span>Transfer Request</span></a>
                <a href="basic_reports.php" class="nav-item"><i class="fa-solid fa-pen-to-square"></i> <span>Basic Reports</span></a>
                <a href="orders.php" class="nav-item active"><i class="fa-solid fa-pen-to-square"></i> <span>Order</span></a>
                <a href="sales.php" class="nav-item "><i class="fa-solid fa-chart-simple"></i> <span>Sales</span></a>
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
                    <h1>Track Orders</h1>
                </div>
            </header>

            <section class="order-section">
                <div class="order-title-bar">Track Orders & Deliveries</div>

                <table class="orders-table" id="ordersTable">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer Name</th>
                            <th>Category</th>
                            <th>Product Details</th>
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
                                <td>#<?= htmlspecialchars($row['order_id']) ?></td>
                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td><span class="category-badge"><?= htmlspecialchars($row['category'] ?? 'N/A') ?></span></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['product_name']) ?></strong><br>
                                    <small><?= htmlspecialchars($row['variation']) ?></small>
                                </td>
                                <td><?= (int)$row['quantity'] ?></td>
                                <td><strong>₱<?= number_format($row['total_price'], 2) ?></strong></td>
                                <td><?= htmlspecialchars($row['fulfillment_method'] ?? 'N/A') ?></td>
                                <td><span class="status-<?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td><?= $row['created_at'] ? date('M d, Y', strtotime($row['created_at'])) : 'N/A' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">No orders found for your account.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <button class="refresh-btn" onclick="openForm()">Add Customer Order</button>
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
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['product_name']) ?></option>
                    <?php endforeach; ?>
                </select>               
                
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
    </script>
</body>
</html>