<?php
require_once "../auth/conn.php";

try {
    $stmt = $pdo->query("SELECT id, name, email, password, role, created_at FROM users ORDER BY created_at ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .quick-overview { margin-bottom: 25px; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
        .card-header { background: #fff; padding: 15px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; color: #555; }
        
        .gauge-container { display: flex; justify-content: space-around; padding: 25px; background: #fff; flex-wrap: wrap; gap: 20px; }
        .gauge-item { text-align: center; flex: 1; min-width: 120px; }
        .gauge-circle { width: 120px; height: 60px; border: 10px solid #eee; border-bottom: 0; border-radius: 120px 120px 0 0; position: relative; display: flex; align-items: flex-end; justify-content: center; margin: 0 auto 10px; }
        .gauge-circle span { font-weight: bold; font-size: 1.2rem; position: relative; top: 5px; }
        
        .blue { border-top-color: #3498db; border-left-color: #3498db; }
        .red { border-top-color: #e74c3c; }
        .yellow { border-top-color: #f1c40f; border-right-color: #f1c40f; }
        .green { border-top-color: #2ecc71; border-right-color: #2ecc71; border-left-color: #2ecc71;}

        
        .d-flex {
            display: flex;
            align-items: center;
        }

        .gap-2 {
            gap: 8px;
        }
        .btn{
            text-decoration:none;
            color:orangered;
            background-color: transparent;
            border: 1px solid #e67e22;
        }
    </style>
</head>
<body>

    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fa-solid fa-boxes-stacked"></i> 
                <span>Title</span>
            </div>

            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a>
                <a href="inventory.php" class="nav-item"><i class="fa-solid fa-boxes-packing"></i> <span>Inventory</span></a>
                <a href="supplies.php" class="nav-item"><i class="fa-solid fa-truck-ramp-box"></i> <span>Supplies</span></a>
                <a href="track&reports.php" class="nav-item"><i class="fa-solid fa-route"></i></i> <span>Track & Reports</span></a>
                <a href="view_orders.php" class="nav-item "><i class="fa-solid fa-file-invoice-dollar"></i> <span>view orders</span></a>
                <a href="User-management.php" class="nav-item active"><i class="fa-solid fa-users"></i> <span>User Management</span></a>
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-gears"></i> <span>Settings</span></a>
            </nav>

            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-item">
                    <i class="fa-solid fa-right-from-bracket icon"></i> 
                    <span>Logout</span>
                </a>
            </div>


        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <h1>User Management</h1>
                </div>
                <div class="user-profile">
                    <i class="fa-solid fa-circle-user"></i>
                </div>
            </header>
            <section class="inventory-list-card">
                <div class="card-header" style="margin: 10px ;">
                    <i class="fa-solid fa-file-lines" style="color: var(--primary-orange);"></i>
                    <strong>list of users</strong>
                </div>
                <div style="padding: 0 15px 15px 15px;">
                <table class="inventory-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>FULL NAME</th>
                        <th>EMAIL</th>
                        <th>PASSWORD</th>
                        <th>ROLE</th>
                        <th>CREATED_AT</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= e($user['id']) ?></td>
                                <td><?= e($user['name']) ?></td>
                                <td><?= e($user['email']) ?></td>
                                <td style="color: #999;">********</td> <td>
                                    <span class="badge"><?= e(strtoupper($user['role'])) ?></span>
                                </td>
                                <td><?= e(date('M d, Y', strtotime($user['created_at']))) ?></td>
                                <td>
                                <div class="d-flex gap-2">
                                    <a href="../control_user.php?id=<?= $user['id'] ?>" class="btn">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </a>

                                    <a href="../delete_user.php?id=<?= $user['id'] ?>" 
                                    class="btn" 
                                    style="color: #e74c3c; border-color: #e74c3c;" 
                                    onclick="return confirm('Are you sure you want to delete this user?');">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px;">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </section>
        </main>
    </div>

        </main>
    </div>

    <script>
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
    });
</script>
</body>
</html>