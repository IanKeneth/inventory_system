<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Basic Updates</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .updates-container { padding: 20px; display: flex; justify-content: center; }
        .updates-card {
            background: #fffdf8;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            width: 100%;
            max-width: 800px;
            padding: 0;
            overflow: hidden;
        }
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        .card-header h2 { font-size: 1.5rem; color: #333; margin: 0; }
        
        .form-section { padding: 25px; }
        .section-divider { border-top: 1px solid #ddd; margin: 20px 0; position: relative; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; font-size: 0.9rem; color: #444; }
        
        .input-with-icon { position: relative; }
        .input-with-icon i { position: absolute; left: 12px; top: 12px; color: #aaa; }
        .form-control {
            width: 100%;
            padding: 10px 10px 10px 35px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background: #fff;
        }
        
        .qty-row { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
        .qty-label-box { 
            background: #fff; border: 1px solid #ccc; padding: 8px 15px; 
            border-radius: 5px; font-weight: bold; font-size: 0.8rem;
        }

        .category-list { display: flex; flex-direction: column; gap: 5px; margin-top: 10px; }
        .cat-item {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            font-size: 0.85rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
        }
        .cat-item:hover { background: #fdfaf5; }

        .submit-all-btn {
            background: #f28c28;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            display: block;
            margin: 20px auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Basic Reports</span></div>
           <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item ">
                    <i class="fa-solid fa-table-columns"></i> 
                    <span>Dashboard</span>
                </a>
                <a href="transfer_request.php" class="nav-item ">
                    <i class="fa-solid fa-right-left"></i> 
                    <span>Transfer Request</span>
                </a>
                <a href="basic_reports.php" class="nav-item active">
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
                    <h1>Basic Updates & Reporting</h1>
                </div>
            </header>

            <section class="updates-container">
                <div class="updates-card">
                    <div class="card-header"><h2>Basic Updates & Reporting</h2></div>
                    
                    <form class="form-section">
                        <p style="font-weight: bold; margin-bottom: 15px;">Quick Update</p>
                        <div class="form-group">
                            <label>Product Name:</label>
                            <div class="input-with-icon">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <select class="form-control">
                                    <option>Brooms</option>
                                    <option>Mops</option>
                                </select>
                            </div>
                        </div>
                        <div class="qty-row">
                            <div class="qty-label-box">QUANTITY ON HAND</div>
                            <input type="number" class="form-control" style="width: 100px; padding-left: 10px;" value="150">
                        </div>

                        <div class="section-divider"></div>

                        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px;">
                            <div>
                                <div class="form-group">
                                    <label>Report Title:</label>
                                    <input type="text" class="form-control" style="padding-left: 10px;">
                                </div>
                                <div class="form-group">
                                    <label>Detailed Description</label>
                                    <textarea class="form-control" rows="5" style="padding-left: 10px;"></textarea>
                                </div>
                            </div>
                            <div>
                                <label>Category</label>
                                <div class="category-list">
                                    <div class="cat-item">Inventory Issue <i class="fa-solid fa-chevron-down"></i></div>
                                    <div class="cat-item">Supply Request</div>
                                    <div class="cat-item">Recent Orders Receive</div>
                                    <div class="cat-item">Maintenance Needed</div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="submit-all-btn">SUBMIT ALL UPDATES</button>
                    </form>
                </div>
            </section>
        </main>
    </div>
</body>
</html>