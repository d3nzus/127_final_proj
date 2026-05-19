<?php
    require_once __DIR__ . "/../config/config.php"

    # connect to MariaDB then create database
    $conn = new mysqli($host, $user, $password);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql_   db = "CREATE DATABASE IF NOT EXISTS `$dbname`";

    if ($conn->query($sql_db) === TRUE) {
        echo "Database ready successfully";
    }else{
        echo "Error creating database: ".$conn->error;
    }

    $conn->close();
?>s