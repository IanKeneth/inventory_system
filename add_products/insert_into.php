<?php
session_start();
require_once '../auth/conn.php'; 
/** @var PDO $pdo */ 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $category     = $_POST['category'];
        $product_name = $_POST['product_name'];
        $variation    = $_POST['variation'];
        $description  = $_POST['description'];
        $price        = $_POST['price'];
        $quantity     = $_POST['quantity'];
        $max_qty      = $_POST['max_quantity'] ?? 100;

    
        $pdo->beginTransaction();

    
        $check_stmt = $pdo->prepare("SELECT id FROM products WHERE category = :cat AND product_name = :prod AND variation = :var LIMIT 1");
        $check_stmt->execute([':cat' => $category, ':prod' => $product_name, ':var' => $variation]);
        
        if ($check_stmt->fetch()) {
            $pdo->rollBack();
            header("Location: ../admin/inventory.php?error=duplicate");
            exit();
        }

    
        $sql = "INSERT INTO products (category, product_name, variation, description, price, quantity, max_quantity) 
                VALUES (:cat, :prod, :var, :desc, :price, :qty, :max)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cat' => $category, ':prod' => $product_name, ':var' => $variation,
            ':desc' => $description, ':price' => $price, ':qty' => $quantity, ':max' => $max_qty
        ]);

    
        $new_product_id = $pdo->lastInsertId();

        $log_sql = "INSERT INTO inventory_logs (product_id, type, quantity, reason) 
                    VALUES (:pid, 'In', :qty, :reason)";
        $log_stmt = $pdo->prepare($log_sql);
        $log_stmt->execute([
            ':pid'    => $new_product_id,
            ':qty'    => $quantity,
            ':reason' => "Initial stock registration"
        ]);

        $pdo->commit();
        header("Location: ../admin/inventory.php?success=1");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Database Error: " . $e->getMessage());
    }
}