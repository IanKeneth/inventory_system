<?php
session_start();
require_once "../auth/conn.php";

if (isset($_POST['apply_changes'])) {
    $user_id = $_SESSION['user_id'];
    $full_name = $_POST['full_name'];
    
    $profile_pic = null;
    if (!empty($_FILES['profile_pic']['name'])) {
        $filename = time() . '_' . $_FILES['profile_pic']['name'];
        move_uploaded_file($_FILES['profile_pic']['tmp_id'], "../assets/uploads/profiles/" . $filename);
        
        $stmt = $pdo->prepare("UPDATE users SET name = ?, profile_pic = ? WHERE id = ?");
        $stmt->execute([$full_name, $filename, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->execute([$full_name, $user_id]);
    }

    if (!empty($_POST['new_password'])) {
        $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_pass, $user_id]);
    }

    $_SESSION['success'] = "Profile updated successfully!";
    header("Location: settings.php");
    exit();
}