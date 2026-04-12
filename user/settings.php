<?php
session_start();
require_once "../auth/conn.php";

// Fetch staff details from session
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? "Staff Member";
$user_email = $_SESSION['user_email'] ?? "staff@example.com";

if (!$user_id) {
    header("Location: ../app/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .main-content { background-color: #fdfbf4; height: 100vh; overflow-y: auto; display: flex; flex-direction: column; }
        .header { position: sticky; top: 0; z-index: 1000; flex-shrink: 0; border-bottom: 1px solid #e8e4d8; }
        .settings-container { max-width: 900px; margin: 0 auto; width: 100%; display: flex; flex-direction: column; gap: 12px; padding: 20px; padding-bottom: 50px; }
        .settings-card { background: white; border: 1px solid #e8e4d8; border-radius: 12px; padding: 15px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03); }
        .profile-header { display: flex; align-items: flex-start; gap: 15px; }
        .profile-pic-container { display: flex; flex-direction: column; align-items: center; gap: 8px; }
        .profile-pic { font-size: 60px; color: #7ba6c9; }
        .change-photo-btn { background: #f28c28; color: white; border: none; padding: 4px 12px; border-radius: 15px; font-size: 0.7rem; cursor: pointer; white-space: nowrap; }
        .input-group { flex-grow: 1; display: flex; flex-direction: column; gap: 8px; }
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #888; font-size: 0.85rem; }
        .settings-input { width: 100%; padding: 8px 10px 8px 32px; border: 1px solid #e0ddd0; border-radius: 8px; box-sizing: border-box; font-size: 0.85rem; background: #fff; }
        .card-title { font-size: 0.9rem; font-weight: bold; color: #444; margin-bottom: 10px; display: block; }
        .notification-sub { font-size: 0.8rem; font-weight: bold; color: #333; margin: 10px 0 5px 0; }
        .toggle-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .toggle-item { display: flex; align-items: center; gap: 8px; font-size: 0.75rem; color: #555; }
        .switch { position: relative; display: inline-block; width: 34px; height: 18px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 20px; }
        .slider:before { position: absolute; content: ""; height: 12px; width: 12px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #f28c28; }
        input:checked + .slider:before { transform: translateX(16px); }
        .permissions-area { border: 1px solid #e0ddd0; border-radius: 8px; margin-top: 5px; background: #f9f9f9; padding: 12px; font-size: 0.8rem; color: #666; }
        .btn-row { display: flex; justify-content: flex-end; margin-top: 10px; }
        .apply-btn { background: #f28c28; color: white; border: none; padding: 8px 25px; border-radius: 20px; font-weight: bold; cursor: pointer; font-size: 0.85rem; }
    </style>
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Staff Panel</span></div>
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item "><i class="fa-solid fa-table-columns"></i> <span>Dashboard</span></a>
                 <a href="user_inventory.php" class="nav-item">
                    <i class="fa-solid fa-right-left"></i> <span>User Inventory</span>
                </a>
                <a href="transfer_request.php" class="nav-item"><i class="fa-solid fa-right-left"></i> <span>Transfer Request</span></a>
                <a href="orders.php" class="nav-item "><i class="fa-solid fa-pen-to-square"></i> <span>Order</span></a>
                <a href="settings.php" class="nav-item active"><i class="fa-solid fa-user-gear"></i> <span>Profile</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1>Staff Settings</h1>
                </div>
                <div class="user-profile"><i class="fa-solid fa-circle-user"></i></div>
            </header>

            <section class="settings-container">
                <form action="../app/update_staff_profile.php" method="POST">
                    <div style="display: flex; flex-direction: column; gap: 15px;">

                        <div class="settings-card">
                            <span class="card-title">My Account</span>
                            <div class="profile-header">
                                <div class="profile-pic-container">
                                    <i class="fa-solid fa-circle-user profile-pic"></i>
                                    <button type="button" class="change-photo-btn">Change</button>
                                </div>
                                <div class="input-group">
                                    <div class="input-wrapper">
                                        <i class="fa-solid fa-user"></i>
                                        <input type="text" name="full_name" class="settings-input" value="<?= htmlspecialchars($user_name) ?>">
                                    </div>
                                    <div class="input-wrapper">
                                        <i class="fa-solid fa-envelope"></i>
                                        <input type="email" name="email" class="settings-input" value="<?= htmlspecialchars($user_email) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="settings-card">
                            <span class="card-title">Update Password</span>
                            <div class="input-wrapper" style="margin-bottom: 8px;">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" name="curr_pass" class="settings-input" placeholder="Current Password">
                            </div>
                            <div class="input-wrapper" style="margin-bottom: 8px;">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" name="new_pass" class="settings-input" placeholder="New Password">
                            </div>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" name="conf_pass" class="settings-input" placeholder="Confirm New Password">
                            </div>
                        </div>

                        <div class="settings-card">
                            <span class="card-title">Your Access Level</span>
                            <div class="permissions-area">
                                <strong style="color: #f28c28;">Role: Staff Member</strong>
                                <ul style="margin: 8px 0 0 18px; padding: 0;">
                                    <li>View Dashboard statistics</li>
                                    <li>Process Transfer Requests</li>
                                    <li>Manage Customer Orders</li>
                                    <li style="color: #999;">Restricted: Financial Reports & User Management</li>
                                </ul>
                            </div>
                        </div>

                        <div class="btn-row">
                            <button type="submit" class="apply-btn">Save Profile</button>
                        </div>
                    </div>
                </form>
            </section>
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