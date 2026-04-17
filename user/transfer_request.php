<?php
session_start();
require_once "../auth/conn.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../app/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_transfer'])) {
    $item = $_POST['item_name'];
    $qty = $_POST['qty'];
    $source = $_POST['source_location'];
    $dest = $_POST['destination'];
    $date = $_POST['date_request'];
    $notes = $_POST['notes'];

    try {
        $sql = "INSERT INTO transfer_requests (item_name, qty, source_location, destination, request_date, notes, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$item, $qty, $source, $dest, $date, $notes]);
        echo "<script>alert('Transfer Request Submitted!'); window.location.href='transfer_request.php';</script>";
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

try {
    $stmt = $pdo->query("SELECT * FROM transfer_requests ORDER BY created_at DESC");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_req = count($requests);
    $approved_count = 0;
    $declined_count = 0;

    foreach ($requests as $r) {
        if ($r['status'] == 'Approved') $approved_count++;
        if ($r['status'] == 'Declined') $declined_count++;
    }
} catch (PDOException $e) {
    $requests = [];
    $total_req = $approved_count = $declined_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Status</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; }
        .status-container { padding: 20px; }
        .status-card { background: white; border: 1px solid #e0e0e0; border-radius: 8px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .status-card h2 { text-align: center; margin-bottom: 20px; color: #333; }

        .summary-badges { display: flex; justify-content: center; gap: 15px; margin-bottom: 30px; }
        .badge { padding: 10px 20px; border-radius: 20px; color: white; font-size: 0.85rem; font-weight: bold; }
        .bg-orange { background-color: #f28c28; }
        .bg-green { background-color: #2ecc71; }
        .bg-red { background-color: #e74c3c; }

        .status-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .status-table th { text-align: left; padding: 15px; background: #fdfdfd; border-bottom: 2px solid #f28c28; color: #666; font-size: 0.8rem; text-transform: uppercase; }
        .status-table td { padding: 15px; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        
        /* Status Colors */
        .status-text { font-weight: bold; }
        .approved { color: #2ecc71; }
        .pending { color: #f1c40f; }
        .declined { color: #e74c3c; }

        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background: #fff; padding: 30px; width: 500px; border-radius: 12px; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .modal-content h2 { margin-top: 0; font-size: 1.8rem; text-transform: lowercase; font-weight: 800; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full-width { grid-column: span 2; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #444; font-size: 0.9rem; }
        .form-control, .modal-content input, .modal-content select, .modal-content textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 0.95rem; }

        .close { position: absolute; right: 20px; top: 15px; cursor: pointer; font-size: 24px; color: #aaa; }
        .submit-btn { grid-column: span 2; background-color: #f28c28; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 10px; transition: 0.2s; }
        .submit-btn:hover { background-color: #d3761d; }
        
        .refresh-btn { background-color: #f28c28; color: white; border: none; padding: 12px 25px; border-radius: 6px; cursor: pointer; font-weight: bold; float: right; margin-left: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fa-solid fa-boxes-stacked"></i> <span>Transfer Request</span>
            </div>
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item "><i class="fa-solid fa-table-columns"></i> <span>Dashboard</span></a>
                <a href="user_inventory.php" class="nav-item"><i class="fa-solid fa-right-left"></i> <span>User Inventory</span></a>
                <a href="transfer_request.php" class="nav-item active"><i class="fa-solid fa-right-left"></i> <span>Transfer Request</span></a>
                <a href="basic_reports.php" class="nav-item "><i class="fa-solid fa-pen-to-square"></i> <span>Basic Reports</span></a>
                <a href="orders.php" class="nav-item "><i class="fa-solid fa-pen-to-square"></i> <span>Order</span></a>
                <a href="sales.php" class="nav-item"><i class="fa-solid fa-chart-simple"></i> <span>Sales</span></a>
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-user-gear"></i> <span>Profile</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1>Track My Transfer</h1>
                </div>
            </header>

            <section class="status-container">
                <div class="status-card">
                    <h2>Track My Transfer</h2>
                    <div class="summary-badges">
                        <div class="badge bg-orange">TOTAL REQUEST: <?= $total_req ?></div>
                        <div class="badge bg-green">ADMIN APPROVED: <?= $approved_count ?></div>
                        <div class="badge bg-red">ADMIN DECLINED: <?= $declined_count ?></div>
                    </div>

                    <table class="status-table">
                        <thead>
                            <tr>
                                <th>REQUEST ID</th>
                                <th>ITEM NAME</th>
                                <th>Qty</th>
                                <th>SOURCE LOCATION</th>
                                <th>DESTINATION</th>
                                <th>DATE REQUESTED</th>
                                <th>CURRENT STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($requests) > 0): ?>
                                <?php foreach ($requests as $row): ?>
                                    <tr>
                                        <td>REQ-<?= $row['id'] ?></td>
                                        <td><?= htmlspecialchars($row['item_name']) ?></td>
                                        <td><?= $row['qty'] ?></td>
                                        <td><?= htmlspecialchars($row['source_location']) ?></td>
                                        <td><?= htmlspecialchars($row['destination']) ?></td>
                                        <td><?= $row['request_date'] ?></td>
                                        <td class="status-text <?= strtolower($row['status']) ?>"><?= $row['status'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align:center; padding:20px;">No requests found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <button class="refresh-btn" onclick="window.location.reload()">Refresh List</button>
                    <button class="refresh-btn" onclick="openForm()">Transfer Request</button>

                    <div id="popupForm" class="modal">
                        <div class="modal-content">
                            <span class="close" onclick="closeForm()">&times;</span>
                            <h2 style="color: #e67e22;">transfer list</h2>

                            <form class="form-grid" method="POST" action="">
                                <div class="form-group">
                                    <label>Item Name</label>
                                    <input type="text" name="item_name" placeholder="Item Name" required>
                                </div>

                                <div class="form-group">
                                    <label>Qty</label>
                                    <input type="number" name="qty" placeholder="Enter QTY" required>
                                </div>

                                <div class="form-group">
                                    <label>Source Location:</label>
                                    <select name="source_location" class="form-control">
                                        <option value="Main Warehouse">Main Warehouse</option>
                                        <option value="Distribution Center">Distribution Center</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>TO (Destination):</label>
                                    <select name="destination" class="form-control">
                                        <option value="Distribution Center">Distribution Center</option>
                                        <option value="Main Warehouse">Main Warehouse</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Date Request:</label>
                                    <input type="date" name="date_request" class="form-control" required>
                                </div>
                                <div class="form-group full-width">
                                    <label>Transfer Notes (Optional):</label>
                                    <textarea name="notes" class="form-control" rows="3" placeholder="Additional details..."></textarea>
                                </div>
                                <button type="submit" name="submit_transfer" class="submit-btn">Submit Transfer Request</button>
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

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });

        function openForm() {
            document.getElementById("popupForm").style.display = "flex";
        }

        function closeForm() {
            document.getElementById("popupForm").style.display = "none";
        }
        window.onclick = function(event) {
            let modal = document.getElementById("popupForm");
            if (event.target == modal) {
                closeForm();
            }
        }
    </script>
</body>
</html>