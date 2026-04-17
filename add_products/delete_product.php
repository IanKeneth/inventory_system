<?php
session_start();
require_once '../auth/conn.php';

if (isset($_GET['id']) && $_SESSION['role'] === 'admin') {
    $id = $_GET['id'];
    try {
        $pdo->beginTransaction();
        
        $pdo->prepare("DELETE FROM inventory_logs WHERE product_id = ?")->execute([$id]);

        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        
        $pdo->commit();
        header("Location: ../admin/inventory.php?success=deleted");
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: ../admin/inventory.php?error=delete_failed");
    }
}