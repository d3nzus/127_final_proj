<?php
    #api endpoint for saved_listing table in MariaDB database

    header("Content-Type: application/json");

    require_once("../config/config.php");

    $conn = new mysqli($host, $user, $password, $dbname);

    $sql = "SELECT * FROM saved_listing";
    $result = $conn->query($sql);

    $saved_listings = [];

    while ($row = $result->fetch_assoc()) {
        $saved_listings[] = $row;
}

echo json_encode($saved_listings);

?>