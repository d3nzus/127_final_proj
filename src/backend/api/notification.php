<?php
    #api endpoint for notification table in MariaDB database

    header("Content-Type: application/json");

    require_once("../config/config.php");

    $conn = new mysqli($host, $user, $password, $dbname);

    $sql = "SELECT * FROM notification";
    $result = $conn->query($sql);

    $notifications = [];

    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
}

echo json_encode($notifications);

?>