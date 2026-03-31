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
        .status-text { font-weight: bold; }
        .approved { color: #2ecc71; }
        .pending { color: #f1c40f; }

        .modal {
            display: none; 
            position: fixed;
            z-index: 9999;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center; 
            align-items: center;
        }

        .modal-content {
            background: #fff;
            padding: 30px;
            width: 500px; 
            border-radius: 12px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .modal-content h2 {
            margin-top: 0;
            font-size: 1.8rem;
            text-transform: lowercase;
            font-weight: 800;
        }


        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .full-width { grid-column: span 2; }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #444;
            font-size: 0.9rem;
        }

        .form-control, .modal-content input, .modal-content select, .modal-content textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 0.95rem;
        }

        .modal-content textarea { resize: none; width: 100%; }

        .close {
            position: absolute;
            right: 20px; top: 15px;
            cursor: pointer;
            font-size: 24px;
            color: #aaa;
        }

        .submit-btn {
            grid-column: span 2;
            background-color: #fff;
            color: #333;
            border: 1px solid #ccc;
            padding: 12px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
            transition: 0.2s;
        }

        .submit-btn:hover { background-color: #f9f9f9; border-color: #999; }

        .refresh-btn {
            background-color: #f28c28;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            float: right;
            margin-left: 10px;
            margin-top: 20px;
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
                <a href="index.php" class="nav-item ">
                    <i class="fa-solid fa-table-columns"></i> 
                    <span>Dashboard</span>
                </a>
                <a href="transfer_request.php" class="nav-item active">
                    <i class="fa-solid fa-right-left"></i> 
                    <span>Transfer Request</span>
                </a>
                <a href="basic_reports.php" class="nav-item ">
                    <i class="fa-solid fa-pen-to-square"></i> 
                    <span>Basic Reports</span>
                </a>
                 <a href="orders.php" class="nav-item ">
                    <i class="fa-solid fa-pen-to-square"></i> 
                    <span>Order</span>
                </a>
                <a href="sales.php" class="nav-item">
                    <i class="fa-solid fa-chart-simple"></i> 
                    <span>Sales</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <i class="fa-solid fa-user-gear"></i> 
                    <span>Profile</span>
                </a>
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
                        <div class="badge bg-orange">TOTAL REQUEST: 25</div>
                        <div class="badge bg-green">ADMIN APPROVED: 12</div>
                        <div class="badge bg-red">ADMIN DECLINED: 3</div>
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
                            <tr>
                                <td>REQ-1001</td>
                                <td>Utility Brush (Long Handle)</td>
                                <td>100</td>
                                <td>WareHouse</td>
                                <td>Distributor Center</td>
                                <td>2026-03-22</td>
                                <td class="status-text approved">Approved</td>
                            </tr>
                            <tr>
                                <td>REQ-1002</td>
                                <td>Microfiber Mop</td>
                                <td>1000</td>
                                <td>Warehouse</td>
                                <td>DistributorCenter</td>
                                <td>2026-03-22</td>
                                <td class="status-text pending">Pending</td>
                            </tr>
                        </tbody>
                    </table>
                    <button class="refresh-btn">Refresh List</button>
                    <button class="refresh-btn" onclick="openForm()">Transfer Request</button>

                    <div id="popupForm" class="modal">
                        <div class="modal-content">
                            <span class="close" onclick="closeForm()">&times;</span>
                            <h2 style="color: #e67e22;">transfer list</h2>

                            <form class="form-grid">
                            <div class="form-group">
                                <label>Itme Name</label>
                                <input type="text" name="item_name" placeholder="Item Name" require>
                            </div>

                             <div class="form-group">
                                <label>qty</label>
                                <input type="number" name="qty" placeholder="Enter QTY" require>
                            </div>

                             <div class="form-group">
                                <label>Source Locatio:</label>
                                <select class="form-control">
                                    <option>Distribution Center</option>
                                    <option>Main Warehouse</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>TO (Destination Location):</label>
                                <select class="form-control">
                                    <option>Distribution Center</option>
                                    <option>Main Warehouse</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Date Request:</label>
                                <input type="date" class="form-control">
                            </div>
                            <div class="form-group full-width">
                                <label>Transfer Notes (Optional):</label>
                                <textarea class="form-control" rows="3" placeholder="Additional details..."></textarea>
                            </div>
                            <button type="submit" class="submit-btn">Submit Transfer Request</button>
                        </form>
                        </div>
                        </div>
                        <div style="clear: both;"></div>
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
</script>
</body>
</html>