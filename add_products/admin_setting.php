<?php
session_start();
require_once "../auth/conn.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);

    try {

        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
            $upload_dir = "../assets/uploads/profiles/";
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $new_filename = "admin_" . $user_id . "_" . time() . "." . $ext;
            $target_file = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                $stmt->execute([$new_filename, $user_id]);
                
                $_SESSION['user_pic'] = $new_filename;
            }
        }

        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $user_id]);
        $_SESSION['user_name'] = $full_name;

        $_SESSION['success'] = "Upload success! Profile updated.";

    } catch (PDOException $e) {
        $_SESSION['error'] = "Database Error: " . $e->getMessage();
    }
    header("Location: ../admin/settings.php");
    exit();
}