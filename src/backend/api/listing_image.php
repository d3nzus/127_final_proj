<?php
    #api endpoint for listing_image table in MariaDB database

    header("Content-Type: application/json");

    require_once("../config/config.php");

    $conn = new mysqli($host, $user, $password, $dbname);

    $sql = "SELECT * FROM listing_image";
    $result = $conn->query($sql);

    $listing_images = [];

    while ($row = $result->fetch_assoc()) {
        $listing_images[] = $row;
}

echo json_encode($listing_images);

?>