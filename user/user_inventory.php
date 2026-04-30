<?php
session_start();
require_once '../auth/conn.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
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
        position: relative;
        background: white;
        border-radius: 15px;
        display: flex;
        flex-direction: column;
        width: 100%;
        min-height: 480px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); }


    .card-category { 
        position: absolute; 
        top: 15px; 
        left: 15px;
        z-index: 10;
        font-size: 0.7rem; 
        color: #f28c28; 
        font-weight: bold; 
        text-transform: uppercase; 
        background: rgba(255, 243, 224, 0.95); 
        padding: 4px 10px; 
        border-radius: 4px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .card-image-wrapper {
        width: 100% !important;
        height: 220px;
        background: #fff;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 10px;
    }

    .card-image-wrapper img {
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
    }

    .card-info {
        padding: 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        width: 100%;
        box-sizing: border-box;
    }

    .card-title { font-size: 1.15rem; font-weight: 700; color: #2c3e50; margin: 0; }
    .card-variation { font-size: 0.85rem; color: #7f8c8d; margin-bottom: 10px; font-style: italic; }

    .card-description {
        display: block !important;
        visibility: visible !important;
        font-size: 0.85rem;
        color: #555 !important;
        line-height: 1.5;
        margin-bottom: 20px;
        flex-grow: 1; 
        word-wrap: break-word;
    }

    .card-footer { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding-top: 15px; 
        border-top: 1px solid #f1f1f1; 
    }
    
    .price-tag { font-size: 1.2rem; font-weight: 800; color: #2c3e50; }
    .qty-tag { font-size: 0.8rem; background: #e8f5e9; color: #2e7d32; padding: 5px 12px; border-radius: 20px; font-weight: 700; }
     .card-actions { position: absolute; top: 10px; right: 10px; display: flex; flex-direction: column; gap: 8px; z-index: 5; }
        .action-btn { width: 35px; height: 35px; background: white; border-radius: 50%; display: flex; justify-content: center; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; color: #555; transition: 0.2s; text-decoration: none; }
        .action-btn:hover { background: #f28c28; color: white; }
    </style>
</head>
<body>

    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Inventory System</span></div>
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item "><i class="fa-solid fa-table-columns"></i> <span>Dashboard</span></a>
                <a href="user_inventory.php" class="nav-item active"><i class="fa-solid fa-box"></i> <span>Inventory</span></a>
                <a href="user_invLog.php" class="nav-item"><i class="fa-solid fa-clock-rotate-left"></i> <span>Inventory_Log</span></a>
                <a href="transfer_request.php" class="nav-item"><i class="fa-solid fa-right-left"></i> <span>My Transfers</span></a>
                <a href="sales.php" class="nav-item "><i class="fa-solid fa-coins"></i> <span>Sales</span></a>
                <a href="orders.php" class="nav-item"><i class="fa-solid fa-pen-to-square"></i> <span>Orders</span></a>
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
                </div>

                <div id="inventory-grid" class="inventory-grid">
                    <p style="grid-column: 1/-1; text-align: center; padding: 50px; color: #7f8c8d;">Loading catalog...</p>
                </div>

            </section>
        </main>
    </div>

    <script src="../assets/api_js/user_inventory.js"></script>
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