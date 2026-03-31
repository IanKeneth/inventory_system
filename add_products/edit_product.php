<?php 
require_once "../auth/conn.php";  

function e($value) { 
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); 
} 

$id = $_GET['id'] ?? null; 

if (!$id) { 
    header("Location: ../admin/inventory.php"); 
    exit; 
} 

if (isset($_POST['update_product'])) { 
    $name = trim($_POST['product_name']); 
    $category = trim($_POST['category']); 
    $price = trim($_POST['price']); 
    $quantity = trim($_POST['quantity']); 
    $max_quantity = trim($_POST['max_quantity']); 

    if ($quantity > $max_quantity) { 
        echo "<script>
                alert('Error: Quantity ($quantity) cannot be greater than Max Quantity ($max_quantity)!');
                window.history.back();
              </script>"; 
        exit; 
    } 

    $sql = "UPDATE products 
            SET product_name = :name, category = :cat, price = :price, 
                quantity = :qty, max_quantity = :max 
            WHERE id = :id"; 

    $stmt = $pdo->prepare($sql); 

    $params = [
        ':name'  => $name,
        ':cat'   => $category,
        ':price' => $price,
        ':qty'   => $quantity,
        ':max'   => $max_quantity,
        ':id'    => $id
    ]; 

    if ($stmt->execute($params)) { 
        echo "<script>
                alert('Product updated successfully!');
                window.location.href='../admin/inventory.php';
              </script>"; 
        exit; 
    } else { 
        echo "<script>alert('Failed to update product.');</script>"; 
    } 
} 

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found in database.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Edit Product</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #fff, #efff);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.container {
    width: 100%;
    max-width: 500px;
    padding: 20px;
}

.card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    padding: 30px;
    width: 100%;
}

h1 {
    text-align: center;
    margin-bottom: 20px;
    color: #333;
}

.form-group {
    margin-bottom: 15px;
}

label {
    font-weight: bold;
    display: block;
    margin-bottom: 5px;
}

.form-control {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
}

.row {
    display: flex;
    gap: 10px;
}

.col {
    flex: 1;
}

.btn {
    padding: 10px;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    cursor: pointer;
    width: 100%;
    margin-top: 10px;
}

.btn-primary {
    background: orange;
    color: white;
}

.btn-primary:hover {
    background: orange;
}

.btn-secondary {
    background: orange;
    color: white;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-secondary:hover {
    background: orange;
}

@media (max-width: 500px) {
    .row {
        flex-direction: column;
    }
}
</style>
</head>

<body>
<div class="container">
    <div class="card">
        <h1>Edit Product</h1>

        <form method="POST">

            <div class="form-group">
                <label>Product Name</label>
                <input type="text" name="product_name" class="form-control"
                       value="<?php echo e($product['product_name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" class="form-control"
                       value="<?php echo e($product['category']); ?>" required>
            </div>

            <div class="row">
                <div class="col">
                    <label>Price ($)</label>
                    <input type="number" step="0.01" name="price" class="form-control"
                           value="<?php echo e($product['price']); ?>" required>
                </div>

                <div class="col">
                    <label>Max Capacity</label>
                    <input type="number" name="max_quantity" class="form-control"
                           value="<?php echo e($product['max_quantity']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Current Quantity</label>
                <input type="number" name="quantity" class="form-control"
                       value="<?php echo e($product['quantity']); ?>" required>
            </div>

            <button type="submit" name="update_product" class="btn btn-primary">
                Update Product
            </button>

            <a href="../admin/inventory.php" class="btn btn-secondary">
                Cancel
            </a>

        </form>
    </div>
</div>
</body>
</html>