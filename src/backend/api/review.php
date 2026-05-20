<?php
    #api endpoint for review table in MariaDB database

    header("Content-Type: application/json");

    global $conn;

    $sql = "SELECT * FROM review";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($reviews);

?>