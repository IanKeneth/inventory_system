<?php
session_start();
require_once '../auth/conn.php';

// 1. Safety Check: Make sure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../app/login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_report'])) {
    $product = $_POST['product_name'];
    $qty = $_POST['quantity'];
    $title = $_POST['report_title'];
    $desc = $_POST['description'];
    $category = $_POST['category'];

    // 2. Added 'user_id' to the INSERT statement
    $sql = "INSERT INTO basic_reports (user_id, product_name, quantity_on_hand, report_title, description, category) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    // Pass the logged-in user's ID as the first value
    $stmt->execute([$current_user_id, $product, $qty, $title, $desc, $category]);
    
    header("Location: basic_reports.php");
    exit();
}

// 3. Fetch ONLY the reports belonging to the logged-in user
$sql_fetch = "SELECT * FROM basic_reports WHERE user_id = ? ORDER BY created_at DESC";
$stmt_fetch = $pdo->prepare($sql_fetch);
$stmt_fetch->execute([$current_user_id]);
$reports = $stmt_fetch->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Basic Updates</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .updates-container { padding: 30px; display: flex; flex-direction: column; align-items: center; }
        .table-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            width: 100%;
            max-width: 1000px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .modal-overlay {
            display: none; 
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .updates-card {
            background: #fffdf8;
            border-radius: 12px;
            width: 90%;
            max-width: 700px;
            padding: 0;
            position: relative;
            animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .card-header { padding: 20px; border-bottom: 1px solid #eee; text-align: center; position: relative;}
        .close-modal { position: absolute; right: 20px; top: 20px; cursor: pointer; font-size: 1.2rem; color: #999; }
        .form-section { padding: 25px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; font-size: 0.9rem; color: #444; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box;}

        .status-text { font-weight: bold; }
        .approved { color: #2ecc71; }
        .pending { color: #f1c40f; }
        .declined { color: #e74c3c; }

        .btn-create { background: #f28c28; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; margin-bottom: 20px; transition: 0.3s; }
        .btn-create:hover { background: #d9771d; transform: translateY(-2px); }
        .submit-all-btn { background: #f28c28; color: white; border: none; padding: 12px 30px; border-radius: 25px; font-weight: bold; cursor: pointer; display: block; margin: 20px auto; width: 100%; }

        .cat-item { padding: 10px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 5px; cursor: pointer; font-size: 0.85rem; transition: 0.2s; }
        .cat-item:hover, .cat-item.active { border-color: #f28c28; background: #fff8f0; color: #f28c28; font-weight: bold; }

        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th { background: #fff8f0; padding: 15px; text-align: left; color: #f28c28; font-weight: bold; border-bottom: 2px solid #f28c28; }
        .report-table td { padding: 15px; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        .report-table tr:hover { background: #fafafa; }
        .badge-cat { background: #eee; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; color: #555; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Basic Reports</span></div>
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> <span>Dashboard</span></a>
                <a href="user_inventory.php" class="nav-item"><i class="fa-solid fa-right-left"></i> <span>User Inventory</span></a>
                <a href="transfer_request.php" class="nav-item "><i class="fa-solid fa-right-left"></i> <span>Transfer Request</span></a>
                <a href="basic_reports.php" class="nav-item active"><i class="fa-solid fa-pen-to-square"></i> <span>Basic Reports</span></a>
                <a href="orders.php" class="nav-item "><i class="fa-solid fa-pen-to-square"></i> <span>Order</span></a>
                <a href="sales.php" class="nav-item"><i class="fa-solid fa-chart-simple"></i> <span>Sales</span></a>
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-user-gear"></i> <span>Profile</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1>Reports Dashboard</h1>
                </div>
                <button class="btn-create" onclick="openModal()">
                    <i class="fa-solid fa-plus"></i> CREATE NEW REPORT
                </button>
            </header>

            <section class="updates-container">
                <div class="table-card">
                    <div class="card-header" style="text-align: left;">
                        <h2 style="font-size: 1.2rem;"><i class="fa-solid fa-list-check" style="color:#f28c28"></i> Recent Submitted Reports</h2>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $r): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($r['product_name']) ?></strong></td>
                                    <td><span style="color: #f28c28; font-weight: bold;"><?= htmlspecialchars($r['quantity_on_hand']) ?></span></td>
                                    <td><?= htmlspecialchars($r['report_title']) ?></td>
                                    <td><span class="badge-cat"><?= htmlspecialchars($r['category']) ?></span></td>
                                    <td><?= nl2br(htmlspecialchars($r['description'])) ?></td>
                                    <td class="status-text <?= strtolower($r['status']) ?>"><?= $r['status'] ?></td>
                                    <td style="color: #888;"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div class="modal-overlay" id="reportModal">
        <div class="updates-card">
            <div class="card-header">
                <h2>New Report Entry</h2>
                <i class="fa-solid fa-xmark close-modal" onclick="closeModal()"></i>
            </div>
            
            <form class="form-section" method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="product_name" class="form-control" placeholder="Enter product name..." required>
                    </div>
                    <div class="form-group">
                        <label>Quantity On Hand</label>
                        <input type="number" name="quantity" class="form-control" value="0">
                    </div>
                </div>

                <div class="form-group">
                    <label>Report Title</label>
                    <input type="text" name="report_title" class="form-control" placeholder="Brief summary..." required>
                </div>

                <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Detailed Description</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Enter details here..." required></textarea>
                    </div>
                    <div>
                        <label>Category</label>
                        <input type="hidden" name="category" id="cat_input" value="Inventory Issue">
                        <div class="category-list">
                            <div class="cat-item active" onclick="selectCat(this, 'Inventory Issue')">Inventory Issue</div>
                            <div class="cat-item" onclick="selectCat(this, 'Supply Request')">Supply Request</div>
                            <div class="cat-item" onclick="selectCat(this, 'Recent Orders')">Recent Orders</div>
                            <div class="cat-item" onclick="selectCat(this, 'Maintenance')">Maintenance Needed</div>
                        </div>
                    </div>
                </div>

                <button type="submit" name="submit_report" class="submit-all-btn">SUBMIT REPORT</button>
            </form>
        </div>
    </div>

    <script>
        // Modal Control
        function openModal() {
            document.getElementById('reportModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('reportModal').style.display = 'none';
        }

        // Category selection
        function selectCat(el, val) {
            document.querySelectorAll('.cat-item').forEach(i => i.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('cat_input').value = val;
        }

        // Close modal if clicking outside the white card
        window.onclick = function(event) {
            let modal = document.getElementById('reportModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>