<?php require 'conn.php';
 try { 
    $name = "Admin";
    $email = "admin@gmail.com";
    $password = "Admin@123";
    $role = "admin";

    $password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $password, $role]);
    }catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        ?>
        <script>
            alert("Sorry we are having technical issues");
        </script>
        <?php
}
?>