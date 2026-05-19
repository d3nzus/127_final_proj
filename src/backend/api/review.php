<?php
    #api endpoint for review table in MariaDB database

    header("Content-Type: application/json");

    require_once("../config/config.php");

    $conn = new mysqli($host, $user, $password, $dbname);

    $sql = "SELECT * FROM review";
    $result = $conn->query($sql);

    $reviews = [];

    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
}

echo json_encode($reviews);

?>