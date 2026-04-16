<?php
session_start();
require_once "../auth/conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'admin') {
    $name = trim($_POST['supplier_name']);
    $contact = trim($_POST['contact_person']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $category = $_POST['category'];
    $products = trim($_POST['products_supplied']); // New Field

    try {
        // Check for duplicates
        $stmt = $pdo->prepare("SELECT id FROM suppliers WHERE LOWER(supplier_name) = LOWER(?)");
        $stmt->execute([$name]);
        
        if ($stmt->fetch()) {
            header("Location: supplies.php?error=duplicate");
            exit;
        }

        $sql = "INSERT INTO suppliers (supplier_name, contact_person, email, phone, category, products_supplied) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$name, $contact, $email, $phone, $category, $products]);

        header("Location: supplies.php?success=added");
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}