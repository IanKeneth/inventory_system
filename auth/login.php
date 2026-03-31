<?php
session_start();
require 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password']) ?? '';

    try{
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        //Autentication
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                ?>
                <script>
                    alert("Welcome Admin!");
                    window.location.href ='../admin/index.php';
                </script>
                <?php
            } else {
                ?>
                <script>
                    alert("Welcome staff");
                    window.location.href ='../user/index.php';
                </script>
                <?php
            }
            exit();
        } else {
            ?>
            <script>
                alert("Invalid email or wrong password. Please try again.");
            </script>
            <?php
        }
    }catch(PDOException $e){
        error_log("DB Error: " . $e->getMessage());
        ?>
        <script>
            alert("Sorry we are having technical issues");
        </script>
        <?php
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="login-style.css">
</head>
<body>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-logo">
                <div class="avatar-circle">
                    <i class="fa-solid fa-user person-icon"></i>
                </div>
            </div>
            
            <h2 class="auth-title">Welcome Back</h2>
            <p class="auth-subtitle">Please enter your details to sign in.</p>
        
            <form action="login.php" method="POST">
                <div class="input-group">
                    <span class="input-icon"><i class="fa-regular fa-user"></i></span>
                    <input type="email" name="email" placeholder="Username" required>
                </div>

                <div class="input-group">
                    <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" placeholder="Password" required>
                    <span class="password-toggle"><i class="fa-regular fa-eye"></i></span>
                </div>

                <div class="form-options">
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn-primary">Login</button>
            </form>

            <div class="auth-footer">
                <span>New here? <a href="registration.php">Create an account</a></span>
            </div>
        </div>
    </div>

</body>
</html>