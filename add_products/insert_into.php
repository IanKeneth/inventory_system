<?php
require '../auth/conn.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST"){
    $product_name = htmlspecialchars(trim($_POST['product_name']));
    $category = htmlspecialchars(trim($_POST['category']));
    $price = trim($_POST['price'] ?? 0);
    $quantity = trim($_POST['quantity'] ?? 0);
    $max_quantity = trim($_POST['max_quantity'] ?? 0);

    $insert = $pdo->prepare("INSERT INTO products (product_name, category, price, quantity, max_quantity) VALUES (?, ?, ?, ?, ?)");
    if ($insert->execute([$product_name, $category, $price, $quantity, $max_quantity])) {
        header("Location: ../admin/inventory.php?status=success");
        exit();
    } else {
        echo "Error: Could not insert the product.";
    }
}
try {
    $stmt = $pdo->query("SELECT id, product_name, category, price, quantity, max_quantity FROM products ORDER BY id DESC");
    $all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Selection Error: " . $e->getMessage());
}
?>