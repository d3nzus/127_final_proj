<?php
    #api endpoint for category table in MariaDB database

    header("Content-Type: application/json");

    global $conn;

    $sql = "SELECT * FROM category";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($categories);

?>