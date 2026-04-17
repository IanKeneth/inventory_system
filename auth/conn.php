<?php
$host = "localhost";
$dbname = "issa_system";
$username = "root";
$password = "";


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if (!function_exists('e')) {
    function e($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
?>