<?php
session_start();
require_once "auth/conn.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $curr_pass = $_POST['curr_pass'];
    $new_pass = $_POST['new_pass'];
    $conf_pass = $_POST['conf_pass'];

    try {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $user_id]);
        
        $_SESSION['success'] = "Profile updated!";
        $_SESSION['user_name'] = $full_name;
        if (!empty($curr_pass)) {
            $query = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $query->execute([$user_id]);
            $user = $query->fetch(PDO::FETCH_ASSOC);

            if (password_verify($curr_pass, $user['password'])) {
                if (!empty($new_pass) && $new_pass === $conf_pass) {
                    $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upd->execute([$hashed_password, $user_id]);
                    $_SESSION['success'] = "Profile and password updated successfully!";
                } else {
                    $_SESSION['error'] = "New passwords do not match.";
                }
            } else {
                $_SESSION['error'] = "Current password is incorrect.";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }

    header("Location: user/settings.php");
    exit();
}