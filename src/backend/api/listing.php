<?php
    #api endpoint for listing table in MariaDB database

    header("Content-Type: application/json");

    require_once("../config/config.php");

    $conn = new mysqli($host, $user, $password, $dbname);

    $sql = "SELECT * FROM listing";
    $result = $conn->query($sql);

    $listings = [];

    while ($row = $result->fetch_assoc()) {
        $listings[] = $row;
}

echo json_encode($listings);

?>