<?php
require 'conn.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //input validation
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $passwordPattern = "/^(?=.*[0-9])(?=.*[!@#$%^&*]).{8,}$/";

    if (empty($name) || !$email) {
        ?>
        <script>alert("Please fill in all fields.");</script>
        <?php
    }
    elseif (!preg_match($passwordPattern, $password)) {
        ?>
        <script>
            alert("Password too weak! Must be at least 8 characters or more and include special characters and numbers");
        </script>
        <?php
    }
    elseif ($password !== $confirm_password) {
        ?>
        <script>alert("Passwords do not match!");</script>
        <?php
    } else {
        try{
            // Check if email already exists
            //Using prepare statement and parameters for secucrity  
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                ?>
                <script>alert("Email already registered!");</script>
                <?php
            } else {
                // Check if email already exists
            //Using prepare statement and parameters for secucrity  
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                if ($insert->execute([$name, $email, $hashed])) {
                    ?>
                    <script>
                        alert("Registration successful! Please login.");
                        window.location.href = 'login.php';
                    </script>
                    <?php
                }
            }
            //error handling
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            ?>
            <script>
                alert("Sorry we are having technical issues");
            </script>"
            <?php
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="registration-style.css">
</head>
<body>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-logo">
                <div class="avatar-circle">
                    <i class="fa-solid fa-user-plus person-icon"></i>
                </div>
            </div>         
            <h2 class="auth-title">Create Account</h2>
            <p class="auth-subtitle">Join us to start managing your inventory.</p>
            
            <form action="registration.php" method="POST">
                <div class="input-group">
                    <span class="input-icon"><i class="fa-regular fa-user"></i></span>
                    <input type="text" name="name" placeholder="Full Name" required>
                </div>

                <div class="input-group">
                    <span class="input-icon"><i class="fa-regular fa-envelope"></i></span>
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>

                <div class="input-group">
                    <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" placeholder="Password" required>
                </div>

                <div class="input-group">
                    <span class="input-icon"><i class="fa-solid fa-shield-halved"></i></span>
                    <input type="password" name="confirm_password"   placeholder="Confirm Password" required>
                </div>

                <button type="submit" class="btn-primary">Register Now</button>
            </form>

            <div class="auth-footer">
                <span>Already have an account? <a href="login.php">Login</a></span>
            </div>
        </div>
    </div>

</body>
</html>