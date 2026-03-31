<?php
require_once "../auth/conn.php";

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

try {
    $stmt = $pdo->query("SELECT id, product_name, category, price, quantity, max_quantity FROM products ORDER BY id ASC");
    $all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Selection Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        /* Container & Layout */
        .inventory-container { padding: 25px; min-height: 100vh; background: #f9f9f9; }
        
        .inventory-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: none;
        }

        .inventory-card h2 {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #2c3e50;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f28c28;
            font-size: 1.4rem;
        }

        /* Table Styling */
        .inventory-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .inventory-table th {
            background-color: #f8f9fa;
            color: #7f8c8d;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 15px;
            text-align: center;
            border-bottom: 2px solid #eee;
        }
        .inventory-table td { padding: 15px; border-bottom: 1px solid #f1f1f1; text-align: center; color: #444; }
        .inventory-table tr:hover { background-color: #fffaf5; }

        /* Progress Bar */
        .progress-wrapper { display: flex; align-items: center; gap: 10px; min-width: 150px; }
        .progress-bar-bg { flex-grow: 1; background: #eee; height: 10px; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; transition: width 0.5s ease; }

        /* Modal / Popup Styling */
        .modal { 
            display: none; position: fixed; z-index: 1000; 
            left: 0; top: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.6); backdrop-filter: blur(3px);
            justify-content: center; align-items: center; 
        }

        .modal-content {
            background: #fff; padding: 0; width: 450px; 
            border-radius: 15px; overflow: hidden; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: none; animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header { background: #f28c28; color: white; padding: 20px; text-align: center; position: relative; }
        .modal-body { padding: 25px; }
        
        .modal-body label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9rem; color: #34495e; }
        .modal-body input { 
            width: 100%; padding: 12px; margin-bottom: 15px; 
            border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; 
            transition: border 0.3s;
        }
        .modal-body input:focus { border-color: #f28c28; outline: none; }

        .btn-submit { 
            width: 100%; padding: 12px; background: #f28c28; color: white; 
            border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 1rem;
        }
        .btn-submit:hover { background: #d3741b; }

        .close { position: absolute; right: 15px; top: 15px; color: white; cursor: pointer; font-size: 20px; opacity: 0.8; }
        .close:hover { opacity: 1; }

        .refresh-btn {
            background: #f28c28; color: white; border: none; padding: 12px 25px;
            border-radius: 30px; cursor: pointer; font-weight: bold; float: right; margin-top: 20px;
            box-shadow: 0 4px 10px rgba(242, 140, 40, 0.3);
        }

        .action-btn { padding: 8px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; margin: 0 2px; }
        .btn-edit { color: #3498db; }
        .btn-delete { color: #e74c3c; }
    </style>
</head>
<body>

    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Title</span></div>
               <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a>
                <a href="inventory.php" class="nav-item active"><i class="fa-solid fa-boxes-packing"></i> <span>Inventory</span></a>
                <a href="supplies.php" class="nav-item"><i class="fa-solid fa-truck-ramp-box"></i> <span>Supplies</span></a>
                <a href="track&reports.php" class="nav-item"><i class="fa-solid fa-route"></i></i> <span>Track & Reports</span></a>
                <a href="view_orders.php" class="nav-item "><i class="fa-solid fa-file-invoice-dollar"></i> <span>view orders</span></a>
                <a href="User-management.php" class="nav-item"><i class="fa-solid fa-users"></i> <span>User Management</span></a>
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-gears"></i> <span>Settings</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-item"><i class="fa-solid fa-right-from-bracket icon"></i> <span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1>Inventory Management</h1>
                </div>
                <div class="user-profile"><i class="fa-solid fa-circle-user"></i></div>
            </header>

            <section class="inventory-container">
                <div class="inventory-card">
                    <h2><i class="fa-solid fa-warehouse"></i> Current Stock Levels</h2>
                    
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Stock Health</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_products)): ?>
                                <?php foreach ($all_products as $product): 
                                    $max = (int)($product['max_quantity'] ?? 0);
                                    $current = (int)($product['quantity'] ?? 0);
                                    $percent = ($max > 0) ? ($current / $max) * 100 : 0;
                                    $status_color = ($percent <= 15) ? '#e74c3c' : '#2ecc71';
                                ?>
                                <tr>
                                    <td><strong>#<?= e($product['id']) ?></strong></td>
                                    <td><?= e($product['product_name']) ?></td>
                                    <td><span style="background:#eee; padding:4px 8px; border-radius:4px; font-size:0.8rem;"><?= e($product['category']) ?></span></td>
                                    <td>₱<?= number_format($product['price'], 2) ?></td>
                                    <td><?= $current ?> / <?= $max ?></td>
                                    <td>
                                        <div class="progress-wrapper">
                                            <span style="font-size: 0.8rem; font-weight: bold; width: 35px;"><?= round($percent) ?>%</span>
                                            <div class="progress-bar-bg">
                                                <div class="progress-fill" style="width: <?= $percent ?>%; background: <?= $status_color ?>;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($percent <= 15): ?>
                                            <span style="color: #e74c3c; font-weight: bold; font-size: 0.75rem;"><i class="fa-solid fa-circle-exclamation"></i> LOW</span>
                                        <?php else: ?>
                                            <span style="color: #2ecc71; font-weight: bold; font-size: 0.75rem;"><i class="fa-solid fa-circle-check"></i> OK</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="../add_products/edit_product.php?id=<?= $product['id'] ?>" class="action-btn btn-edit" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        <a href="delete_product.php?id=<?= $product['id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Delete product?')"><i class="fa-solid fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8">No products found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <button class="refresh-btn" onclick="openForm()"><i class="fa-solid fa-plus"></i> Add New Product</button>
                </div>

                <div id="popupForm" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <span class="close" onclick="closeForm()">&times;</span>
                            <h2 style="margin:0; color:white;">Add New Product</h2>
                        </div>
                        <div class="modal-body">
                            <form action="../add_products/insert_into.php" method="POST">
                                <label>Product Name</label>
                                <input type="text" name="product_name" placeholder="Enter product name" required>
                                
                                <label>Category</label>
                                <input type="text" name="category" placeholder="e.g. Cleaning Supplies" required>

                                <div style="display: flex; gap: 10px;">
                                    <div style="flex:1;">
                                        <label>Price</label>
                                        <input type="number" name="price" step="0.01" required>
                                    </div>
                                    <div style="flex:1;">
                                        <label>Current Qty</label>
                                        <input type="number" name="quantity" required>
                                    </div>
                                </div>

                                <label>Max Stock Capacity</label>
                                <input type="number" name="max_quantity" required>

                                <button type="submit" class="btn-submit">Save Product</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        toggleBtn.addEventListener('click', () => { sidebar.classList.toggle('collapsed'); });

        function openForm() { document.getElementById("popupForm").style.display = "flex"; }
        function closeForm() { document.getElementById("popupForm").style.display = "none"; }
        
        // Close modal if user clicks outside of it
        window.onclick = function(event) {
            if (event.target == document.getElementById("popupForm")) closeForm();
        }
    </script>
</body>
</html>