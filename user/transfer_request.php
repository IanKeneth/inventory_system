<?php
session_start();
require_once "../auth/conn.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../app/login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_transfer'])) {
    $item = $_POST['item_name'];
    $qty = $_POST['qty'];
    $source = $_POST['source_location'];
    $dest = $_POST['destination'];
    $date = $_POST['date_request'];
    $notes = $_POST['notes'];

    try {
        $sql = "INSERT INTO transfer_requests (user_id, item_name, qty, source_location, destination, request_date, notes, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')";   
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_user_id, $item, $qty, $source, $dest, $date, $notes]);
        
        echo "<script>alert('Transfer Request Submitted!'); window.location.href='transfer_request.php';</script>";
    } catch (PDOException $e) {
        die("Error saving request: " . $e->getMessage());
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM transfer_requests WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$current_user_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_pending = 0;
    $approved_count = 0;
    $declined_count = 0;

    foreach ($requests as $r) {
        if ($r['status'] == 'Pending') {
            $total_pending++;
        } elseif ($r['status'] == 'Approved') {
            $approved_count++;
        } elseif ($r['status'] == 'Declined') {
            $declined_count++;
        }
    }
} catch (PDOException $e) {
    $requests = [];
    $total_pending = $approved_count = $declined_count = 0;
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
    <title>Track Status</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { 
            margin: 0; padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f4f7f6; 
        }

        .container { display: flex; min-height: 100vh; }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 240px;
            background-color: #ffffff;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            transition: width 0.4s ease;
            overflow: hidden;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .sidebar.collapsed { width: 75px; }

        .sidebar-header {
            height: 70px;
            padding: 0 25px;
            font-size: 1.2rem;
            font-weight: bold;
            color: #f28c28;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .sidebar-header i { font-size: 1.3rem; min-width: 25px; text-align: center; }

        nav { flex: 1; }

        .nav-item {
            padding: 15px 25px;
            display: flex;
            align-items: center;
            cursor: pointer;
            border-bottom: 1px solid #f9f9f9;
            font-weight: 500;
            color: #555;
            transition: 0.3s;
            text-decoration: none;
            gap: 15px;
        }

        .nav-item:hover, .nav-item.active {
            background-color: #fff5eb;
            color: #f28c28;
        }

        .nav-item.active { border-right: 4px solid #f28c28; }

        .sidebar span {
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        .sidebar.collapsed span { opacity: 0; pointer-events: none; }

        /* --- MAIN CONTENT --- */
        .main-content { flex: 1; min-width: 0; }

        .header {
            background-color: #f28c28;
            height: 70px;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .header-left { display: flex; align-items: center; gap: 20px; }
        .header h1 { margin: 0; font-size: 1.2rem; }

        .hamburger-btn {
            background: none; border: none; color: white;
            font-size: 1.2rem; cursor: pointer;
        }

        /* --- STATUS CARD & TABLE --- */
        .status-container { padding: 20px; }
        .status-card { 
            background: white; border: 1px solid #e0e0e0; 
            border-radius: 8px; padding: 25px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        }

        .summary-badges { display: flex; justify-content: center; gap: 15px; margin-bottom: 30px; }
        .badge { padding: 10px 20px; border-radius: 20px; color: white; font-size: 0.85rem; font-weight: bold; }
        .bg-orange { background-color: #f28c28; }
        .bg-green { background-color: #2ecc71; }
        .bg-red { background-color: #e74c3c; }

        .status-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .status-table th { 
            text-align: left; padding: 15px; background: #fdfaf7; 
            border-bottom: 2px solid #f28c28; color: #666; 
            font-size: 0.75rem; text-transform: uppercase; 
        }
        .status-table td { padding: 15px; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        
        .status-text { font-weight: bold; }
        .approved { color: #2ecc71; }
        .pending { color: #f1c40f; }
        .declined { color: #e74c3c; }

        /* --- MODAL --- */
        .modal { 
            display: none; position: fixed; z-index: 999; 
            left: 0; top: 0; width: 100%; height: 100%; 
            background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; 
        }
        .modal-content { background: #fff; padding: 30px; width: 500px; border-radius: 12px; position: relative; }
        .close { position: absolute; right: 20px; top: 15px; cursor: pointer; font-size: 24px; color: #aaa; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full-width { grid-column: span 2; }
        .form-control, input, select, textarea { 
            width: 100%; padding: 10px; border: 1px solid #ddd; 
            border-radius: 6px; box-sizing: border-box; 
        }

        .submit-btn { 
            grid-column: span 2; background-color: #f28c28; color: white; 
            border: none; padding: 12px; border-radius: 4px; 
            cursor: pointer; font-weight: bold; transition: 0.2s; 
        }
        .refresh-btn { 
            background-color: #f28c28; color: white; border: none; 
            padding: 10px 20px; border-radius: 6px; cursor: pointer; 
            font-weight: bold; float: right; margin-left: 10px; margin-top: 20px; 
        }
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
                <a href="user_inventory.php" class="nav-item"><i class="fa-solid fa-box"></i> <span>Inventory</span></a>
                <a href="user_invLog.php" class="nav-item"><i class="fa-solid fa-clock-rotate-left"></i> <span>Inventory_Log</span></a>
                <a href="transfer_request.php" class="nav-item active"><i class="fa-solid fa-right-left"></i> <span>My Transfers</span></a>
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
                    <h1>Track My Transfer</h1>
                </div>
            </header>

            <section class="status-container">
                <div class="status-card">
                    <h2 style="text-align: center; color: #333;">Transfer Status Overview</h2>
                    <div class="summary-badges">
                        <div class="badge bg-orange">ACTIVE REQUESTS: <?= $total_pending ?></div>
                        <div class="badge bg-green">APPROVED: <?= $approved_count ?></div>
                        <div class="badge bg-red">DECLINED: <?= $declined_count ?></div>
                    </div>

                    <table class="status-table">
                        <thead>
                            <tr>
                                <th>REQ ID</th>
                                <th>ITEM NAME</th>
                                <th>QTY</th>
                                <th>SOURCE</th>
                                <th>DESTINATION</th>
                                <th>DATE</th>
                                <th>STATUS</th>
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

                    <button class="refresh-btn" onclick="openForm()">New Transfer</button>
                    <button class="refresh-btn" onclick="window.location.reload()">Refresh</button>

                    <div id="popupForm" class="modal">
                        <div class="modal-content">
                            <span class="close" onclick="closeForm()">&times;</span>
                            <h2 style="color: #f28c28;">Transfer Item</h2>
                            <form class="form-grid" method="POST">
                                <div class="form-group">
                                    <label>Item Name</label>
                                    <input type="text" name="item_name" required>
                                </div>
                                <div class="form-group">
                                    <label>Quantity</label>
                                    <input type="number" name="qty" required>
                                </div>
                                <div class="form-group">
                                    <label>Source</label>
                                    <select name="source_location">
                                        <option value="Main Warehouse">Main Warehouse</option>
                                        <option value="Distribution Center">Distribution Center</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Destination</label>
                                    <select name="destination">
                                        <option value="Distribution Center">Distribution Center</option>
                                        <option value="Main Warehouse">Main Warehouse</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" name="date_request" required>
                                </div>
                                <div class="form-group full-width">
                                    <label>Notes</label>
                                    <textarea name="notes" rows="2"></textarea>
                                </div>
                                <button type="submit" name="submit_transfer" class="submit-btn">Submit Request</button>
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

        function openForm() { document.getElementById("popupForm").style.display = "flex"; }
        function closeForm() { document.getElementById("popupForm").style.display = "none"; }
        
        window.onclick = function(event) {
            let modal = document.getElementById("popupForm");
            if (event.target == modal) closeForm();
        }
    </script>
</body>
</html>s