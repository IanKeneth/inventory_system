<?php
session_start();
require_once "../auth/conn.php"; // Defines $pdo

// 1. Check if session exists
if (!isset($_SESSION['user_id'])) {
    header("Location: ../app/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // 2. Fetch data using PDO
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. SECURE CONDITIONING: If the user doesn't exist in the DB, kill the session
    if (!$user) {
        session_destroy();
        header("Location: ../app/login.php?error=account_not_found");
        exit();
    }

    // Now it is safe to assign these
    $user_name = $user['name'];
    $user_email = $user['email'];

} catch (PDOException $e) {
    // Handle database errors securely (don't show full error to users in production)
    die("A database error occurred. Please try again later.");
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
        .input-wrapper .toggle-eye { left: auto; right: 10px; cursor: pointer; }
        .settings-input { width: 100%; padding: 8px 35px 8px 32px; border: 1px solid #e0ddd0; border-radius: 8px; box-sizing: border-box; font-size: 0.85rem; background: #fff; }
        .card-title { font-size: 0.9rem; font-weight: bold; color: #444; margin-bottom: 10px; display: block; }
        .permissions-area { border: 1px solid #e0ddd0; border-radius: 8px; margin-top: 5px; background: #f9f9f9; padding: 12px; font-size: 0.8rem; color: #666; }
        .btn-row { display: flex; justify-content: flex-end; margin-top: 10px; }
        .apply-btn { background: #f28c28; color: white; border: none; padding: 8px 25px; border-radius: 20px; font-weight: bold; cursor: pointer; font-size: 0.85rem; }
        .alert { padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.85rem; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Staff Panel</span></div>
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> <span>Dashboard</span></a>
                <a href="user_inventory.php" class="nav-item"><i class="fa-solid fa-right-left"></i> <span>User Inventory</span></a>
                <a href="transfer_request.php" class="nav-item"><i class="fa-solid fa-right-left"></i> <span>Transfer Request</span></a>
                <a href="orders.php" class="nav-item"><i class="fa-solid fa-pen-to-square"></i> <span>Order</span></a>
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
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form action="../update_staff_profile.php" method="POST">
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
                                        <input type="text" name="full_name" class="settings-input" value="<?= htmlspecialchars($user_name) ?>" required>
                                    </div>
                                    <div class="input-wrapper">
                                        <i class="fa-solid fa-envelope"></i>
                                        <input type="email" name="email" class="settings-input" value="<?= htmlspecialchars($user_email) ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="settings-card">
                            <span class="card-title">Update Password</span>
                            <div class="input-wrapper" style="margin-bottom: 8px;">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" name="curr_pass" class="settings-input pass-input" placeholder="Current Password">
                                <i class="fa-regular fa-eye toggle-eye"></i>
                            </div>
                            <div class="input-wrapper" style="margin-bottom: 8px;">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" name="new_pass" class="settings-input pass-input" placeholder="New Password">
                                <i class="fa-regular fa-eye toggle-eye"></i>
                            </div>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" name="conf_pass" class="settings-input pass-input" placeholder="Confirm New Password">
                                <i class="fa-regular fa-eye toggle-eye"></i>
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
        // Sidebar Toggle logic
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });

        // Eye Icon Logic
        document.querySelectorAll('.toggle-eye').forEach(eye => {
            eye.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.pass-input');
                if (input.type === "password") {
                    input.type = "text";
                    this.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = "password";
                    this.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });
    </script>
</body>
</html>