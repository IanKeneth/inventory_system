<?php
// 1. Set headers so the receiver knows this is JSON data
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Allows other sites to access this data

require_once '../auth/conn.php'; 

// 2. Fetch the data just like your current code
try {
    $stmt = $pdo->prepare("SELECT id, product_name, category,variation,description, price, quantity FROM products ORDER BY id DESC"); 
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Send the data as a JSON object
    echo json_encode([
        "status" => "success",
        "count" => count($products),
        "data" => $products
    ]);

} catch(PDOException $e) {
    // Send an error message if something goes wrong
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>