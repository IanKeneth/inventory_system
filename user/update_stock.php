<?php 
session_start();
require_once "../auth/conn.php"; 

/** @param mixed $value */
function e($value): string { 
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); 
}

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: user_inventory.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) { die("Product not found."); }

if (isset($_POST['update_stock'])) { 
    $new_qty = (int)$_POST['quantity']; 
    $max_qty = (int)$_POST['max_quantity'];
    $old_qty = (int)$product['quantity'];
    $uid     = $_SESSION['user_id'] ?? 0;

    if ($new_qty > $max_qty) {
        echo "<script>alert('Error: Exceeds Max Capacity!'); window.history.back();</script>";
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE products SET quantity = ?, max_quantity = ? WHERE id = ?");
        $stmt->execute([$new_qty, $max_qty, $id]);

        if ($new_qty !== $old_qty) {
            $diff = abs($new_qty - $old_qty);
            $type = ($new_qty > $old_qty) ? 'In' : 'Out';
            $log  = $pdo->prepare("INSERT INTO inventory_logs (product_id, user_id, type, quantity, reason, log_date) VALUES (?, ?, ?, ?, 'Staff Adjustment', NOW())");
            $log->execute([$id, $uid, $type, $diff]);
        }

        $pdo->commit();
        header("Location: user_inventory.php?success=1");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("System Error: " . $e->getMessage());
    }
} 
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Stock</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f6; display: flex; justify-content: center; padding: 50px; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 350px; }
        h2 { margin-top: 0; color: #333; font-size: 1.2rem; }
        .info { background: #eef2ff; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .save { background: #f28c28; color: white; margin-bottom: 10px; }
        .cancel { background: #eee; color: #666; text-decoration: none; display: block; text-align: center; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="card">
    <h2>Update Levels</h2>
    <div class="info">
        <strong>Product:</strong> <?php echo e($product['product_name']); ?>
    </div>

    <form method="POST">
        <label>Current Stock</label>
        <input type="number" name="quantity" value="<?php echo $product['quantity']; ?>" required>

        <label>Max Capacity</label>
        <input type="number" name="max_quantity" value="<?php echo $product['max_quantity']; ?>" required>

        <button type="submit" name="update_stock" class="btn save">Save Updates</button>
        <a href="user_inventory.php" class="cancel">Go Back</a>
    </form>
</div>

</body>
</html>