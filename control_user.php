<?php
require_once "auth/conn.php"; 

function e($value) { 
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); 
}

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: admin/User-management.php");
    exit;
}

if (isset($_POST['update_role'])) {
    $new_role = trim($_POST['role']);
    
    $sql = "UPDATE users SET role = :role WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute(['role' => $new_role, 'id' => $id])) {
        echo "<script>
                alert('Role updated successfully!');
                window.location.href='admin/User-management.php';
              </script>";
        exit;
    } else {
        echo "<script>alert('Failed to update role.');</script>";
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Edit Role</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #ffffff;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.container {
    width: 100%;
    max-width: 450px;
    padding: 20px;
}

.card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    padding: 30px;
}

h2 {
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

.form-control:focus {
    border-color: orange;
    outline: none;
}

.btn {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: none;
    font-size: 15px;
    cursor: pointer;
    margin-top: 10px;
}

.btn-primary {
    background: orange;
    color: white;
}

.btn-primary:hover {
    background: #e69500;
}

.btn-secondary {
    background: #e69500;
    color: black;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-secondary:hover {
    background: #e69500;
}

hr {
    margin: 15px 0;
}

@media (max-width: 500px) {
    .card {
        padding: 20px;
    }
}
</style>
</head>

<body>
<div class="container">
    <div class="card">
        <h2>Edit User Role</h2>

        <form method="POST">

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" class="form-control"
                       value="<?php echo e($user['name']); ?>" readonly>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="text" class="form-control"
                       value="<?php echo e($user['email']); ?>" readonly>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control">
                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="staff" <?php echo $user['role'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                </select>
            </div>

            <button type="submit" name="update_role" class="btn btn-primary">
                Update Role
            </button>

            <a href="admin/User-management.php" class="btn btn-secondary">
                Cancel
            </a>

        </form>
    </div>
</div>
</body>
</html>