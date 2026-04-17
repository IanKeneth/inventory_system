<?php
session_start();
require_once "../auth/conn.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../app/login.php");
    exit();
}

try {
    // Real-world query: Joining products to get prices and calculating totals
    $query = "SELECT o.order_id, p.product_name, o.customer_name, o.quantity, p.price, 
            (o.quantity * p.price) AS total_amount, o.created_at FROM orders o
            JOIN products p ON o.product_id = p.id WHERE o.status IN ('Approved', 'Delivered') ORDER BY o.created_at ASC";
        
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
    <title>Sales Management | Reports</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    
    <script src="https://cdn.jsdelivr.net/gh/linways/table-to-excel@v1.0.4/dist/tableToExcel.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    
    <style>
        /* Table and Card Styling */
        .sales-card { background: white; border: 1px solid #e0e0e0; border-radius: 8px; margin: 20px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .sales-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .sales-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .sales-table th { background-color: #fdfdfd; color: #666; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; padding: 15px; text-align: left; border-bottom: 2px solid #f28c28; }
        .sales-table td { padding: 15px; border-bottom: 1px solid #eee; font-size: 0.9rem; color: #444; }
        .amount-text { font-weight: bold; color: #27ae60; }
        
        /* Dropdown Button Group */
        .btn-group { display: flex; gap: 10px; position: relative; }
        .action-btn { border: none; padding: 10px 18px; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 600; transition: 0.2s; }
        .report-btn { background: #2c3e50; color: white; }
        .report-btn:hover { background: #1a252f; }
        .refresh-btn { background: #f28c28; color: white; }

        /* Dropdown Menu */
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 48px;
            background-color: white;
            min-width: 220px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.15);
            z-index: 1000;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #ddd;
        }
        .dropdown-content button {
            color: #333;
            padding: 12px 16px;
            display: block;
            width: 100%;
            border: none;
            background: none;
            text-align: left;
            cursor: pointer;
            font-size: 0.85rem;
            transition: 0.2s;
        }
        .dropdown-content button:hover { background-color: #f8f9fa; color: #f28c28; }
        .dropdown-content i { width: 20px; font-size: 1rem; }
        .show { display: block; }

        /* Print Optimization */
        @media print {
            .sidebar, .btn-group, .hamburger-btn { display: none !important; }
            .main-content { margin: 0; padding: 0; width: 100%; }
            .sales-card { border: none; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Sales Admin</span></div>
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> <span>Dashboard</span></a>
                <a href="user_inventory.php" class="nav-item"><i class="fa-solid fa-right-left"></i> <span>User Inventory</span></a>
                <a href="transfer_request.php" class="nav-item"><i class="fa-solid fa-right-left"></i> <span>Transfer Request</span></a>
                <a href="basic_reports.php" class="nav-item"><i class="fa-solid fa-pen-to-square"></i> <span>Basic Reports</span></a>
                <a href="orders.php" class="nav-item"><i class="fa-solid fa-pen-to-square"></i> <span>Order</span></a>
                <a href="sales.php" class="nav-item active"><i class="fa-solid fa-chart-simple"></i> <span>Sales</span></a>
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-user-gear"></i> <span>Profile</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1>Sales Management</h1>
                </div>
            </header>

            <section class="sales-card">
                <div class="sales-header">
                    <h2><i class="fa-solid fa-coins" style="color: #f28c28;"></i> Recent Sales History</h2>
                    <div class="btn-group">
                        <button class="action-btn report-btn" id="reportDropdownBtn">
                            <i class="fa-solid fa-file-export"></i> Generate Report <i class="fa-solid fa-chevron-down" style="font-size: 0.7rem;"></i>
                        </button>
                        
                        <div id="reportDropdown" class="dropdown-content">
                            <button id="exportExcel"><i class="fa-solid fa-file-excel" style="color: #27ae60;"></i> Excel Spreadsheet (.xlsx)</button>
                            <button id="exportWord"><i class="fa-solid fa-file-word" style="color: #2b5797;"></i> MS Word Document (.doc)</button>
                            <button id="exportPDF"><i class="fa-solid fa-file-pdf" style="color: #e74c3c;"></i> PDF Document (.pdf)</button>
                            <button id="exportCSV"><i class="fa-solid fa-file-csv" style="color: #1d6f42;"></i> CSV Raw Data (.csv)</button>
                            <hr style="border: 0; border-top: 1px solid #eee; margin: 5px 0;">
                            <button onclick="window.print()"><i class="fa-solid fa-print"></i> Print View</button>
                        </div>

                        <button class="action-btn refresh-btn" onclick="window.location.reload()">
                            <i class="fa-solid fa-rotate"></i> Refresh
                        </button>
                    </div>
                </div>

                <table class="sales-table" id="salesTable">
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
                                    <td>₱<?= number_format($sale['price'], 2) ?></td>
                                    <td class="amount-text">₱<?= number_format($sale['total_amount'], 2) ?></td>
                                    <td><?= date('M d, Y', strtotime($sale['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center; padding:40px; color:#999;">No approved sales records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <script>
        const reportBtn = document.getElementById('reportDropdownBtn');
        const dropdown = document.getElementById('reportDropdown');

        reportBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });

        window.addEventListener('click', () => {
            if (dropdown.classList.contains('show')) dropdown.classList.remove('show');
        });

        document.getElementById('exportExcel').addEventListener('click', function() {
            let table = document.getElementById("salesTable");
            TableToExcel.convert(table, {
                name: `Sales_Report_<?= date('Y-m-d') ?>.xlsx`,
                sheet: { name: "Approved Sales" }
            });
        });
        document.getElementById('exportPDF').addEventListener('click', function() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.setFontSize(18);
            doc.text("Sales Management Report", 14, 20);
            doc.setFontSize(10);
            doc.text(`Report Generated: <?= date('F d, Y') ?>`, 14, 28);
            
            doc.autoTable({
                html: '#salesTable',
                startY: 35,
                theme: 'grid',
                headStyles: { fillColor: [242, 140, 40] }
            });
            doc.save(`Sales_Report_<?= date('Y-m-d') ?>.pdf`);
        });

        document.getElementById('exportWord').addEventListener('click', function() {
            const tableHTML = document.getElementById("salesTable").outerHTML;
            const content = `
                <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
                <head><meta charset='utf-8'><style>table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid black; padding: 8px; text-align: left; }</style></head>
                <body><h2>Sales Report - <?= date('Y-m-d') ?></h2>${tableHTML}</body></html>`;
            
            const uri = 'data:application/vnd.ms-word;charset=utf-8,' + encodeURIComponent(content);
            const link = document.createElement("a");
            link.href = uri;
            link.download = 'Sales_Report.doc';
            link.click();
        });

        document.getElementById('exportCSV').addEventListener('click', function() {
            let csv = [];
            let rows = document.querySelectorAll("#salesTable tr");
            rows.forEach(row => {
                let cols = row.querySelectorAll("td, th");
                let rowData = Array.from(cols).map(c => `"${c.innerText.replace(/₱|,/g, '')}"`).join(",");
                csv.push(rowData);
            });
            let csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
            let encodedUri = encodeURI(csvContent);
            let link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "sales_data.csv");
            link.click();
        });

        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        toggleBtn.addEventListener('click', () => { sidebar.classList.toggle('collapsed'); });
    </script>
</body>
</html>