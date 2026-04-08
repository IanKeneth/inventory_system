<?php
session_start();
require_once '../auth/conn.php'; 

// Fetch all products
$all_products = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM products ORDER BY id ASC"); 
    $stmt->execute();
    $all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

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
        /* Existing Styles */
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
        .desc-text { color: #7f8c8d; font-size: 0.8rem; display: block; max-width: 200px; margin: 0 auto; line-height: 1.2; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(3px); justify-content: center; align-items: center; }
        .modal-content { background: #fff; width: 480px; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3); animation: slideDown 0.3s ease-out; }
        @keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { background: #f28c28; color: white; padding: 20px; text-align: center; position: relative; }
        .modal-body { padding: 25px; }
        .modal-body label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.85rem; color: #34495e; }
        .modal-body input, .modal-body select { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .btn-submit { width: 100%; padding: 12px; background: #f28c28; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .btn-submit:disabled { background: #ccc; cursor: not-allowed; }
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
                <a href="inventory.php" class="nav-item"><i class="fa-solid fa-boxes-packing"></i> <span>Inventory</span></a>
                <a href="supplies.php" class="nav-item"><i class="fa-solid fa-truck-ramp-box"></i> <span>Supplies</span></a>
                <a href="track&reports.php" class="nav-item active"><i class="fa-solid fa-route"></i> <span>Track & Reports</span></a>
                <a href="view_orders.php" class="nav-item "><i class="fa-solid fa-file-invoice-dollar"></i> <span>View Orders</span></a>
                <a href="User-management.php" class="nav-item"><i class="fa-solid fa-users"></i> <span>User Management</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-item"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1>Stock Inventory</h1>
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
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_products)): ?>
                                <?php foreach ($all_products as $product): 
                                    $max = (int)($product['max_quantity'] ?? 100);
                                    $current = (int)($product['quantity'] ?? 0);
                                    $percent = ($max > 0) ? ($current / $max) * 100 : 0;
                                    $health_color = ($percent <= 15) ? '#e74c3c' : '#2ecc71';
                                ?>
                                <tr id="row-<?= $product['id'] ?>">
                                    <td>#<?= e($product['id']) ?></td>
                                    <td><span class="category-tag"><?= e($product['category']) ?></span></td>
                                    <td><strong><?= e($product['product_name']) ?></strong></td>
                                    <td><span class="variation-text"><?= e($product['variation'] ?? 'Standard') ?></span></td>
                                    <td><span class="desc-text"><?= e($product['description']) ?></span></td>
                                    <td>₱<?= number_format($product['price'], 2) ?></td>
                                    <td><?= $current ?> / <?= $max ?></td>
                                    <td>
                                        <div class="progress-wrapper">
                                            <div class="progress-bar-bg"><div class="progress-fill" style="width:<?= $percent ?>%; background:<?= $health_color ?>;"></div></div>
                                            <small><?= round($percent) ?>%</small>
                                        </div>
                                    </td>
                                    <td><?= e(date("M j, Y h:i A", strtotime($product['created_at']))) ?></td>
                                    <td><a href="../add_products/edit_product.php?id=<?= $product['id'] ?>" style="color:#f28c28;"><i class="fa-solid fa-pen-to-square"></i></a></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <button class="refresh-btn" onclick="openForm()"><i class="fa-solid fa-plus"></i> Add New Product</button>
                </div>

                <div id="popupForm" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <span class="close" onclick="closeForm()">&times;</span>
                            <h2 style="margin:0; color:white;">Register Stock</h2>
                        </div>
                        <div class="modal-body">
                            <form action="../add_products/insert_into.php" method="POST">
                                <label>Category</label>
                                <select name="category" id="catSelect" onchange="updateProducts()" required>
                                    <option value="">-- Select Category --</option>
                                    <option value="Brooms">Brooms (Silhig)</option>
                                    <option value="Dustpan">Dustpan</option>
                                    <option value="Brushes">Brushes</option>
                                    <option value="Bucket">Bucket (Balde)</option>
                                    <option value="Tub">Tub (Labador)</option>
                                    <option value="Scrub">Floor Scrub</option>
                                    <option value="Doormats">Doormats</option>
                                    <option value="Mops">Mops</option>
                                    <option value="Trash Can">Trash Can</option>
                                    <option value="Pails">Pails</option>
                                </select>

                                <label>Product Name</label>
                                <select name="product_name" id="prodSelect" onchange="updateDescriptions()" required disabled>
                                    <option value="">-- Select Category First --</option>
                                </select>

                                <label>Variation (Color/Size/Type)</label>
                                <select name="variation" id="varSelect" onchange="checkSubmit()" required disabled>
                                    <option value="">-- Select Product First --</option>
                                </select>

                                <label>Description</label>
                                <input type="text" name="description" id="descInput" readonly style="background:#f4f4f4; border:1px solid #ccc; color:#666;">

                                <div style="display: flex; gap: 10px;">
                                    <div style="flex:1;">
                                        <label>Price (₱)</label>
                                        <input type="number" name="price" step="0.01" required>
                                    </div>
                                    <div style="flex:1;">
                                        <label>Qty</label>
                                        <input type="number" name="quantity" required>
                                    </div>
                                </div>
                                <input type="hidden" name="max_quantity" value="100">
                                <button type="submit" id="submitBtn" class="btn-submit" disabled>Confirm & Save</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Data for dropdowns
        const inventoryData = {
            "Brooms": { "Soft Broom": ["Ordinary", "Premium"], "Stiff Broom": ["Standard"], "Street Broom": ["Standard"], "Lobby Broom": ["Standard"] },
            "Dustpan": { "Dustpan (Medium)": ["Red", "Green", "Blue", "Black"], "Dustpan (Large)": ["Red", "Green", "Blue", "Black"] },
            "Trash Can": { "Small Bin": ["Black", "Blue", "Red"], "Medium Bin": ["Black", "Blue", "Red"], "Large Bin": ["Black", "Blue", "Red"] },
            "Pails": { "Plastic Pail": ["Green", "Red", "Black", "Blue"] },
            "Brushes": { "Plastic Brush": ["Standard"], "Toilet Brush": ["Standard"] },
            "Bucket": { "Plastic Buckets": ["Red", "Blue", "Black"] },
            "Scrub": { "Coconut Scrub": ["Standard"] },
            "Mops": { "String Mops": ["Standard"], "Rag Mops": ["Standard"], "Microfiber Mops": ["Standard"] },
            "Doormats": { "Rubber Mat": ["Standard"], "Microfiber Mat": ["Standard"], "Cotton Mat": ["Standard"] },
            "Tub": { "Washing Tub": ["Black","Red"], "Water Container": ["Black","Red","Green"] }
        };

        const productDescriptions = {
            "Soft Broom": "Fine-threaded grass broom for indoor smooth floors.",
            "Stiff Broom": "Hard bristles for outdoor sweeping and rough surfaces.",
            "Street Broom": "Wide heavy-duty broom for public roads and pathways.",
            "Lobby Broom": "Professional upright broom for commercial lobbies.",
            "Dustpan (Medium)": "Standard household dustpan with high-walled sides.",
            "Dustpan (Large)": "Heavy-duty industrial dustpan for large debris.",
            "Small Bin": "Compact waste bin suitable for office or bathroom.",
            "Medium Bin": "Standard kitchen size waste container.",
            "Large Bin": "Industrial capacity bin for bulk waste management.",
            "Plastic Pail": "Break-resistant plastic with reinforced carrying handle.",
            "Plastic Brush": "Stiff nylon bristles for heavy floor scrubbing.",
            "Toilet Brush": "Sanitary curved head for deep toilet bowl cleaning.",
            "Plastic Buckets": "Multipurpose heavy-duty bucket with liter markings.",
            "Coconut Scrub": "Traditional natural coconut husk for deep scouring.",
            "String Mops": "Industrial cotton strings for maximum water absorption.",
            "Rag Mops": "Recycled cloth strips for tough floor stains.",
            "Microfiber Mops": "Electrostatic fibers that trap dust and bacteria.",
            "Rubber Mat": "Non-slip heavy rubber for wet area entrance.",
            "Microfiber Mat": "Ultra-absorbent soft mat for drying feet.",
            "Cotton Mat": "Breathable natural fiber mat for bedroom use.",
            "Washing Tub": "Reinforced wide basin for manual clothes washing.",
            "Water Container": "Food-grade safe plastic for drinking water storage."
        };

        // Pass PHP inventory to JS for duplicate checking
        const currentInventory = <?= json_encode($all_products); ?>;

        function updateProducts() {
            const cat = document.getElementById("catSelect").value;
            const prod = document.getElementById("prodSelect");
            const varS = document.getElementById("varSelect");
            prod.innerHTML = '<option value="">-- Select Product --</option>';
            varS.innerHTML = '<option value="">-- Select Product First --</option>';
            varS.disabled = true;
            document.getElementById("descInput").value = "";
            if (inventoryData[cat]) {
                prod.disabled = false;
                for (let p in inventoryData[cat]) { prod.add(new Option(p, p)); }
            } else { prod.disabled = true; }
            checkSubmit();
        }

        function updateDescriptions() {
            const cat = document.getElementById("catSelect").value;
            const prodName = document.getElementById("prodSelect").value;
            const varS = document.getElementById("varSelect");
            const descI = document.getElementById("descInput");
            varS.innerHTML = '<option value="">-- Select Variation --</option>';
            if (inventoryData[cat] && inventoryData[cat][prodName]) {
                varS.disabled = false;
                inventoryData[cat][prodName].forEach(v => { varS.add(new Option(v, v)); });
                descI.value = productDescriptions[prodName] || "Quality cleaning supplies.";
            } else {
                varS.disabled = true;
                descI.value = "";
            }
            checkSubmit();
        }

        function checkSubmit() {
            const catVal = document.getElementById("catSelect").value;
            const prodVal = document.getElementById("prodSelect").value;
            const varVal = document.getElementById("varSelect").value;
            const submitBtn = document.getElementById("submitBtn");

            submitBtn.disabled = (varVal === "");

            if (varVal !== "") {
                const duplicate = currentInventory.find(item => 
                    item.category === catVal && 
                    item.product_name === prodVal && 
                    (item.variation === varVal || (item.variation === null && varVal === "Standard"))
                );

                if (duplicate) {
                    alert(`❌ ALREADY EXISTS!\n\n"${prodVal} - ${varVal}" is already in your inventory as ID #${duplicate.id}.\n\nPlease edit that row instead of adding a new one.`);
                    document.getElementById("varSelect").value = "";
                    submitBtn.disabled = true;
                }
            }
        }

        function openForm() { document.getElementById("popupForm").style.display = "flex"; }
        function closeForm() { document.getElementById("popupForm").style.display = "none"; }
    </script>


</body>
</html>