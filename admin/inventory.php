<?php
session_start();
require_once '../auth/conn.php'; 

// We keep the security check! Only admins should see the page shell.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Note: We removed the $all_products loop from here because the API handles it now.
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
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
        /* Update the parent to allow children to spread out */
.header-left {
    display: flex;
    align-items: center;
    width: 96%; /* Ensure it takes full width of the header */
}

/* Updated Search Container Styles */
.search-container {
    position: relative;
    max-width: 300px;
    width: 100%;
    margin-left: auto; /* This pushes the bar to the far right automatically */
}

.search-container i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%); /* Perfectly centers the icon vertically */
    color: #7f8c8d;
}

.search-container input {
    width: 100%;
    padding: 10px 10px 10px 40px;
    border: 1px solid #ddd;
    border-radius: 25px;
    outline: none;
    transition: 0.3s;
}
        .inventory-container { padding: 25px; min-height: 100vh; background: #f9f9f9; }
        .inventory-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .inventory-card h2 { display: flex; align-items: center; gap: 12px; color: #2c3e50; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 2px solid #f28c28; font-size: 1.4rem; }
        .inventory-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .inventory-table th { background-color: #f8f9fa; color: #7f8c8d; text-transform: uppercase; font-size: 0.75rem; font-weight: 700; padding: 15px; text-align: center; border-bottom: 2px solid #eee; }
        .inventory-table td { padding: 15px; border-bottom: 1px solid #f1f1f1; text-align: center; color: #444; vertical-align: middle; }
        .progress-wrapper { display: flex; align-items: center; gap: 10px; min-width: 120px; justify-content: center; }
        .progress-bar-bg { flex-grow: 1; background: #eee; height: 8px; border-radius: 10px; overflow: hidden; max-width: 80px; }
        .progress-fill { height: 100%; transition: width 0.5s ease; }
        .category-tag { background:#eee; padding:4px 8px; border-radius:4px; font-size:0.75rem; font-weight: bold; color: #555; }
        .variation-text { color: #f28c28; font-weight: 600; font-size: 0.9rem; }
        .desc-text { color: #7f8c8d; font-size: 0.8rem; display: block; max-width: 200px; margin: 0 auto; line-height: 1.4; }
        
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(3px); justify-content: center; align-items: center; }
        .modal-content { background: #fff; width: 480px; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3); animation: slideDown 0.3s ease-out; }
        @keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { background: #f28c28; color: white; padding: 20px; text-align: center; position: relative; }
        .modal-body { padding: 25px; }
        .modal-body label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.85rem; color: #34495e; }
        .modal-body input, .modal-body select { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-family: inherit; }
        .btn-submit { width: 100%; padding: 12px; background: #f28c28; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: #d67616; }
        .close { position: absolute; right: 20px; top: 15px; color: white; font-size: 28px; cursor: pointer; }
        .refresh-btn { background: #f28c28; color: white; border: none; padding: 12px 25px; border-radius: 30px; cursor: pointer; font-weight: bold; float: right; margin-top: 20px; }
    </style>
</head>
<body>

    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Inventory System</span></div>
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a>
                <a href="inventory.php" class="nav-item active"><i class="fa-solid fa-boxes-packing"></i> <span>Inventory</span></a>
                <a href="supplies.php" class="nav-item"><i class="fa-solid fa-truck-ramp-box"></i> <span>Supplies</span></a>
                <a href="inventory_logs.php" class="nav-item "><i class="fa-solid fa-route"></i> <span>Inventory Logs</span></a>
                <a href="track_request.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> <span>Track Requests</span></a>
                <a href="view_orders.php" class="nav-item"><i class="fa-solid fa-file-invoice-dollar"></i> <span>View Orders</span></a>
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
        <button id="sidebarToggle" class="hamburger-btn">
            <i class="fa-solid fa-bars"></i>
        </button>
        <h1 style="white-space: nowrap; margin-right: 20px;">Stock Inventory</h1>
        
        <div class="search-container">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="inventorySearch" placeholder="Search by product name, category, or ID...">
        </div>
    </div>
</header>

            <section class="inventory-container">
                <div class="inventory-card">
                    <h2><i class="fa-solid fa-warehouse"></i> Current Stock Levels</h2>
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Category</th>
                                <th>Product</th>
                                <th>Variation</th> 
                                <th>Description</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Health</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="inventory-data">
                            <tr><td colspan="9">Loading products...</td></tr>
                        </tbody>
                    </table>
                    <button class="refresh-btn" onclick="openForm()"><i class="fa-solid fa-plus"></i> Add New Product</button>
                </div>

                <div id="popupForm" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <span class="close" onclick="closeForm()">&times;</span>
                            <h2 style="margin:0; color:white;">Register New Stock</h2>
                        </div>
                        <div class="modal-body">
                            <form action="../add_products/insert_into.php" method="POST">
                                <label>Category</label>
                                <select name="category" required>
                                    <option value="">-- Select Category --</option>
                                    <option value="Brooms">Brooms (Silhig)</option>
                                    <option value="Dustpan">Dustpan</option>
                                    <option value="Brushes">Brushes</option>
                                    <option value="Bucket">Bucket (Balde)</option>
                                    <option value="Tub">Tub (Labador)</option>
                                    <option value="Doormats">Doormats</option>
                                    <option value="Mops">Mops</option>
                                    <option value="Trash Can">Trash Can</option>
                                </select>
                                <label>Product Name</label>
                                <input type="text" name="product_name" placeholder="e.g. Soft Broom" required>
                                <label>Variation</label>
                                <input type="text" name="variation" placeholder="e.g. Red, Large">
                                <label>Description</label>
                                <input type="text" name="description" placeholder="Short details">
                                <div style="display: flex; gap: 10px;">
                                    <div style="flex:1;">
                                        <label>Price (₱)</label>
                                        <input type="number" name="price" step="0.01" required>
                                    </div>
                                    <div style="flex:1;">
                                        <label>Initial Qty</label>
                                        <input type="number" name="quantity" required>
                                    </div>
                                </div>
                                <label>Max Capacity</label>
                                <input type="number" name="max_quantity" value="100" required>
                                <button type="submit" class="btn-submit">Confirm & Save Product</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="../assets/api_js/inventory_api.js"></script>

    <script>
        // Run the fetch as soon as page loads
        window.onload = function() {
            loadInventory();

            // Check for success/error messages in URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('error') === 'duplicate') alert("❌ Duplicate Product!");
            if (urlParams.get('success') === '1') alert("✅ Product added successfully!");
            window.history.replaceState({}, document.title, window.location.pathname);
        };

        // Existing helper functions
        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this product?")) {
                window.location.href = "../add_products/delete_product.php?id=" + id;
            }
        }
        function openForm() { document.getElementById("popupForm").style.display = "flex"; }
        function closeForm() { document.getElementById("popupForm").style.display = "none"; }
        window.onclick = function(event) {
            if (event.target == document.getElementById("popupForm")) closeForm();
        }

         const sidebar = document.querySelector('.sidebar');
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    </script>
</body>
</html>