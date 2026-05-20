<?php
    #api endpoint for listing_image table in MariaDB database

    header("Content-Type: application/json");

    global $conn;

    $sql = "SELECT * FROM listing_image";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $listing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($listing_images);

?>