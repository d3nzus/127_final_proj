<?php
    #api endpoint for non_consumable_detail table in MariaDB database

    header("Content-Type: application/json");

    require_once("../config/config.php");

    $conn = new mysqli($host, $user, $password, $dbname);

    $sql = "SELECT * FROM non_consumable_detail";
    $result = $conn->query($sql);

    $non_consumable_details = [];

    while ($row = $result->fetch_assoc()) {
        $non_consumable_details[] = $row;
}

echo json_encode($non_consumable_details);

?>