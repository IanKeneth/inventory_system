<?php
session_start();
require_once '../auth/conn.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}
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
    <title>Inventory - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .header-left {display: flex;align-items: center;width: 96%;}
        .search-container {position: relative;max-width: 300px;width: 100%;margin-left: auto;}
        .search-container i {position: absolute;left: 15px;top: 50%;transform: translateY(-50%); color: #7f8c8d;}
        .search-container input {width: 100%;padding: 10px 10px 10px 40px;border: 1px solid #ddd;border-radius: 25px;outline: none;transition: 0.3s;}
        .inventory-container { padding: 25px; min-height: 100vh; background: #f9f9f9; }

        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
            border: 1px solid #eee;
        }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); }

        .card-image-wrapper {
            width: 100%;
            height: 200px;
            background: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f9f9f9;
        }
        .card-image-wrapper img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .card-info { padding: 20px; flex-grow: 1; }
        .card-category { font-size: 0.7rem; color: #f28c28; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; background: #fff3e0; display: inline-block; padding: 2px 8px; border-radius: 4px; }
        .card-title { font-size: 1.15rem; font-weight: 700; color: #2c3e50; margin: 8px 0; }
        .card-variation { font-size: 0.85rem; color: #7f8c8d; margin-bottom: 15px; font-style: italic; }
        .card-description {
            display: block !important; /* Forces it to show */
            visibility: visible !important;
            opacity: 1 !important;
            font-size: 0.8rem !important;
            color: #555 !important;
            line-height: 1.4 !important;
            margin: 10px 0 !important;
            height: auto !important; /* Prevents it from being squashed to 0px */
            overflow: visible !important;
        }
        .card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #f1f1f1; }
        .price-tag { font-size: 1.2rem; font-weight: 800; color: #2c3e50; }
        .qty-tag { font-size: 0.8rem; background: #e8f5e9; color: #2e7d32; padding: 5px 12px; border-radius: 20px; font-weight: 700; }

        .card-actions { position: absolute; top: 10px; right: 10px; display: flex; flex-direction: column; gap: 8px; z-index: 5; }
        .action-btn { width: 35px; height: 35px; background: white; border-radius: 50%; display: flex; justify-content: center; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; color: #555; transition: 0.2s; text-decoration: none; }
        .action-btn:hover { background: #f28c28; color: white; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(3px); justify-content: center; align-items: center; }
        .modal-content { background: #fff; width: 480px; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3); animation: slideDown 0.3s ease-out; }
        @keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { background: #f28c28; color: white; padding: 20px; text-align: center; position: relative; }
        .modal-body { padding: 25px; max-height: 80vh; overflow-y: auto; }
        .modal-body label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.85rem; color: #34495e; }
        .modal-body input, .modal-body select { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        
        .file-input-wrapper { border: 2px dashed #f28c28; padding: 15px; text-align: center; border-radius: 8px; margin-bottom: 15px; cursor: pointer; }
        #imagePreview { width: 100px; height: 100px; border-radius: 8px; object-fit: cover; margin-top: 10px; display: none; border: 2px solid #f28c28; }

        .btn-submit { width: 100%; padding: 12px; background: #f28c28; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .refresh-btn { background: #f28c28; color: white; border: none; padding: 12px 25px; border-radius: 30px; cursor: pointer; font-weight: bold; }
        .close { position: absolute; right: 20px; top: 15px; color: white; font-size: 28px; cursor: pointer; }
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
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1 style="white-space: nowrap; margin-right: 20px;">Stock Inventory</h1>
                    <div class="search-container">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="inventorySearch" placeholder="Search product...">
                    </div>
                </div>
            </header>

            <section class="inventory-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="color: #2c3e50;"><i class="fa-solid fa-layer-group"></i> Current Catalog</h2>
                    <button class="refresh-btn" onclick="openForm()"><i class="fa-solid fa-plus"></i> Add New Product</button>
                </div>

                <div id="inventory-grid" class="inventory-grid">
                    <p style="grid-column: 1/-1; text-align: center; padding: 50px; color: #7f8c8d;">Loading catalog...</p>
                </div>

                <div id="popupForm" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <span class="close" onclick="closeForm()">&times;</span>
                            <h2 style="margin:0; color:white;">Register New Stock</h2>
                        </div>
                        <div class="modal-body">
                            <form action="../add_products/insert_into.php" method="POST" enctype="multipart/form-data">
                                <label>Product Photo</label>
                                <div class="file-input-wrapper">
                                    <input type="file" name="product_image" id="imgInput" accept="image/*" required onchange="previewImage(this)">
                                    <p style="font-size: 0.7rem; color: #7f8c8d;">Click to upload (PNG, JPG)</p>
                                    <center><img id="imagePreview" src="">
                                </div>

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
                                <input type="text" name="product_name" placeholder="e.g. Walis Tambo Ordinary" required>

                                <label>Description</label>
                                <input type="text" name="description" placeholder="e.g. High-quality broom with durable handle" required>

                                <label>Variation</label>
                                <input type="text" name="variation" placeholder="e.g. Wooden Handle">

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
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; }
                reader.readAsDataURL(input.files[0]);
            }
        }

        window.onload = loadInventory;
        function openForm() { document.getElementById("popupForm").style.display = "flex"; }
        function closeForm() { 
            document.getElementById("popupForm").style.display = "none";
            document.getElementById("imagePreview").style.display = "none";
        }
        
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