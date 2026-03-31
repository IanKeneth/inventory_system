<?php
require_once "auth/conn.php";

// HANDLE STATUS UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];

    try {
        // 1. Prepare the update statement
        $sql = "UPDATE orders SET status = ? WHERE order_id = ?";
        $stmt = $pdo->prepare($sql);
        
        // 2. Execute the update
        if ($stmt->execute([$new_status, $order_id])) {
            // Success! Redirect to show the message
            header("Location: admin_orders.php?success=1");
            exit();
        }
    } catch (PDOException $e) {
        // If something goes wrong (like a database error)
        die("Error updating order: " . $e->getMessage());
    }
}
?>