<?php
// Set headers so the receiver knows this is JSON data
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); 

require_once '../auth/conn.php'; 

try {
    // UPDATED: Added image_path and max_quantity to the SELECT statement
    $stmt = $pdo->prepare("SELECT id, product_name, category, variation, description, price, quantity, image_path, max_quantity FROM products ORDER BY id DESC"); 
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "count" => count($products),
        "data" => $products
    ]);

} catch(PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>