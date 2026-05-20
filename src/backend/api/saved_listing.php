<?php
    #api endpoint for saved_listing table in MariaDB database

    header("Content-Type: application/json");

    global $conn;

    $sql = "SELECT * FROM saved_listing";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $saved_listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($saved_listings);

?>