<?php
    #api endpoint for report table in MariaDB database

    header("Content-Type: application/json");

    require_once("../config/config.php");

    $conn = new mysqli($host, $user, $password, $dbname);

    $sql = "SELECT * FROM report";
    $result = $conn->query($sql);

    $reports = [];

    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
}

echo json_encode($reports);

?>