<?php
    #api endpoint for consumable_detail table in MariaDB database

    header("Content-Type: application/json");

    require_once("../config/config.php");

    $conn = new mysqli($host, $user, $password, $dbname);

    $sql = "SELECT * FROM consumable_detail";
    $result = $conn->query($sql);

    $consumable_details = [];

    while ($row = $result->fetch_assoc()) {
        $consumable_details[] = $row;
}

echo json_encode($consumable_details);

?>