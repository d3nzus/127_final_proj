<?php

// database connection settings
$host = "localhost";
$dbname = "upcart";
$user = "root";
$password = "";

try {
    // create PDO instance
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);

    // set error mode to exception for debugging
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // handle connection error
    die("Database connection failed: " . $e->getMessage());
}
?>