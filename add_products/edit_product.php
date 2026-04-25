<?php 
require_once "../auth/conn.php";  
/** @var PDO $pdo */ 

function e($value) { 
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); 
} 

$id = $_GET['id'] ?? null; 

if (!$id) { 
    header("Location: ../admin/inventory.php"); 
    exit; 
} 

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found in database.");
}

if (isset($_POST['update_product'])) { 
    $name = trim($_POST['product_name']); 
    $category = trim($_POST['category']); 
    $price = trim($_POST['price']); 
    $new_quantity = (int)$_POST['quantity']; 
    $max_quantity = (int)$_POST['max_quantity']; 
    $old_quantity = (int)$product['quantity'];
    
    // --- IMAGE HANDLING LOGIC ---
    $image_name = $product['image_path']; // Keep existing image by default

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
        $target_dir = "../uploads/";
        $file_ext = pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION);
        $image_name = time() . "_" . preg_replace("/[^a-zA-Z0-9]/", "_", $name) . "." . $file_ext;
        $target_file = $target_dir . $image_name;

        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            // Optional: Delete the old image file from folder if it's not the default
            if ($product['image_path'] && $product['image_path'] != 'default-product.png' && file_exists($target_dir . $product['image_path'])) {
                unlink($target_dir . $product['image_path']);
            }
        }
    }
    // ----------------------------

    if ($new_quantity > $max_quantity) { 
        echo "<script>
                alert('Error: Quantity ($new_quantity) cannot be greater than Max Quantity ($max_quantity)!');
                window.history.back();
              </script>"; 
        exit; 
    } 

    try {
        $pdo->beginTransaction();

        $sql = "UPDATE products 
                SET product_name = :name, category = :cat, price = :price, 
                    quantity = :qty, max_quantity = :max, image_path = :img 
                WHERE id = :id"; 

        $stmt = $pdo->prepare($sql); 
        $stmt->execute([
            ':name'  => $name,
            ':cat'   => $category,
            ':price' => $price,
            ':qty'   => $new_quantity,
            ':max'   => $max_quantity,
            ':img'   => $image_name,
            ':id'    => $id
        ]);

        if ($new_quantity !== $old_quantity) {
            $diff = abs($new_quantity - $old_quantity);
            $type = ($new_quantity > $old_quantity) ? 'In' : 'Out';
            $reason = "Manual Adjustment (Edit Profile)";

            $log_sql = "INSERT INTO inventory_logs (product_id, type, quantity, reason) 
                        VALUES (:pid, :type, :qty, :reason)";
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([
                ':pid'    => $id,
                ':type'   => $type,
                ':qty'    => $diff,
                ':reason' => $reason
            ]);
        }

        $pdo->commit();
        echo "<script>
                alert('Product updated successfully!');
                window.location.href='../admin/inventory.php';
                </script>"; 
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
} 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Product - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root { --primary: #f28c28; --secondary: #64748b; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f8fafc; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 30px; width: 100%; max-width: 450px; }
        h1 { text-align: center; margin-bottom: 20px; color: #1e293b; font-size: 1.4rem; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: 600; display: block; margin-bottom: 5px; color: #475569; font-size: 0.85rem; }
        .form-control { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 14px; box-sizing: border-box; }
        .row { display: flex; gap: 10px; }
        .col { flex: 1; }
        .image-preview { width: 80px; height: 80px; border-radius: 8px; object-fit: cover; border: 2px solid #eee; margin-bottom: 10px; }
        .btn { padding: 12px; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 10px; transition: 0.3s; text-decoration: none; display: block; text-align: center; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-secondary { background: #f1f5f9; color: var(--secondary); border: 1px solid #e2e8f0; }
    </style>
</head>
<body>

<div class="card">
    <h1>Edit Product Profile</h1>

    <form method="POST" enctype="multipart/form-data">
        
        <div style="text-align: center;">
            <img src="../uploads/<?php echo $product['image_path'] ?: 'default-product.png'; ?>" class="image-preview" id="preview">
            <div class="form-group">
                <label>Change Product Image</label>
                <input type="file" name="image_path" class="form-control" accept="image/*" onchange="previewImage(this)">
            </div>
        </div>

        <div class="form-group">
            <label>Product Name</label>
            <input type="text" name="product_name" class="form-control" value="<?php echo e($product['product_name']); ?>" required>
        </div>

        <div class="form-group">
            <label>Category</label>
            <input type="text" name="category" class="form-control" value="<?php echo e($product['category']); ?>" required>
        </div>

        <div class="form-group">
            <label>Price (₱)</label>
            <input type="number" step="0.01" name="price" class="form-control" value="<?php echo e($product['price']); ?>" required>
        </div>

        <div class="row">
            <div class="col">
                <div class="form-group">
                    <label>Current Stock</label>
                    <input type="number" name="quantity" class="form-control" value="<?php echo e($product['quantity']); ?>" required>
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label>Max Capacity</label>
                    <input type="number" name="max_quantity" class="form-control" value="<?php echo e($product['max_quantity']); ?>" required>
                </div>
            </div>
        </div>

        <button type="submit" name="update_product" class="btn btn-primary">Save Changes</button>
        <a href="../admin/inventory.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

</body>
</html>