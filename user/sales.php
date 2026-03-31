<?php
session_start();
require_once "../auth/conn.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../app/login.php");
    exit();
}

try {
    $query = "SELECT o.order_id, p.product_name, o.customer_name, o.quantity, p.price, (o.quantity * p.price) AS total_amount,o.created_at FROM orders o
                JOIN products p ON o.product_id = p.id WHERE o.status IN ('Approved', 'Delivered')ORDER BY o.created_at ASC";
        
    $stmt = $pdo->query($query);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching sales: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .sales-card { background: white; border: 1px solid #e0e0e0; border-radius: 8px; margin: 20px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .sales-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .sales-table { width: 100%; border-collapse: collapse; }
        .sales-table th { background-color: #fdfdfd; color: #666; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; padding: 15px; text-align: left; border-bottom: 2px solid #f28c28; }
        .sales-table td { padding: 15px; border-bottom: 1px solid #eee; font-size: 0.9rem; color: #444; }
        .amount-text { font-weight: bold; color: #27ae60; }
        .price-text { color: #666; font-style: italic; }
        .empty-row { text-align: center; padding: 40px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Sales</span></div>
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item ">
                    <i class="fa-solid fa-table-columns"></i> 
                    <span>Dashboard</span>
                </a>
                <a href="transfer_request.php" class="nav-item ">
                    <i class="fa-solid fa-right-left"></i> 
                    <span>Transfer Request</span>
                </a>
                <a href="basic_reports.php" class="nav-item ">
                    <i class="fa-solid fa-pen-to-square"></i> 
                    <span>Basic Reports</span>
                </a>
                 <a href="orders.php" class="nav-item ">
                    <i class="fa-solid fa-pen-to-square"></i> 
                    <span>Order</span>
                </a>
                <a href="sales.php" class="nav-item active">
                    <i class="fa-solid fa-chart-simple"></i> 
                    <span>Sales</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <i class="fa-solid fa-user-gear"></i> 
                    <span>Profile</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1>Sales Management</h1>
                </div>
                <div class="user-profile"><i class="fa-solid fa-circle-user"></i></div>
            </header>

            <section class="sales-card">
                <div class="sales-header">
                    <h2><i class="fa-solid fa-coins" style="color: #f28c28;"></i> Recent Sales History</h2>
                    <button class="refresh-btn" onclick="window.location.reload()" style="background: #f28c28; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">
                        <i class="fa-solid fa-rotate"></i> Refresh
                    </button>
                </div>

                <table class="sales-table">
                    <thead>
                        <tr>
                            <th>Sales ID</th>
                            <th>Product Name</th>
                            <th>Customer Name</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($sales) > 0): ?>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td>#<?= $sale['order_id'] ?></td>
                                    <td><strong><?= htmlspecialchars($sale['product_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($sale['customer_name']) ?></td>
                                    <td><?= $sale['quantity'] ?></td>
                                    <td class="price-text">₱<?= number_format($sale['price'], 2) ?></td>
                                    <td class="amount-text">₱<?= number_format($sale['total_amount'], 2) ?></td>
                                    <td><?= date('M d, Y | h:i A', strtotime($sale['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-row">No approved sales found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <script>
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    </script>
</body>
</html>