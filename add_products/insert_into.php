<?php
require_once '../auth/conn.php';

$target_dir = "../uploads/";
$image_name = "default-product.png";

// Create uploads folder if it doesn't exist
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// 1. Image Upload Logic
if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = $_FILES['product_image']['type'];

    if (in_array($file_type, $allowed_types)) {
        $file_ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $p_name   = $_POST['product_name'] ?? 'product';
        $clean_name = preg_replace("/[^a-zA-Z0-9]/", "_", $p_name);
        $image_name = time() . "_" . $clean_name . "." . $file_ext;

        if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $target_dir . $image_name)) {
            // Upload failed — fall back and log why
            error_log("Image upload failed. Check permissions on: " . $target_dir);
            $image_name = "default-product.png";
        }
    } else {
        error_log("Invalid file type: " . $file_type);
        $image_name = "default-product.png";
    }
} else {
    // Log the upload error code if present
    if (isset($_FILES['product_image']['error']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        error_log("File upload error code: " . $_FILES['product_image']['error']);
    }
}

// 2. Database Insertion with Snapshot
try {
    $pdo->beginTransaction();

    $sql = "INSERT INTO products 
                (category, product_name, variation, description, price, quantity, max_quantity, image_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['category']     ?? 'General',
        $_POST['product_name'] ?? 'Unnamed',
        $_POST['variation']    ?? 'Standard',
        $_POST['description']  ?? '',
        $_POST['price']        ?? 0,
        $_POST['quantity']     ?? 0,
        $_POST['max_quantity'] ?? 100,
        $image_name                          // ← always has a value now
    ]);

    $new_id = $pdo->lastInsertId();

    // Create the Initial Snapshot Log
    $log_sql = "INSERT INTO inventory_logs (product_id, type, quantity, Q_qty, reason) 
                VALUES (?, 'In', ?, ?, 'Initial Registration')";
    $log_stmt = $pdo->prepare($log_sql);
    $qty = $_POST['quantity'] ?? 0;
    $log_stmt->execute([$new_id, $qty, $qty]);

    $pdo->commit();

    header("Location: ../admin/inventory.php?success=1");
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("DB Insert Error: " . $e->getMessage());
    die("Error: " . $e->getMessage());
}
?>