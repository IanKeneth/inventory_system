<?php
require_once "../auth/conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_supply'])) {
    $supplier = $_POST['supplier_name'];
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $date_added = $_POST['date_added'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO supplies (product_id, supplier_name, quantity_received, date_received) VALUES (?, ?, ?, ?)");
        $stmt->execute([$product_id, $supplier, $quantity, $date_added]);

        $updateStock = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
        $updateStock->execute([$quantity, $product_id]);

        $pdo->commit();
        header("Location: supplies.php?success=1");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

$all_products = $pdo->query("SELECT id, product_name FROM products")->fetchAll();
$all_supplies = $pdo->query("SELECT s.*, p.product_name 
                    FROM supplies s 
                    JOIN products p ON s.product_id = p.id 
                    ORDER BY s.date_received ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplies - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .order-section { padding: 20px; }
        .order-title-bar { background-color: #f28c28; color: white; padding: 12px; border-radius: 25px; text-align: center; font-weight: bold; margin-bottom: 30px; max-width: 600px; margin: 0 auto 30px; }
        .orders-table { width: 100%; background: white; border-collapse: collapse; border: 1px solid #ddd; }
        .orders-table th { border: 1px solid #ddd; padding: 15px; background-color: #fffdfa; color: #333; }
        .orders-table td { border: 1px solid #ddd; padding: 12px; text-align: center; }
        
        /* Modal Fixes */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background: #fff; margin: 5% auto; padding: 25px; width: 400px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .modal-content label { display: block; margin-top: 10px; font-weight: bold; font-size: 0.9rem; }
        .modal-content input, .modal-content select { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .add-btn { width: 100%; background: #f28c28; color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 15px; }
        .close { float: right; cursor: pointer; font-size: 24px; color: #999; }
        .refresh-btn { background-color: #f28c28; color: white; border: none; padding: 10px 25px; border-radius: 20px; cursor: pointer; font-weight: bold; float: right; margin: 10px 5px; }
    </style>
</head>
<body>

    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Title</span></div>
           <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a>
                <a href="inventory.php" class="nav-item"><i class="fa-solid fa-boxes-packing"></i> <span>Inventory</span></a>
                <a href="supplies.php" class="nav-item active"><i class="fa-solid fa-truck-ramp-box"></i> <span>Supplies</span></a>
                <a href="track&reports.php" class="nav-item"><i class="fa-solid fa-route"></i></i> <span>Track & Reports</span></a>
                <a href="view_orders.php" class="nav-item "><i class="fa-solid fa-file-invoice-dollar"></i> <span>view orders</span></a>
                <a href="User-management.php" class="nav-item"><i class="fa-solid fa-users"></i> <span>User Management</span></a>
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-gears"></i> <span>Settings</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1>Supplies Management</h1>
                </div>
            </header>

            <section class="order-section">
                <?php if(isset($_GET['success'])): ?>
                    <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                        Stock updated successfully!
                    </div>
                <?php endif; ?>

                <div class="order-title-bar">List Of Supplies Added</div>

                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Supply ID</th>
                            <th>Supplier Name</th>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Date Received</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($all_supplies as $supply): ?>
                        <tr>
                            <td>#<?= $supply['supply_id'] ?></td>
                            <td><?= htmlspecialchars($supply['supplier_name']) ?></td>
                            <td><?= htmlspecialchars($supply['product_name']) ?></td>
                            <td><strong>+<?= $supply['quantity_received'] ?></strong></td>
                            <td><?= $supply['date_received'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <button class="refresh-btn" onclick="location.reload()">Refresh</button>
                <button class="refresh-btn" onclick="openForm()">Add Supplies</button>

                <div id="popupForm" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeForm()">&times;</span>
                        <h2 style="color: #f28c28; margin-top: 0;">Add Stock</h2>

                        <form method="POST">
                            <label>Supplier Name:</label>
                            <input type="text" name="supplier_name" placeholder="Who sent this?" required>

                            <label>Product:</label>
                            <select name="product_id" required>
                                <option value="">-- Select Product --</option>
                                <?php foreach($all_products as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= $p['product_name'] ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label>Quantity to Add:</label>
                            <input type="number" name="quantity" min="1" required>

                            <label>Date Received:</label>
                            <input type="date" name="date_added" value="<?= date('Y-m-d') ?>" required>

                            <button type="submit" name="add_supply" class="add-btn">Add to Inventory</button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });

        function openForm() { document.getElementById("popupForm").style.display = "block"; }
        function closeForm() { document.getElementById("popupForm").style.display = "none"; }
        
        // Close modal if user clicks outside of it
        window.onclick = function(event) {
            if (event.target == document.getElementById("popupForm")) { closeForm(); }
        }
    </script>
</body>
</html>