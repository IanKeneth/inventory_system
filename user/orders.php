<?php
require_once "../auth/conn.php";

// --- DATABASE LOGIC (Keep your existing logic) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_order'])) {
    $customer_name = $_POST['customer_name'];
    $product_id = $_POST['product_id']; 
    $quantity = (int)$_POST['quantity'];
    $status = 'Pending'; 
    $delivery = $_POST['estimated_delivery'];

    $stmt_product = $pdo->prepare("SELECT product_name, price, variation FROM products WHERE id = ?");
    $stmt_product->execute([$product_id]);
    $product_info = $stmt_product->fetch();

    if ($product_info) {
        $product_name = $product_info['product_name'];
        $variation = $product_info['variation'];
        $unit_price = $product_info['price'];
        $total_price = $unit_price * $quantity;

        $sql = "INSERT INTO orders (product_id, product_name, variation, customer_name, quantity, total_price, status, estimated_delivery) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id, $product_name, $variation, $customer_name, $quantity, $total_price, $status, $delivery]);

        header("Location: orders.php?success=1");
        exit();
    }
}

$all_products = $pdo->query("SELECT id, product_name, price FROM products")->fetchAll();
$all_orders = $pdo->query("SELECT o.*, p.category, p.price as unit_price 
                            FROM orders o 
                            JOIN products p ON o.product_id = p.id 
                            ORDER BY o.created_at DESC")->fetchAll();
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
        /* --- KEEPING YOUR ORIGINAL DESIGN --- */
        .order-section { padding: 20px; }
        .order-title-bar { background-color: #f28c28; color: white; padding: 12px; border-radius: 25px; text-align: center; font-weight: bold; margin-bottom: 30px; max-width: 600px; margin: 0 auto 30px auto; }
        .orders-table { width: 100%; background: white; border-collapse: collapse; border: 1px solid #ddd; }
        .orders-table th { border: 1px solid #ddd; padding: 15px; background-color: #fffdfa; color: #333; font-size: 0.9rem; }
        .orders-table td { border: 1px solid #ddd; padding: 12px; text-align: center; font-size: 0.85rem; }
        
        .status-Approved { color: #27ae60; font-weight: bold; }
        .status-Pending { color: #e67e22; font-weight: bold; }
        .status-Delivered { color: #2c3e50; font-weight: bold; }
        .status-Declined { color: #e74c3c; font-weight: bold; }
        
        .category-badge { background: #f0f0f0; color: #666; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; }
        .total-price { font-weight: bold; color: #2c3e50; }
        
        .refresh-btn { background-color: #db740dec; color: white; border: none; padding: 10px 25px; border-radius: 20px; cursor: pointer; font-weight: bold; margin-left: 10px; float: right; margin-top: 20px; transition: 0.3s; }
        .refresh-btn:hover { background-color: #d9771a; }

        .dropdown { position: relative; float: right; }
        .dropdown-content {
            display: none;
            position: absolute;
            right: 10px;
            top: 65px;
            background-color: white;
            min-width: 220px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.15);
            z-index: 1000;
            border-radius: 10px;
            border: 1px solid #eee;
            overflow: hidden;
        }
        .dropdown-content button {
            width: 100%;
            padding: 12px 15px;
            border: none;
            background: none;
            text-align: left;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dropdown-content button:hover { background-color: #fdf2e9; color: #f28c28; }
        .show { display: block !important; }


        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background: #fff; margin: 10% auto; padding: 20px; width: 400px; border-radius: 10px; }
        .modal-content input, .modal-content select { width: 100%; padding: 10px; margin: 5px 0 15px; box-sizing: border-box; border: 1px solid #ddd; border-radius: 5px; }
        .close { float: right; cursor: pointer; font-size: 20px; color: #999; }
        .alert-success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
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
                
                <div class="dropdown">
                    <button class="refresh-btn" id="reportDropdownBtn">
                        <i class="fa-solid fa-file-export"></i> Generate Report
                    </button>
                    <div id="reportDropdown" class="dropdown-content">
                        <button id="exportExcel"><i class="fa-solid fa-file-excel" style="color: #27ae60;"></i> Excel Spreadsheet</button>
                        <button id="exportWord"><i class="fa-solid fa-file-word" style="color: #2b5797;"></i> MS Word Document</button>
                        <button id="exportPDF"><i class="fa-solid fa-file-pdf" style="color: #e74c3c;"></i> PDF Document</button>
                        <button id="exportCSV"><i class="fa-solid fa-file-csv" style="color: #1d6f42;"></i> CSV Raw Data</button>
                        <hr style="border: 0; border-top: 1px solid #eee; margin: 0;">
                        <button onclick="window.print()"><i class="fa-solid fa-print"></i> Print View</button>
                    </div>
                </div>
            </header>

            <section class="order-section">
                <?php if(isset($_GET['success'])): ?>
                    <div class="alert-success">Order added successfully! Waiting for Admin approval.</div>
                <?php endif; ?>

                <div class="order-title-bar">Track Orders & Deliveries</div>

                <table class="orders-table" id="ordersTable">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer Name</th>
                            <th>Category</th>
                            <th>Product</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th>Total Price</th>
                            <th>Status</th> 
                            <th>Estimated Delivery</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_orders as $row): 
                            $status_text = !empty($row['status']) ? $row['status'] : 'Pending';
                        ?>
                        <tr>
                            <td>#<?= $row['order_id'] ?></td>
                            <td><?= htmlspecialchars($row['customer_name']) ?></td>
                            <td><span class="category-badge"><?= htmlspecialchars($row['category'] ?? 'N/A') ?></span></td>
                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                            <td>₱<?= number_format($row['unit_price'], 2) ?></td>
                            <td><?= $row['quantity'] ?></td>
                            <td class="total-price">₱<?= number_format($row['total_price'], 2) ?></td>
                            <td>
                                <span class="status-<?= $status_text ?>"><?= $status_text ?></span>
                            </td>
                            <td><?= date('M d, Y', strtotime($row['estimated_delivery'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <button class="refresh-btn" onclick="openForm()">Add Customer Order</button>
                <button class="refresh-btn" onclick="location.reload()">Refresh</button>

                <div id="popupForm" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeForm()">&times;</span>
                        <h2 style="color: #f28c28; margin-bottom: 20px;">New Order Entry</h2>
                        <form method="POST">
                            <label>Customer Name:</label>
                            <input type="text" name="customer_name" required placeholder="John Doe">
                            
                            <label>Product:</label>
                            <select name="product_id" required>
                                <option value="">-- Select Product --</option>
                                <?php foreach ($all_products as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['product_name']) ?> (₱<?= number_format($p['price'], 2) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            
                            <label>Quantity:</label>
                            <input type="number" name="quantity" min="1" required value="1">

                            <label>Estimated Delivery:</label>
                            <input type="date" name="estimated_delivery" required>
                            
                            <button type="submit" name="save_order" style="background: #f28c28; color: white; border: none; padding: 12px; width: 100%; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 10px;">Save Order</button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // 1. Dropdown Toggle Logic
        const reportBtn = document.getElementById('reportDropdownBtn');
        const dropdown = document.getElementById('reportDropdown');

        reportBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });

        window.addEventListener('click', () => {
            if (dropdown.classList.contains('show')) dropdown.classList.remove('show');
        });

        // 2. Export Excel
        document.getElementById('exportExcel').addEventListener('click', function() {
            let table = document.getElementById("ordersTable");
            TableToExcel.convert(table, {
                name: `Orders_Report_<?= date('Y-m-d') ?>.xlsx`,
                sheet: { name: "Orders" }
            });
        });

        // 3. Export PDF
        document.getElementById('exportPDF').addEventListener('click', function() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.text("Order Tracking Report", 14, 20);
            doc.autoTable({
                html: '#ordersTable',
                startY: 25,
                theme: 'grid',
                headStyles: { fillColor: [242, 140, 40] }
            });
            doc.save(`Orders_Report_<?= date('Y-m-d') ?>.pdf`);
        });

        // 4. Export Word
        document.getElementById('exportWord').addEventListener('click', function() {
            const tableHTML = document.getElementById("ordersTable").outerHTML;
            const content = `<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
                            <head><meta charset='utf-8'><style>table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid black; padding: 8px; text-align: left; }</style></head>
                            <body><h2>Orders Report</h2>${tableHTML}</body></html>`;
            const uri = 'data:application/vnd.ms-word;charset=utf-8,' + encodeURIComponent(content);
            const link = document.createElement("a");
            link.href = uri;
            link.download = 'Orders_Report.doc';
            link.click();
        });

        // 5. Export CSV
        document.getElementById('exportCSV').addEventListener('click', function() {
            let csv = [];
            let rows = document.querySelectorAll("#ordersTable tr");
            rows.forEach(row => {
                let cols = row.querySelectorAll("td, th");
                let rowData = Array.from(cols).map(c => `"${c.innerText.replace(/₱|,/g, '')}"`).join(",");
                csv.push(rowData);
            });
            let csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
            let link = document.createElement("a");
            link.setAttribute("href", encodeURI(csvContent));
            link.setAttribute("download", "orders_data.csv");
            link.click();
        });

        // Sidebar and Modal Functions
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });
        function openForm() { document.getElementById("popupForm").style.display = "block"; }
        function closeForm() { document.getElementById("popupForm").style.display = "none"; }
    </script>
</body>
</html>