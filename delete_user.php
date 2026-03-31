<?php
require_once "auth/conn.php"; 

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: tables.php");
    exit;
}


$sql = "DELETE FROM users WHERE id = :id";
$stmt = $pdo->prepare($sql);

try {
    if ($stmt->execute(['id' => $id])) {
        echo "<script>
                alert('User account deleted successfully.');
                window.location.href='tables.php';
                </script>";
    } else {
        echo "<script>
                alert('Error: Could not delete user.');
                window.location.href='tables.php';
                </script>";
    }
} catch (PDOException $e) {

    echo "<script>
            alert('Database Error: " . addslashes($e->getMessage()) . "');
            window.location.href='admin/User-management.php';
            </script>";
}
?>