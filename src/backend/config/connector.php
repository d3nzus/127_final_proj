<?php
    require_once __DIR__ . "/config.php";

    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
?>