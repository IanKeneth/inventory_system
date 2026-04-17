<?php
require_once "auth/conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];

    try {
        $sql = "UPDATE orders SET status = ? WHERE order_id = ?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$new_status, $order_id])) {
            header("Location: admin_orders.php?success=1");
            exit();
        }
    } catch (PDOException $e) {
        die("Error updating order: " . $e->getMessage());
    }
}
?>