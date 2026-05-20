<?php
    #api endpoint for listing table in MariaDB database

    header("Content-Type: application/json");

    global $conn;

    $sql = "SELECT * FROM listing";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($listings);

?>